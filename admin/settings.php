<?php
/**
 * Knitin Portfolio — Admin Settings & Mail Diagnostics
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

Auth::requireAuth();

$pdo = db();
$pageTitle = 'Settings & Diagnostics';
$activeNav = 'settings';

$success = '';
$error = '';

// Handle form updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired.';
    } else {
        $action = $_POST['action'] ?? '';

        // Save Auto-reply Settings
        if ($action === 'save_auto_reply') {
            $autoReplyEnabled = isset($_POST['auto_reply_enabled']) ? '1' : '0';
            $autoReplySubject = trim($_POST['auto_reply_subject'] ?? '');
            $autoReplyTemplate = trim($_POST['auto_reply_template'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_reply_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$autoReplyEnabled, $autoReplyEnabled]);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_reply_subject', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$autoReplySubject, $autoReplySubject]);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_reply_template', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$autoReplyTemplate, $autoReplyTemplate]);

            $success = 'Auto-reply settings saved successfully!';
        }

        // Change Admin Password
        if ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            $adminId = Auth::id();
            $adminStmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $adminStmt->execute([$adminId]);
            $admin = $adminStmt->fetch();

            if (!password_verify($currentPass, $admin['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPass) < 8) {
                $error = 'New password must be at least 8 characters long.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'New password and confirmation do not match.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE admins SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$newHash, $adminId]);
                $success = 'Admin password updated successfully!';
            }
        }
    }
}

// Fetch current settings
$settings = [];
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $settingsStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Recent Sync Logs
$syncLogsStmt = $pdo->query("SELECT * FROM email_sync_logs ORDER BY started_at DESC LIMIT 5");
$syncLogs = $syncLogsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #065f46; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #991b1b; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 24px;">

    <!-- Email Configuration & Diagnostics -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Mail Server Diagnostics</div>
        </div>
        <div class="card-body">
            <div style="font-size: 0.88rem; color: var(--admin-text-muted); margin-bottom: 20px;">
                Configuration loaded from <code>.env</code> file. Passwords are never shown.
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 8px; color: var(--admin-primary);">SMTP (Outgoing Mail)</div>
                <div style="font-size: 0.85rem; line-height: 1.8;">
                    <strong>Host:</strong> <?= htmlspecialchars((string)env('SMTP_HOST', 'Not set')) ?>:<?= htmlspecialchars((string)env('SMTP_PORT', '465')) ?><br>
                    <strong>Encryption:</strong> <?= strtoupper(htmlspecialchars((string)env('SMTP_ENCRYPTION', 'ssl'))) ?><br>
                    <strong>Username:</strong> <?= htmlspecialchars((string)env('SMTP_USER', 'Not set')) ?><br>
                    <strong>Password:</strong> ••••••••••••
                </div>
                <button type="button" id="btnTestSmtp" class="btn btn-outline btn-sm" style="margin-top: 12px;">
                    Test SMTP Connection
                </button>
                <div id="smtpTestResult" style="margin-top: 8px; font-size: 0.82rem;"></div>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 8px; color: var(--admin-primary);">IMAP (Incoming Mail Sync)</div>
                <div style="font-size: 0.85rem; line-height: 1.8;">
                    <strong>Host:</strong> <?= htmlspecialchars((string)env('IMAP_HOST', 'Not set')) ?>:<?= htmlspecialchars((string)env('IMAP_PORT', '993')) ?><br>
                    <strong>Encryption:</strong> <?= strtoupper(htmlspecialchars((string)env('IMAP_ENCRYPTION', 'ssl'))) ?><br>
                    <strong>Username:</strong> <?= htmlspecialchars((string)env('IMAP_USER', 'Not set')) ?><br>
                    <strong>Mailbox:</strong> <?= htmlspecialchars((string)env('IMAP_MAILBOX', 'INBOX')) ?><br>
                    <strong>Password:</strong> ••••••••••••
                </div>
                <button type="button" id="btnTestImap" class="btn btn-outline btn-sm" style="margin-top: 12px;">
                    Test IMAP Connection
                </button>
                <div id="imapTestResult" style="margin-top: 8px; font-size: 0.82rem;"></div>
            </div>

            <div>
                <div style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: var(--admin-text-muted); margin-bottom: 8px;">Hostinger Cron Command</div>
                <div style="background: #0f172a; color: #38bdf8; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 0.82rem; word-break: break-all;">
                    /usr/bin/php <?= htmlspecialchars(ROOT_PATH) ?>/cron/email-sync.php > /dev/null 2>&1
                </div>
                <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 6px;">
                    Set up this command in Hostinger cPanel &rarr; Cron Jobs to run every 5 or 10 minutes.
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-Reply Template Editor -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Auto-Reply Confirmation Email</div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="save_auto_reply">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="autoReplyEnabled" name="auto_reply_enabled" value="1" <?= ($settings['auto_reply_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="autoReplyEnabled" style="margin-bottom: 0; cursor: pointer; text-transform: none; font-size: 0.95rem;">
                        Enable automatic confirmation email to website visitors
                    </label>
                </div>

                <div class="form-group">
                    <label>Auto-Reply Subject Line</label>
                    <input type="text" name="auto_reply_subject" class="form-control" value="<?= htmlspecialchars($settings['auto_reply_subject'] ?? 'Thank you for contacting Nitin Kumar') ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Message Template</label>
                    <textarea name="auto_reply_template" class="form-control" style="min-height: 160px;" required><?= htmlspecialchars($settings['auto_reply_template'] ?? '') ?></textarea>
                    <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 6px;">
                        Available dynamic tags: <code>{name}</code> (visitor name), <code>{subject}</code> (inquiry subject), <code>{service}</code> (selected service).
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                    Save Auto-Reply Template
                </button>
            </form>
        </div>
    </div>

    <!-- Admin Password Change -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Change Administrator Password</div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>New Password (min 8 characters)</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8">
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                    Update Admin Password
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Email Sync Logs -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent IMAP Sync Activity</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Messages</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($syncLogs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-text-muted); padding: 24px;">
                                No sync runs logged yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($syncLogs as $log): ?>
                            <tr>
                                <td><?= ucfirst($log['sync_type']) ?></td>
                                <td>
                                    <span class="status-pill status-<?= $log['status'] === 'success' ? 'active' : 'closed' ?>">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td><?= $log['messages_processed'] ?> processed</td>
                                <td style="font-size: 0.8rem; color: var(--admin-text-muted);">
                                    <?= date('M d, H:i:s', strtotime($log['started_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.getElementById('btnTestSmtp')?.addEventListener('click', async function() {
    const resEl = document.getElementById('smtpTestResult');
    resEl.innerHTML = '<span style="color: var(--admin-primary);">Testing SMTP connection...</span>';
    this.disabled = true;

    try {
        const response = await fetch('ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'test_smtp',
                csrf_token: getCsrfToken()
            })
        });
        const data = await response.json();
        resEl.innerHTML = `<span style="color: ${data.success ? '#10b981' : '#ef4444'}; font-weight: 600;">${data.message}</span>`;
    } catch (e) {
        resEl.innerHTML = '<span style="color: #ef4444;">Connection test failed.</span>';
    } finally {
        this.disabled = false;
    }
});

document.getElementById('btnTestImap')?.addEventListener('click', async function() {
    const resEl = document.getElementById('imapTestResult');
    resEl.innerHTML = '<span style="color: var(--admin-primary);">Testing IMAP connection...</span>';
    this.disabled = true;

    try {
        const response = await fetch('ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'test_imap',
                csrf_token: getCsrfToken()
            })
        });
        const data = await response.json();
        resEl.innerHTML = `<span style="color: ${data.success ? '#10b981' : '#ef4444'}; font-weight: 600;">${data.message}</span>`;
    } catch (e) {
        resEl.innerHTML = '<span style="color: #ef4444;">Connection test failed.</span>';
    } finally {
        this.disabled = false;
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
