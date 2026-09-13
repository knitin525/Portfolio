<?php
/**
 * Knitin Portfolio — Direct Email Composer
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/MailSender.php';

Auth::requireAuth();

$pageTitle = 'Compose Message';
$activeNav = 'inbox';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $toEmail = filter_var(trim($_POST['to_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $toName = trim($_POST['to_name'] ?? '');
        $subject = trim($_POST['subject'] ?? '(No Subject)');
        $message = trim($_POST['message'] ?? '');
        $cc = trim($_POST['cc'] ?? '');
        $bcc = trim($_POST['bcc'] ?? '');

        if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid recipient email address.';
        } elseif (empty($message)) {
            $error = 'Message body cannot be empty.';
        } else {
            $pdo = db();

            // Find or create Contact
            $stmt = $pdo->prepare("SELECT id FROM contacts WHERE email = ? LIMIT 1");
            $stmt->execute([$toEmail]);
            $contact = $stmt->fetch();

            if ($contact) {
                $contactId = (int)$contact['id'];
            } else {
                $insertContact = $pdo->prepare("INSERT INTO contacts (name, email, status, created_at) VALUES (?, ?, 'contacted', NOW())");
                $insertContact->execute([$toName ?: 'Recipient', $toEmail]);
                $contactId = (int)$pdo->lastInsertId();
            }

            // Create Conversation
            $insertConv = $pdo->prepare("INSERT INTO conversations (contact_id, subject, status, priority, last_message_at, created_at) VALUES (?, ?, 'active', 'normal', NOW(), NOW())");
            $insertConv->execute([$contactId, $subject]);
            $convId = (int)$pdo->lastInsertId();

            // Handle Attachment
            $attachmentsForMail = [];
            $savedAttachment = null;

            if (!empty($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($file['name']));
                $ext = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));

                $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
                $targetDir = UPLOADS_PATH;
                if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
                $destPath = $targetDir . DIRECTORY_SEPARATOR . $storedName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $savedAttachment = [
                        'original_name' => $cleanName,
                        'stored_name' => $storedName,
                        'file_path' => 'uploads/' . $storedName,
                        'mime_type' => mime_content_type($destPath) ?: 'application/octet-stream',
                        'file_size' => (int)$file['size']
                    ];
                    $attachmentsForMail[] = [
                        'path' => $destPath,
                        'name' => $cleanName
                    ];
                }
            }

            // Outbound Message ID
            $domain = substr(strrchr((string)env('SMTP_USER', 'contact@knitin525.in'), "@"), 1) ?: 'knitin525.in';
            $messageId = '<out.' . bin2hex(random_bytes(12)) . '.' . time() . '@' . $domain . '>';

            // Send via SMTP
            $mailer = new MailSender();
            $res = $mailer->send([
                'to' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'text' => $message,
                'html' => nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')),
                'cc' => $cc ?: null,
                'bcc' => $bcc ?: null,
                'attachments' => $attachmentsForMail,
                'message_id' => $messageId,
            ]);

            if ($res['success']) {
                // Save in emails table
                $insertEmail = $pdo->prepare("
                    INSERT INTO emails (conversation_id, contact_id, message_id, direction, from_email, from_name, to_email, to_name, cc, bcc, subject, message_text, message_html, is_read, status, sent_at, created_at)
                    VALUES (?, ?, ?, 'outbound', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'sent', NOW(), NOW())
                ");
                $insertEmail->execute([
                    $convId,
                    $contactId,
                    $messageId,
                    env('SMTP_USER', 'contact@knitin525.in'),
                    env('ADMIN_NAME', 'Nitin Kumar'),
                    $toEmail,
                    $toName,
                    $cc ?: null,
                    $bcc ?: null,
                    $subject,
                    $message,
                    nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
                ]);
                $emailId = (int)$pdo->lastInsertId();

                if ($savedAttachment) {
                    $insertAtt = $pdo->prepare("INSERT INTO attachments (email_id, original_name, stored_name, file_path, mime_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $insertAtt->execute([
                        $emailId,
                        $savedAttachment['original_name'],
                        $savedAttachment['stored_name'],
                        $savedAttachment['file_path'],
                        $savedAttachment['mime_type'],
                        $savedAttachment['file_size']
                    ]);
                }

                redirect("conversation.php?id={$convId}");
            } else {
                $error = 'SMTP Send Error: ' . $res['message'];
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 760px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">Compose Direct Email</div>
        <a href="inbox.php" class="btn btn-outline btn-sm">Cancel</a>
    </div>

    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Recipient Email *</label>
                    <input type="email" name="to_email" class="form-control" placeholder="client@example.com" value="<?= htmlspecialchars($_POST['to_email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Recipient Name</label>
                    <input type="text" name="to_name" class="form-control" placeholder="Client Name" value="<?= htmlspecialchars($_POST['to_name'] ?? '') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>CC (Optional)</label>
                    <input type="text" name="cc" class="form-control" placeholder="cc@example.com" value="<?= htmlspecialchars($_POST['cc'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>BCC (Optional)</label>
                    <input type="text" name="bcc" class="form-control" placeholder="bcc@example.com" value="<?= htmlspecialchars($_POST['bcc'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Subject *</label>
                <input type="text" name="subject" class="form-control" placeholder="Project Update / Design Proposal" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Message *</label>
                <textarea name="message" class="form-control" style="min-height: 200px;" placeholder="Write your email here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Attachment (Optional)</label>
                <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.zip">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Send Email via SMTP
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
