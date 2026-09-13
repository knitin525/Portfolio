<?php
/**
 * Knitin Portfolio — Admin Dashboard Overview
 */

declare(strict_types=1);

$pageTitle = 'Dashboard Overview';
$activeNav = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$pdo = db();

// Fetch metrics
$totalContacts = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$totalConversations = (int)$pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
$unreadEmails = (int)$pdo->query("SELECT COUNT(*) FROM emails WHERE is_read = 0 AND direction = 'inbound' AND deleted_at IS NULL")->fetchColumn();
$sentEmails = (int)$pdo->query("SELECT COUNT(*) FROM emails WHERE direction = 'outbound' AND deleted_at IS NULL")->fetchColumn();
$totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();
$messagesThisMonth = (int)$pdo->query("SELECT COUNT(*) FROM emails WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') AND deleted_at IS NULL")->fetchColumn();

// Fetch recent conversations
$recentConvsStmt = $pdo->query("
    SELECT c.*, ct.name AS contact_name, ct.email AS contact_email,
           (SELECT COUNT(*) FROM emails WHERE conversation_id = c.id AND deleted_at IS NULL) AS message_count,
           (SELECT COUNT(*) FROM emails WHERE conversation_id = c.id AND is_read = 0 AND direction = 'inbound' AND deleted_at IS NULL) AS unread_in_conv
    FROM conversations c
    JOIN contacts ct ON c.contact_id = ct.id
    ORDER BY c.last_message_at DESC
    LIMIT 6
");
$recentConversations = $recentConvsStmt->fetchAll();

// Fetch recent website leads
$recentLeadsStmt = $pdo->query("
    SELECT cs.*, ct.name AS contact_name, ct.email AS contact_email
    FROM contact_submissions cs
    JOIN contacts ct ON cs.contact_id = ct.id
    ORDER BY cs.created_at DESC
    LIMIT 6
");
$recentLeads = $recentLeadsStmt->fetchAll();

// Last sync info
$lastSyncStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'last_sync_time' LIMIT 1");
$lastSyncTime = $lastSyncStmt->fetchColumn() ?: null;
?>

<!-- Metric Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        </div>
        <div class="stat-meta">
            <div class="stat-label">Unread Emails</div>
            <div class="stat-value" style="color: var(--admin-primary);"><?= $unreadEmails ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-emerald">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
        </div>
        <div class="stat-meta">
            <div class="stat-label">Form Inquiries</div>
            <div class="stat-value"><?= $totalLeads ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-cyan">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-meta">
            <div class="stat-label">Total Contacts</div>
            <div class="stat-value"><?= $totalContacts ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-amber">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
        </div>
        <div class="stat-meta">
            <div class="stat-label">Conversations</div>
            <div class="stat-value"><?= $totalConversations ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-rose">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </div>
        <div class="stat-meta">
            <div class="stat-label">Sent Messages</div>
            <div class="stat-value"><?= $sentEmails ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">

    <!-- Recent Conversations -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Conversations</div>
            <a href="inbox.php" class="btn btn-outline btn-sm">View All Inbox &rarr;</a>
        </div>
        <div class="inbox-list">
            <?php if (empty($recentConversations)): ?>
                <div style="padding: 32px; text-align: center; color: var(--admin-text-muted);">
                    No conversations found yet. Website inquiries and emails will appear here.
                </div>
            <?php else: ?>
                <?php foreach ($recentConversations as $conv): ?>
                    <a href="conversation.php?id=<?= $conv['id'] ?>" class="inbox-row <?= $conv['unread_in_conv'] > 0 ? 'unread' : '' ?>">
                        <div class="inbox-sender">
                            <?= htmlspecialchars($conv['contact_name']) ?>
                        </div>
                        <div class="inbox-content-preview">
                            <span class="inbox-subject"><?= htmlspecialchars($conv['subject']) ?></span>
                            <?php if ($conv['message_count'] > 1): ?>
                                <span style="font-size: 0.75rem; background: #e2e8f0; padding: 1px 6px; border-radius: 99px;"><?= $conv['message_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="status-pill status-<?= htmlspecialchars($conv['status']) ?>">
                            <?= htmlspecialchars($conv['status']) ?>
                        </span>
                        <div class="inbox-date">
                            <?= date('M d', strtotime($conv['last_message_at'])) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Website Leads -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Website Inquiries</div>
            <a href="leads.php" class="btn btn-outline btn-sm">All Leads Pipeline &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentLeads)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-text-muted); padding: 32px;">
                                No website form submissions yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentLeads as $lead): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($lead['contact_name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= htmlspecialchars($lead['contact_email']) ?></div>
                                </td>
                                <td>
                                    <span style="font-size: 0.82rem; font-weight: 500;"><?= htmlspecialchars($lead['service'] ?: 'General') ?></span>
                                </td>
                                <td>
                                    <select class="lead-status-select form-control" data-lead-id="<?= $lead['id'] ?>" style="padding: 4px 8px; font-size: 0.78rem; width: auto;">
                                        <?php foreach (['new', 'reviewed', 'replied', 'follow_up', 'converted', 'closed'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>>
                                                <?= ucfirst(str_replace('_', ' ', $st)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <a href="conversation.php?id=<?= $lead['conversation_id'] ?>" class="btn btn-outline btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
