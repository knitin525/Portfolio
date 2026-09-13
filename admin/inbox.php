<?php
/**
 * Knitin Portfolio — Gmail-Style Admin Inbox
 */

declare(strict_types=1);

$pageTitle = 'Inbox';
$activeNav = 'inbox';

require_once __DIR__ . '/includes/header.php';

$pdo = db();

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereClauses = ["e.deleted_at IS NULL"];
$params = [];

// Apply category filters
switch ($filter) {
    case 'unread':
        $whereClauses[] = "e.is_read = 0 AND e.direction = 'inbound'";
        break;
    case 'read':
        $whereClauses[] = "e.is_read = 1 AND e.direction = 'inbound'";
        break;
    case 'starred':
        $whereClauses[] = "e.is_starred = 1";
        break;
    case 'attachments':
        $whereClauses[] = "EXISTS (SELECT 1 FROM attachments WHERE email_id = e.id)";
        break;
    case 'contact_form':
        $whereClauses[] = "EXISTS (SELECT 1 FROM contact_submissions WHERE email_id = e.id)";
        break;
    case 'direct':
        $whereClauses[] = "NOT EXISTS (SELECT 1 FROM contact_submissions WHERE email_id = e.id)";
        break;
    default:
        // 'all' includes all non-archived inbox emails
        $whereClauses[] = "e.is_archived = 0";
        break;
}

// Search filter
if ($search !== '') {
    $whereClauses[] = "(e.subject LIKE ? OR e.from_email LIKE ? OR e.from_name LIKE ? OR e.message_text LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$whereSql = implode(' AND ', $whereClauses);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM emails e WHERE {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage) ?: 1;

// Fetch emails
$sql = "
    SELECT e.*, c.subject AS conversation_subject,
           (SELECT COUNT(*) FROM attachments WHERE email_id = e.id) AS attachment_count
    FROM emails e
    JOIN conversations c ON e.conversation_id = c.id
    WHERE {$whereSql}
    ORDER BY e.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$emails = $stmt->fetchAll();
?>

<div class="card">
    <!-- Toolbar with Search & Filter Pills -->
    <div class="inbox-toolbar">
        <form method="GET" class="inbox-search-wrap">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <svg class="inbox-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by sender, email, subject, or content...">
        </form>

        <div class="filter-pills">
            <a href="inbox.php?filter=all<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="inbox.php?filter=unread<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'unread' ? 'active' : '' ?>">Unread</a>
            <a href="inbox.php?filter=starred<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'starred' ? 'active' : '' ?>">Starred</a>
            <a href="inbox.php?filter=attachments<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'attachments' ? 'active' : '' ?>">With Attachments</a>
            <a href="inbox.php?filter=contact_form<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'contact_form' ? 'active' : '' ?>">Contact Form</a>
            <a href="inbox.php?filter=direct<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'direct' ? 'active' : '' ?>">Direct Email</a>
        </div>
    </div>

    <!-- Messages List -->
    <div class="inbox-list">
        <?php if (empty($emails)): ?>
            <div style="padding: 60px 20px; text-align: center; color: var(--admin-text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; opacity: 0.5;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 6px;">No messages found</div>
                <div style="font-size: 0.88rem;">There are no emails matching the selected filter criteria.</div>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <?php
                    $isUnread = ($email['is_read'] == 0 && $email['direction'] === 'inbound');
                    $senderDisplay = $email['from_name'] ?: $email['from_email'];
                    $snippet = strip_tags($email['message_text'] ?: $email['message_html'] ?: '');
                    $snippet = mb_strimwidth($snippet, 0, 100, '...');
                ?>
                <div class="inbox-row <?= $isUnread ? 'unread' : '' ?>" onclick="window.location.href='conversation.php?id=<?= $email['conversation_id'] ?>'">
                    <!-- Star Button -->
                    <button type="button" class="star-btn <?= $email['is_starred'] ? 'starred' : '' ?>" data-email-id="<?= $email['id'] ?>" title="<?= $email['is_starred'] ? 'Unstar' : 'Star' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="<?= $email['is_starred'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </button>

                    <!-- Sender Name -->
                    <div class="inbox-sender">
                        <?= htmlspecialchars($senderDisplay) ?>
                    </div>

                    <!-- Subject + Snippet -->
                    <div class="inbox-content-preview">
                        <span class="inbox-subject"><?= htmlspecialchars($email['subject']) ?></span>
                        <span class="inbox-snippet">— <?= htmlspecialchars($snippet) ?></span>
                    </div>

                    <!-- Attachment Indicator -->
                    <?php if ($email['attachment_count'] > 0): ?>
                        <div class="inbox-attachment-icon" title="<?= $email['attachment_count'] ?> attachment(s)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                        </div>
                    <?php endif; ?>

                    <!-- Date -->
                    <div class="inbox-date">
                        <?= date('M d, Y', strtotime($email['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                Showing <?= $offset + 1 ?> to <?= min($totalRows, $offset + $perPage) ?> of <?= $totalRows ?> messages
            </div>
            <div style="display: flex; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="inbox.php?filter=<?= $filter ?>&page=<?= $page - 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="btn btn-outline btn-sm">&larr; Prev</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="inbox.php?filter=<?= $filter ?>&page=<?= $page + 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="btn btn-outline btn-sm">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
