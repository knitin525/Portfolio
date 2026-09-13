<?php
/**
 * Knitin Portfolio — Sent Emails Log
 */

declare(strict_types=1);

$pageTitle = 'Sent Emails';
$activeNav = 'sent';

require_once __DIR__ . '/includes/header.php';

$pdo = db();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM emails WHERE direction = 'outbound' AND deleted_at IS NULL")->fetchColumn();
$totalPages = ceil($totalRows / $perPage) ?: 1;

$stmt = $pdo->query("
    SELECT e.*, c.subject AS conv_subject, ct.name AS contact_name,
           (SELECT COUNT(*) FROM attachments WHERE email_id = e.id) AS attachment_count
    FROM emails e
    JOIN conversations c ON e.conversation_id = c.id
    JOIN contacts ct ON e.contact_id = ct.id
    WHERE e.direction = 'outbound' AND e.deleted_at IS NULL
    ORDER BY e.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$sentEmails = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Outbound Messages (Sent via SMTP)</div>
        <a href="compose.php" class="btn btn-primary btn-sm">+ Compose New</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Attachments</th>
                    <th>Date Sent</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sentEmails)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 40px;">
                            No sent messages logged yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sentEmails as $email): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($email['to_name'] ?: $email['contact_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= htmlspecialchars($email['to_email']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($email['subject']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--admin-text-subtle);">Thread: <?= htmlspecialchars($email['conv_subject']) ?></div>
                            </td>
                            <td>
                                <span class="status-pill status-active">
                                    <?= ucfirst($email['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $email['attachment_count'] > 0 ? "{$email['attachment_count']} file(s)" : '—' ?>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--admin-text-muted);">
                                <?= date('M d, Y h:i A', strtotime($email['created_at'])) ?>
                            </td>
                            <td>
                                <a href="conversation.php?id=<?= $email['conversation_id'] ?>" class="btn btn-outline btn-sm">
                                    View Thread
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <div style="display: flex; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="sent.php?page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">&larr; Prev</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="sent.php?page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
