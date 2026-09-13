<?php
/**
 * Knitin Portfolio — Email Synchronization Service
 * Imports IMAP emails into local MySQL database, resolves conversation threading, and updates sync logs.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ImapClient.php';

class EmailSyncService {
    public function sync(string $type = 'cron', int $limit = 30): array {
        $startedAt = date('Y-m-d H:i:s');
        $logId = null;

        try {
            $pdo = db();

            // Insert initial sync log
            $logStmt = $pdo->prepare("INSERT INTO email_sync_logs (sync_type, status, messages_processed, started_at) VALUES (?, 'running', 0, ?)");
            $logStmt->execute([$type, $startedAt]);
            $logId = (int)$pdo->lastInsertId();

            $client = new ImapClient();
            $messages = $client->fetchUnread($limit);

            $processedCount = 0;

            foreach ($messages as $msg) {
                if ($this->processMessage($pdo, $msg)) {
                    $processedCount++;
                }
            }

            // Update log to success
            $updateLog = $pdo->prepare("UPDATE email_sync_logs SET status = 'success', messages_processed = ?, completed_at = NOW() WHERE id = ?");
            $updateLog->execute([$processedCount, $logId]);

            // Update last sync time setting
            $updateSetting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_sync_time', NOW()) ON DUPLICATE KEY UPDATE setting_value = NOW()");
            $updateSetting->execute();

            return [
                'success' => true,
                'messages_processed' => $processedCount,
                'message' => "Successfully processed {$processedCount} new messages.",
            ];
        } catch (Throwable $e) {
            error_log("Email Sync Error: " . $e->getMessage());

            if ($logId) {
                try {
                    $pdo = db();
                    $updateLog = $pdo->prepare("UPDATE email_sync_logs SET status = 'error', error_message = ?, completed_at = NOW() WHERE id = ?");
                    $updateLog->execute([$e->getMessage(), $logId]);
                } catch (Throwable $ignore) {}
            }

            return [
                'success' => false,
                'messages_processed' => 0,
                'message' => 'Sync Error: ' . $e->getMessage(),
            ];
        }
    }

    private function processMessage(PDO $pdo, array $msg): bool {
        $messageId = trim($msg['message_id'] ?? '');
        if (empty($messageId)) {
            $messageId = '<' . md5(json_encode($msg)) . '@knitin525.in>';
        }

        // 1. Deduplication check
        $checkStmt = $pdo->prepare("SELECT id FROM emails WHERE message_id = ? LIMIT 1");
        $checkStmt->execute([$messageId]);
        if ($checkStmt->fetch()) {
            return false; // Already imported
        }

        $fromEmail = filter_var($msg['from_email'] ?? '', FILTER_SANITIZE_EMAIL);
        if (empty($fromEmail)) {
            $fromEmail = 'unknown@sender.com';
        }
        $fromName = trim($msg['from_name'] ?? '');
        $subject = trim($msg['subject'] ?? '(No Subject)');
        $textBody = $msg['text'] ?? '';
        $htmlBody = $msg['html'] ?? '';
        $receivedAt = $msg['date'] ?? date('Y-m-d H:i:s');
        $inReplyTo = !empty($msg['in_reply_to']) ? trim($msg['in_reply_to']) : null;
        $references = !empty($msg['references']) ? trim($msg['references']) : null;

        // 2. Find or create Contact
        $contactStmt = $pdo->prepare("SELECT id FROM contacts WHERE email = ? LIMIT 1");
        $contactStmt->execute([$fromEmail]);
        $contact = $contactStmt->fetch();

        if ($contact) {
            $contactId = (int)$contact['id'];
            if ($fromName) {
                $updateContact = $pdo->prepare("UPDATE contacts SET name = COALESCE(NULLIF(name, ''), ?), updated_at = NOW() WHERE id = ?");
                $updateContact->execute([$fromName, $contactId]);
            }
        } else {
            $insertContact = $pdo->prepare("INSERT INTO contacts (name, email, status, created_at) VALUES (?, ?, 'new_lead', NOW())");
            $insertContact->execute([$fromName ?: 'Contact', $fromEmail]);
            $contactId = (int)$pdo->lastInsertId();
        }

        // 3. Resolve Conversation Threading
        $conversationId = null;

        // A. Match In-Reply-To
        if ($inReplyTo) {
            $convStmt = $pdo->prepare("SELECT conversation_id FROM emails WHERE message_id = ? LIMIT 1");
            $convStmt->execute([$inReplyTo]);
            $found = $convStmt->fetch();
            if ($found) {
                $conversationId = (int)$found['conversation_id'];
            }
        }

        // B. Match References header
        if (!$conversationId && $references) {
            $refIds = preg_split('/\s+/', $references);
            foreach ($refIds as $refId) {
                $refId = trim($refId);
                if ($refId) {
                    $refStmt = $pdo->prepare("SELECT conversation_id FROM emails WHERE message_id = ? LIMIT 1");
                    $refStmt->execute([$refId]);
                    $found = $refStmt->fetch();
                    if ($found) {
                        $conversationId = (int)$found['conversation_id'];
                        break;
                    }
                }
            }
        }

        // C. Match Normalized Subject with same contact
        if (!$conversationId) {
            $cleanSubject = preg_replace('/^(re|fwd|fw):\s*/i', '', $subject);
            $cleanSubject = trim($cleanSubject);

            $subjStmt = $pdo->prepare("SELECT id FROM conversations WHERE contact_id = ? AND (subject = ? OR subject LIKE ?) ORDER BY last_message_at DESC LIMIT 1");
            $subjStmt->execute([$contactId, $subject, "%{$cleanSubject}%"]);
            $found = $subjStmt->fetch();
            if ($found) {
                $conversationId = (int)$found['id'];
            }
        }

        // D. Create New Conversation if no thread matched
        if (!$conversationId) {
            $newConv = $pdo->prepare("INSERT INTO conversations (contact_id, subject, status, priority, last_message_at, created_at) VALUES (?, ?, 'active', 'normal', ?, NOW())");
            $newConv->execute([$contactId, $subject, $receivedAt]);
            $conversationId = (int)$pdo->lastInsertId();
        } else {
            // Update last_message_at on existing conversation
            $updateConv = $pdo->prepare("UPDATE conversations SET last_message_at = ?, status = 'active', updated_at = NOW() WHERE id = ?");
            $updateConv->execute([$receivedAt, $conversationId]);
        }

        // 4. Insert Email Record
        $insertEmail = $pdo->prepare("INSERT INTO emails (conversation_id, contact_id, message_id, in_reply_to, references_header, direction, from_email, from_name, to_email, to_name, subject, message_text, message_html, is_read, status, received_at, created_at) VALUES (?, ?, ?, ?, ?, 'inbound', ?, ?, ?, 'Nitin Kumar', ?, ?, ?, 0, 'received', ?, NOW())");
        $insertEmail->execute([
            $conversationId,
            $contactId,
            $messageId,
            $inReplyTo,
            $references,
            $fromEmail,
            $fromName,
            env('ADMIN_EMAIL', 'contact@knitin525.in'),
            $subject,
            $textBody,
            $htmlBody,
            $receivedAt
        ]);
        $emailId = (int)$pdo->lastInsertId();

        // 5. Insert Attachments
        if (!empty($msg['attachments'])) {
            $attStmt = $pdo->prepare("INSERT INTO attachments (email_id, original_name, stored_name, file_path, mime_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            foreach ($msg['attachments'] as $att) {
                $attStmt->execute([
                    $emailId,
                    $att['original_name'],
                    $att['stored_name'],
                    $att['file_path'],
                    $att['mime_type'],
                    $att['file_size']
                ]);
            }
        }

        return true;
    }
}
