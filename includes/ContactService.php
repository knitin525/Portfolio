<?php
/**
 * Knitin Portfolio — Contact Form Service
 * Validates, stores, and orchestrates contact form submissions, notifications, and auto-replies.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/MailSender.php';

class ContactService {
    private const MAX_SUBMISSIONS_PER_HOUR = 6;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_EXTS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public function handleSubmission(array $data, ?array $file = null, ?string $clientIp = null): array {
        // 1. Honeypot check (hidden "website" or "hp_field")
        if (!empty($data['website']) || !empty($data['hp_company_url'])) {
            // Silently return success to trick spam bots without saving
            return [
                'success' => true,
                'message' => 'Thank you! Your message has been received.',
            ];
        }

        // 2. Validate CSRF token (if present)
        if (isset($data['csrf_token']) && !verify_csrf($data['csrf_token'])) {
            return [
                'success' => false,
                'message' => 'Security token expired. Please refresh the page and try again.',
            ];
        }

        // 3. Rate limiting by IP
        $ip = $clientIp ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        if ($this->isRateLimited($ip)) {
            return [
                'success' => false,
                'message' => 'Too many messages sent from this IP. Please wait a few minutes before trying again.',
            ];
        }

        // 4. Sanitize and validate inputs
        $name = trim(strip_tags($data['name'] ?? ''));
        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = trim(strip_tags($data['phone'] ?? ''));
        $company = trim(strip_tags($data['company'] ?? ''));
        $service = trim(strip_tags($data['service'] ?? 'General Inquiry'));
        $subject = trim(strip_tags($data['subject'] ?? ''));
        $message = trim(strip_tags($data['message'] ?? ''));
        $source = trim(strip_tags($data['source'] ?? ($_SERVER['HTTP_REFERER'] ?? 'knitin525.in#contact')));

        if (empty($name)) {
            return ['success' => false, 'message' => 'Please enter your full name.'];
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        if (empty($message)) {
            return ['success' => false, 'message' => 'Please enter your message.'];
        }
        if (empty($subject)) {
            $subject = "Project Inquiry ({$service}) from {$name}";
        }

        // 5. Handle File Attachment (if uploaded)
        $attachmentData = null;
        if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            $fileCheck = $this->validateAndSaveUpload($file);
            if (!$fileCheck['success']) {
                return $fileCheck;
            }
            $attachmentData = $fileCheck['data'];
        }

        // 6. Database Storage (Transaction)
        try {
            $pdo = db();
            $pdo->beginTransaction();

            // A. Find or create Contact
            $stmt = $pdo->prepare("SELECT id FROM contacts WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $contact = $stmt->fetch();

            if ($contact) {
                $contactId = (int)$contact['id'];
                $updateContact = $pdo->prepare("UPDATE contacts SET name = ?, phone = COALESCE(NULLIF(?, ''), phone), company = COALESCE(NULLIF(?, ''), company), updated_at = NOW() WHERE id = ?");
                $updateContact->execute([$name, $phone, $company, $contactId]);
            } else {
                $insertContact = $pdo->prepare("INSERT INTO contacts (name, email, phone, company, status, created_at) VALUES (?, ?, ?, ?, 'new_lead', NOW())");
                $insertContact->execute([$name, $email, $phone, $company]);
                $contactId = (int)$pdo->lastInsertId();
            }

            // B. Create Conversation Thread
            $insertConv = $pdo->prepare("INSERT INTO conversations (contact_id, subject, status, priority, last_message_at, created_at) VALUES (?, ?, 'active', 'normal', NOW(), NOW())");
            $insertConv->execute([$contactId, $subject]);
            $conversationId = (int)$pdo->lastInsertId();

            // C. Generate RFC 2822 Message-ID
            $domain = substr(strrchr((string)env('SMTP_USER', 'contact@knitin525.in'), "@"), 1) ?: 'knitin525.in';
            $messageId = '<contact.' . bin2hex(random_bytes(12)) . '.' . time() . '@' . $domain . '>';

            // D. Insert into emails (direction = inbound)
            $insertEmail = $pdo->prepare("INSERT INTO emails (conversation_id, contact_id, message_id, direction, from_email, from_name, to_email, to_name, subject, message_text, message_html, is_read, status, received_at, created_at) VALUES (?, ?, ?, 'inbound', ?, ?, ?, 'Nitin Kumar', ?, ?, ?, 0, 'received', NOW(), NOW())");
            $insertEmail->execute([
                $conversationId,
                $contactId,
                $messageId,
                $email,
                $name,
                env('ADMIN_EMAIL', 'contact@knitin525.in'),
                $subject,
                $message,
                nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
            ]);
            $emailId = (int)$pdo->lastInsertId();

            // E. Save attachment to DB if present
            $attachmentPathForSubmission = null;
            if ($attachmentData) {
                $insertAtt = $pdo->prepare("INSERT INTO attachments (email_id, original_name, stored_name, file_path, mime_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insertAtt->execute([
                    $emailId,
                    $attachmentData['original_name'],
                    $attachmentData['stored_name'],
                    $attachmentData['file_path'],
                    $attachmentData['mime_type'],
                    $attachmentData['file_size']
                ]);
                $attachmentPathForSubmission = $attachmentData['file_path'];
            }

            // F. Insert into contact_submissions
            $insertSubm = $pdo->prepare("INSERT INTO contact_submissions (contact_id, conversation_id, email_id, service, phone, company, subject, message, attachment_path, status, source, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, NOW())");
            $insertSubm->execute([
                $contactId,
                $conversationId,
                $emailId,
                $service,
                $phone,
                $company,
                $subject,
                $message,
                $attachmentPathForSubmission,
                $source,
                $ip
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Contact form DB Error: " . $e->getMessage());
            // Even if DB fails, continue to notify admin so no lead is lost!
        }

        // 7. Send Notifications & Auto-replies via SMTP
        $mailer = new MailSender();

        // Admin Notification
        if (env('ADMIN_NOTIFY', true)) {
            $adminEmail = (string)env('ADMIN_EMAIL', 'contact@knitin525.in');
            $adminSubject = "New Portfolio Lead: {$name} — {$subject}";
            $adminHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <h2 style='color: #2563eb; margin-top: 0;'>New Contact Form Submission</h2>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                        <tr><td style='padding: 8px 0; color: #64748b; width: 120px;'><strong>Name:</strong></td><td>{$name}</td></tr>
                        <tr><td style='padding: 8px 0; color: #64748b;'><strong>Email:</strong></td><td><a href='mailto:{$email}'>{$email}</a></td></tr>
                        " . ($phone ? "<tr><td style='padding: 8px 0; color: #64748b;'><strong>Phone:</strong></td><td>{$phone}</td></tr>" : "") . "
                        " . ($company ? "<tr><td style='padding: 8px 0; color: #64748b;'><strong>Company:</strong></td><td>{$company}</td></tr>" : "") . "
                        <tr><td style='padding: 8px 0; color: #64748b;'><strong>Service:</strong></td><td>{$service}</td></tr>
                        <tr><td style='padding: 8px 0; color: #64748b;'><strong>Subject:</strong></td><td>{$subject}</td></tr>
                        <tr><td style='padding: 8px 0; color: #64748b;'><strong>Source:</strong></td><td>{$source}</td></tr>
                    </table>
                    <div style='background: #f8fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #2563eb; margin-bottom: 20px;'>
                        <strong>Message:</strong><br>
                        <p style='margin: 8px 0 0; line-height: 1.6; white-space: pre-line;'>" . htmlspecialchars($message) . "</p>
                    </div>
                    <p style='font-size: 12px; color: #94a3b8; margin-bottom: 0;'>Logged into Knitin Portfolio Admin CRM at " . date('d M Y, h:i A') . " IST</p>
                </div>
            ";

            $adminAtts = [];
            if ($attachmentData && file_exists(ROOT_PATH . '/' . $attachmentData['file_path'])) {
                $adminAtts[] = [
                    'path' => ROOT_PATH . '/' . $attachmentData['file_path'],
                    'name' => $attachmentData['original_name']
                ];
            }

            $mailer->send([
                'to' => $adminEmail,
                'reply_to' => $email,
                'reply_to_name' => $name,
                'subject' => $adminSubject,
                'html' => $adminHtml,
                'attachments' => $adminAtts,
            ]);
        }

        // Visitor Auto-Reply
        $autoReplyEnabled = $this->getSetting('auto_reply_enabled', '1') === '1';
        if ($autoReplyEnabled) {
            $replySubject = $this->getSetting('auto_reply_subject', 'Thank you for contacting Nitin Kumar');
            $replyTemplate = $this->getSetting('auto_reply_template', "Hi {name},\n\nThank you for getting in touch!\n\nI have received your message regarding \"{subject}\" and will review it shortly.\n\nBest regards,\nNitin Kumar\nGraphic Designer & Web Developer\nhttps://knitin525.in/");

            $replyBody = str_replace(
                ['{name}', '{subject}', '{service}'],
                [$name, $subject, $service],
                $replyTemplate
            );

            $replyHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <div style='margin-bottom: 20px;'>
                        <span style='font-size: 20px; font-weight: bold; color: #0f172a;'>Knitin<span style='color: #2563eb;'>.</span></span>
                    </div>
                    <div style='font-size: 15px; line-height: 1.7; color: #334155; white-space: pre-line;'>
                        " . htmlspecialchars($replyBody) . "
                    </div>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;'>
                    <div style='font-size: 12px; color: #64748b; line-height: 1.5;'>
                        <strong>Nitin Kumar</strong><br>
                        Graphic Designer &amp; Web Developer (12+ Years Experience)<br>
                        Chandigarh / Panchkula, India<br>
                        <a href='https://knitin525.in/' style='color: #2563eb; text-decoration: none;'>knitin525.in</a> |
                        <a href='mailto:contact@knitin525.in' style='color: #2563eb; text-decoration: none;'>contact@knitin525.in</a>
                    </div>
                </div>
            ";

            $mailer->send([
                'to' => $email,
                'to_name' => $name,
                'subject' => $replySubject,
                'text' => $replyBody,
                'html' => $replyHtml,
                'in_reply_to' => $messageId,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully. Nitin will respond shortly.',
        ];
    }

    private function validateAndSaveUpload(array $file): array {
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Attachment file size exceeds the 10 MB limit.'];
        }

        $origName = basename($file['name']);
        $cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $origName);
        $ext = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Allowed formats: PDF, JPG, PNG, WEBP, DOCX, XLSX, ZIP.'
            ];
        }

        $targetDir = UPLOADS_PATH;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'message' => 'Could not save file attachment. Please try again.'];
        }

        return [
            'success' => true,
            'data' => [
                'original_name' => $cleanName,
                'stored_name' => $storedName,
                'file_path' => 'uploads/' . $storedName,
                'mime_type' => mime_content_type($destPath) ?: 'application/octet-stream',
                'file_size' => (int)$file['size'],
            ]
        ];
    }

    private function isRateLimited(string $ip): bool {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_submissions WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$ip]);
            $count = (int)$stmt->fetchColumn();
            return $count >= self::MAX_SUBMISSIONS_PER_HOUR;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getSetting(string $key, string $default = ''): string {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? (string)$val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}
