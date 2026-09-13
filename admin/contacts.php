<?php
/**
 * Knitin Portfolio — CRM Contacts Directory
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

Auth::requireAuth();

$pdo = db();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=knitin_contacts_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Status', 'Notes', 'Created At']);

    $exportStmt = $pdo->query("SELECT id, name, email, phone, company, status, notes, created_at FROM contacts ORDER BY created_at DESC");
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Contacts CRM';
$activeNav = 'contacts';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if ($statusFilter !== 'all') {
    $whereClauses[] = "c.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $whereClauses[] = "(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.phone LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT c.*,
           (SELECT COUNT(*) FROM emails WHERE contact_id = c.id AND deleted_at IS NULL) AS total_messages,
           (SELECT id FROM conversations WHERE contact_id = c.id ORDER BY last_message_at DESC LIMIT 1) AS latest_conv_id
    FROM contacts c
    WHERE {$whereSql}
    ORDER BY c.updated_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="inbox-toolbar">
        <form method="GET" class="inbox-search-wrap">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <svg class="inbox-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search contacts by name, email, company...">
        </form>

        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="filter-pills">
                <a href="contacts.php?status=all<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
                <a href="contacts.php?status=new_lead<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $statusFilter === 'new_lead' ? 'active' : '' ?>">New Leads</a>
                <a href="contacts.php?status=contacted<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $statusFilter === 'contacted' ? 'active' : '' ?>">Contacted</a>
                <a href="contacts.php?status=in_discussion<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $statusFilter === 'in_discussion' ? 'active' : '' ?>">In Discussion</a>
                <a href="contacts.php?status=client<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $statusFilter === 'client' ? 'active' : '' ?>">Clients</a>
            </div>

            <a href="contacts.php?export=csv" class="btn btn-outline btn-sm" title="Export Contacts as CSV">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Company / Phone</th>
                    <th>Status</th>
                    <th>Messages</th>
                    <th>First Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 40px;">
                            No contacts matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($contact['name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--admin-primary);"><?= htmlspecialchars($contact['email']) ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($contact['company'] ?: '—') ?></div>
                                <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= htmlspecialchars($contact['phone'] ?: '—') ?></div>
                            </td>
                            <td>
                                <select class="form-control" style="padding: 4px 8px; font-size: 0.8rem; width: auto;" onchange="updateContactStatus(<?= $contact['id'] ?>, this.value)">
                                    <?php foreach (['new_lead', 'contacted', 'in_discussion', 'client', 'completed', 'archived'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $contact['status'] === $st ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $st)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <span class="badge-count" style="background: #e2e8f0; color: #334155;"><?= $contact['total_messages'] ?></span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--admin-text-muted);">
                                <?= date('M d, Y', strtotime($contact['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($contact['latest_conv_id']): ?>
                                    <a href="conversation.php?id=<?= $contact['latest_conv_id'] ?>" class="btn btn-outline btn-sm">
                                        View Thread
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-size: 0.8rem;">No Thread</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function updateContactStatus(contactId, status) {
    try {
        const response = await fetch('ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'update_contact_status',
                contact_id: contactId,
                status: status,
                csrf_token: getCsrfToken()
            })
        });
        const data = await response.json();
        if (data.success) {
            showToast('Contact status updated', 'success');
        } else {
            showToast(data.message || 'Error updating status', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
