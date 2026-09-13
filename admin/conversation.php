<?php
/**
 * Knitin Portfolio — Threaded Conversation & Reply Composer
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/MailSender.php';

Auth::requireAuth();

$pdo = db();
$convId = (int)($_GET['id'] ?? 0);

if (!$convId) {
    redirect('inbox.php');
}

// Fetch Conversation & Contact
$convStmt = $pdo->prepare("
    SELECT c.*, ct.name AS contact_name, ct.email AS contact_email, ct.phone AS contact_phone,
           ct.company AS contact_company, ct.status AS contact_status, ct.notes AS contact_notes
    FROM conversations c
    JOIN contacts ct ON c.contact_id = ct.id
    WHERE c.id = ? LIMIT 1
");
$convStmt->execute([$convId]);
$conversation = $convStmt->fetch();

if (!$conversation) {
    redirect('inbox.php');
}

$pageTitle = $conversation['subject'];
$activeNav = 'inbox';

// Mark all inbound unread messages in this conversation as read
$markRead = $pdo->prepare("UPDATE emails SET is_read = 1 WHERE conversation_id = ? AND direction = 'inbound' AND is_read = 0");
$markRead->execute([$convId]);

$replySuccess = '';
$replyError = '';

// Handle Reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reply') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $replyError = 'Security token expired. Please try again.';
    } else {
        $replyToEmail = trim($_POST['to'] ?? $conversation['contact_email']);
        $replySubject = trim($_POST['subject'] ?? 'Re: ' . $conversation['subject']);
        $replyMessage = trim($_POST['message'] ?? '');
        $replyCc = trim($_POST['cc'] ?? '');
        $replyBcc = trim($_POST['bcc'] ?? '');

        if (empty($replyMessage)) {
            $replyError = 'Reply message cannot be empty.';
        } else {
            // Find latest inbound email to reference
            $lastInboundStmt = $pdo->prepare("SELECT message_id FROM emails WHERE conversation_id = ? AND direction = 'inbound' ORDER BY created_at DESC LIMIT 1");
            $lastInboundStmt->execute([$convId]);
            $lastInbound = $lastInboundStmt->fetch();
            $inReplyTo = $lastInbound['message_id'] ?? null;

            // Handle attachment if any
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

            // Generate Outbound Message-ID
            $domain = substr(strrchr((string)env('SMTP_USER', 'contact@knitin525.in'), "@"), 1) ?: 'knitin525.in';
            $outboundMessageId = '<reply.' . bin2hex(random_bytes(12)) . '.' . time() . '@' . $domain . '>';

            // Send via SMTP
            $mailer = new MailSender();
            $sendRes = $mailer->send([
                'to' => $replyToEmail,
                'to_name' => $conversation['contact_name'],
                'subject' => $replySubject,
                'text' => $replyMessage,
                'html' => nl2br(htmlspecialchars($replyMessage, ENT_QUOTES, 'UTF-8')),
                'in_reply_to' => $inReplyTo,
                'references' => $inReplyTo,
                'cc' => $replyCc ?: null,
                'bcc' => $replyBcc ?: null,
                'attachments' => $attachmentsForMail,
                'message_id' => $outboundMessageId,
            ]);

            if ($sendRes['success']) {
                // Save outbound email into database
                $insertReply = $pdo->prepare("
                    INSERT INTO emails (conversation_id, contact_id, message_id, in_reply_to, direction, from_email, from_name, to_email, to_name, cc, bcc, subject, message_text, message_html, is_read, status, sent_at, created_at)
                    VALUES (?, ?, ?, ?, 'outbound', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'sent', NOW(), NOW())
                ");
                $insertReply->execute([
                    $convId,
                    $conversation['contact_id'],
                    $outboundMessageId,
                    $inReplyTo,
                    env('SMTP_USER', 'contact@knitin525.in'),
                    env('ADMIN_NAME', 'Nitin Kumar'),
                    $replyToEmail,
                    $conversation['contact_name'],
                    $replyCc ?: null,
                    $replyBcc ?: null,
                    $replySubject,
                    $replyMessage,
                    nl2br(htmlspecialchars($replyMessage, ENT_QUOTES, 'UTF-8'))
                ]);
                $replyEmailId = (int)$pdo->lastInsertId();

                if ($savedAttachment) {
                    $insertAtt = $pdo->prepare("INSERT INTO attachments (email_id, original_name, stored_name, file_path, mime_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $insertAtt->execute([
                        $replyEmailId,
                        $savedAttachment['original_name'],
                        $savedAttachment['stored_name'],
                        $savedAttachment['file_path'],
                        $savedAttachment['mime_type'],
                        $savedAttachment['file_size']
                    ]);
                }

                // Update conversation and contact status
                $updateConv = $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), status = 'active', updated_at = NOW() WHERE id = ?");
                $updateConv->execute([$convId]);

                $updateContact = $pdo->prepare("UPDATE contacts SET status = 'contacted', updated_at = NOW() WHERE id = ?");
                $updateContact->execute([$conversation['contact_id']]);

                // Update lead submission if any
                $updateLead = $pdo->prepare("UPDATE contact_submissions SET status = 'replied' WHERE conversation_id = ? AND status = 'new'");
                $updateLead->execute([$convId]);

                $replySuccess = 'Your reply has been dispatched successfully!';
            } else {
                $replyError = 'Failed to send reply: ' . $sendRes['message'];
            }
        }
    }
}

// Fetch all emails in this conversation
$emailsStmt = $pdo->prepare("
    SELECT * FROM emails
    WHERE conversation_id = ? AND deleted_at IS NULL
    ORDER BY created_at ASC
");
$emailsStmt->execute([$convId]);
$messages = $emailsStmt->fetchAll();

// Fetch attachments for all emails
$emailIds = array_column($messages, 'id');
$attachmentsByEmail = [];
if (!empty($emailIds)) {
    $inClause = implode(',', array_map('intval', $emailIds));
    $attStmt = $pdo->query("SELECT * FROM attachments WHERE email_id IN ({$inClause})");
    while ($att = $attStmt->fetch()) {
        $attachmentsByEmail[$att['email_id']][] = $att;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <a href="inbox.php" class="btn btn-outline btn-sm">
        &larr; Back to Inbox
    </a>
    <div style="display: flex; gap: 8px;">
        <span class="status-pill status-<?= htmlspecialchars($conversation['status']) ?>">
            <?= ucfirst($conversation['status']) ?>
        </span>
        <span class="status-pill" style="background: #f1f5f9; color: #475569;">
            Priority: <?= ucfirst($conversation['priority']) ?>
        </span>
    </div>
</div>

<?php if ($replySuccess): ?>
    <div class="alert" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #065f46; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($replySuccess) ?>
    </div>
<?php endif; ?>

<?php if ($replyError): ?>
    <div class="alert" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #991b1b; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($replyError) ?>
    </div>
<?php endif; ?>

<div class="thread-container">
    <!-- Main Thread Messages & Reply Composer -->
    <div class="thread-messages">
        <?php foreach ($messages as $msg): ?>
            <?php
                $isInbound = ($msg['direction'] === 'inbound');
                $senderName = $isInbound ? ($msg['from_name'] ?: $conversation['contact_name']) : ($msg['from_name'] ?: 'Nitin Kumar');
                $senderEmail = $msg['from_email'];
                $atts = $attachmentsByEmail[$msg['id']] ?? [];
            ?>
            <div class="message-card">
                <div class="message-header">
                    <div class="message-sender-wrap">
                        <div class="message-avatar <?= !$isInbound ? 'admin' : '' ?>">
                            <?= strtoupper(substr($senderName, 0, 1)) ?>
                        </div>
                        <div>
                            <div class="message-meta-name"><?= htmlspecialchars($senderName) ?></div>
                            <div class="message-meta-email"><?= htmlspecialchars($senderEmail) ?></div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="<?= $isInbound ? 'message-badge-inbound' : 'message-badge-outbound' ?>">
                            <?= $isInbound ? 'Client Inbound' : 'Sent Reply' ?>
                        </span>
                        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">
                            <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <div class="message-body">
                    <?php if (!empty($msg['message_html'])): ?>
                        <?= nl2br(strip_tags($msg['message_html'], '<p><br><a><b><strong><i><em><ul><ol><li>')) ?>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($msg['message_text'] ?? '')) ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($atts)): ?>
                    <div class="message-attachments">
                        <?php foreach ($atts as $att): ?>
                            <a href="attachment.php?id=<?= $att['id'] ?>" class="attachment-pill" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                <span><?= htmlspecialchars($att['original_name']) ?></span>
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">(<?= round($att['file_size'] / 1024, 1) ?> KB)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Reply Composer -->
        <div class="reply-card">
            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; margin-bottom: 16px; color: var(--admin-text);">
                Reply to <?= htmlspecialchars($conversation['contact_name']) ?>
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="send_reply">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label>To</label>
                    <input type="email" name="to" class="form-control" value="<?= htmlspecialchars($conversation['contact_email']) ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>CC (Optional)</label>
                        <input type="text" name="cc" class="form-control" placeholder="cc@example.com">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" value="Re: <?= htmlspecialchars($conversation['subject']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" placeholder="Type your reply here..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Attach File (Optional)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.zip">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 16px;">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Send Reply via SMTP
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contact Profile & CRM Sidebar -->
    <div class="thread-sidebar">
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 0.95rem;">Client Details</div>
            </div>
            <div class="card-body" style="padding: 20px;">
                <div style="margin-bottom: 16px;">
                    <div style="font-weight: 700; font-size: 1.05rem;"><?= htmlspecialchars($conversation['contact_name']) ?></div>
                    <a href="mailto:<?= htmlspecialchars($conversation['contact_email']) ?>" style="color: var(--admin-primary); font-size: 0.88rem;"><?= htmlspecialchars($conversation['contact_email']) ?></a>
                </div>

                <?php if ($conversation['contact_phone']): ?>
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 700; text-transform: uppercase;">Phone</div>
                        <div style="font-size: 0.9rem;"><?= htmlspecialchars($conversation['contact_phone']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($conversation['contact_company']): ?>
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 700; text-transform: uppercase;">Company</div>
                        <div style="font-size: 0.9rem;"><?= htmlspecialchars($conversation['contact_company']) ?></div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Lifecycle Status</div>
                    <span class="status-pill status-<?= htmlspecialchars($conversation['contact_status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $conversation['contact_status'])) ?>
                    </span>
                </div>

                <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 20px 0;">

                <div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Internal Notes</div>
                    <textarea id="contactNotesInput" class="form-control" style="font-size: 0.85rem; min-height: 100px;" placeholder="Private notes about this client..."><?= htmlspecialchars($conversation['contact_notes'] ?? '') ?></textarea>
                    <button type="button" id="btnSaveContactNotes" data-contact-id="<?= $conversation['contact_id'] ?>" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 8px;">
                        Save Notes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
