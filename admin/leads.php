<?php
/**
 * Knitin Portfolio — Website Form Leads Pipeline
 */

declare(strict_types=1);

$pageTitle = 'Website Inquiries & Leads';
$activeNav = 'leads';

require_once __DIR__ . '/includes/header.php';

$pdo = db();

$statusFilter = $_GET['status'] ?? 'all';
$whereClause = ($statusFilter !== 'all') ? "WHERE cs.status = ?" : "";
$params = ($statusFilter !== 'all') ? [$statusFilter] : [];

$sql = "
    SELECT cs.*, ct.name AS contact_name, ct.email AS contact_email
    FROM contact_submissions cs
    JOIN contacts ct ON cs.contact_id = ct.id
    {$whereClause}
    ORDER BY cs.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();
?>

<div class="card">
    <div class="inbox-toolbar">
        <div class="card-title">Website Contact Form Pipeline</div>
        <div class="filter-pills">
            <a href="leads.php?status=all" class="filter-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
            <a href="leads.php?status=new" class="filter-pill <?= $statusFilter === 'new' ? 'active' : '' ?>">New</a>
            <a href="leads.php?status=reviewed" class="filter-pill <?= $statusFilter === 'reviewed' ? 'active' : '' ?>">Reviewed</a>
            <a href="leads.php?status=replied" class="filter-pill <?= $statusFilter === 'replied' ? 'active' : '' ?>">Replied</a>
            <a href="leads.php?status=follow_up" class="filter-pill <?= $statusFilter === 'follow_up' ? 'active' : '' ?>">Follow Up</a>
            <a href="leads.php?status=converted" class="filter-pill <?= $statusFilter === 'converted' ? 'active' : '' ?>">Converted</a>
            <a href="leads.php?status=closed" class="filter-pill <?= $statusFilter === 'closed' ? 'active' : '' ?>">Closed</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Lead Name</th>
                    <th>Service &amp; Subject</th>
                    <th>Attachment</th>
                    <th>Status</th>
                    <th>Date Received</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 40px;">
                            No website leads found for this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($lead['contact_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--admin-primary);"><?= htmlspecialchars($lead['contact_email']) ?></div>
                                <?php if ($lead['phone']): ?>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= htmlspecialchars($lead['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.88rem;"><?= htmlspecialchars($lead['service'] ?: 'General Inquiry') ?></div>
                                <div style="font-size: 0.82rem; color: var(--admin-text-muted);"><?= htmlspecialchars($lead['subject']) ?></div>
                            </td>
                            <td>
                                <?php if ($lead['attachment_path']): ?>
                                    <a href="../<?= htmlspecialchars($lead['attachment_path']) ?>" target="_blank" class="attachment-pill" style="padding: 4px 8px; font-size: 0.75rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        <span>File</span>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-size: 0.8rem;">—</span>
                                <?php endif; ?>
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
                            <td style="font-size: 0.82rem; color: var(--admin-text-muted);">
                                <?= date('M d, Y h:i A', strtotime($lead['created_at'])) ?>
                            </td>
                            <td>
                                <a href="conversation.php?id=<?= $lead['conversation_id'] ?>" class="btn btn-outline btn-sm">
                                    View / Reply
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
