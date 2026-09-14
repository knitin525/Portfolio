<?php
/**
 * Knitin Portfolio — Projects Management
 * List all projects with Primary Category, compact Additional Category badges, and actions.
 */

declare(strict_types=1);

$pageTitle = 'Projects Showcase';
$activeNav = 'projects';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/ProjectService.php';

$projectService = new ProjectService();

// Handle Delete Request
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please try again.';
    } else {
        $deleteId = (int)($_POST['project_id'] ?? 0);
        try {
            if ($projectService->deleteProject($deleteId)) {
                $message = 'Project deleted successfully.';
            } else {
                $error = 'Project could not be deleted.';
            }
        } catch (Throwable $e) {
            $error = 'Error deleting project: ' . $e->getMessage();
        }
    }
}

// Filters
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selectedStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$filterParams = [];
if ($selectedCategory > 0) $filterParams['category_id'] = $selectedCategory;
if (!empty($selectedStatus)) $filterParams['status'] = $selectedStatus;
if (!empty($search)) $filterParams['search'] = $search;

$projects = $projectService->getProjects($filterParams);
$categories = $projectService->getCategories();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--admin-text); margin-bottom: 4px;">Portfolio Projects</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">
            Manage projects with exact <strong>One Primary Category</strong> and <strong>Multiple Additional Categories</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="categories.php" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            <span>Manage Categories</span>
        </a>
        <a href="project-edit.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Add New Project</span>
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--admin-success); color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--admin-danger); color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Filter & Search Toolbar -->
<div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px;">
    <form method="GET" action="projects.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search projects by title, industry, tag..." class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
        </div>

        <div style="min-width: 180px;">
            <select name="category" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $selectedCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 140px;">
            <select name="status" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
                <option value="">All Statuses</option>
                <option value="published" <?= $selectedStatus === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $selectedStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="archived" <?= $selectedStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">Filter</button>
        <?php if (!empty($search) || $selectedCategory > 0 || !empty($selectedStatus)): ?>
            <a href="projects.php" class="btn btn-secondary btn-sm" style="padding: 8px 12px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Projects Table -->
<div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 600; font-size: 0.95rem; color: var(--admin-text);">
            Showing <?= count($projects) ?> Project<?= count($projects) === 1 ? '' : 's' ?>
        </span>
    </div>

    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: var(--admin-border-light); border-bottom: 1px solid var(--admin-border); color: var(--admin-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <th style="padding: 12px 16px;">Project</th>
                    <th style="padding: 12px 16px;">Primary Category</th>
                    <th style="padding: 12px 16px;">Additional Categories</th>
                    <th style="padding: 12px 16px; text-align: center;">Status</th>
                    <th style="padding: 12px 16px; text-align: center;">Featured</th>
                    <th style="padding: 12px 16px;">Updated Date</th>
                    <th style="padding: 12px 16px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" style="padding: 40px 20px; text-align: center; color: var(--admin-text-muted);">
                            <div style="margin-bottom: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.4;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <div style="font-weight: 600; color: var(--admin-text);">No projects found</div>
                            <p style="font-size: 0.85rem; margin-top: 4px;">Try modifying your search or click "Add New Project".</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <tr style="border-bottom: 1px solid var(--admin-border); transition: background 0.15s ease;" onmouseover="this.style.background='var(--admin-border-light)'" onmouseout="this.style.background='transparent'">
                            <!-- Project Title & Subtitle -->
                            <td style="padding: 14px 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($project['hero_image'])): ?>
                                        <img src="<?= htmlspecialchars($project['hero_image']) ?>" alt="" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--admin-border);">
                                    <?php else: ?>
                                        <div style="width: 44px; height: 44px; border-radius: 8px; background: linear-gradient(135deg, #1e293b, #334155); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                            <?= htmlspecialchars(strtoupper(substr($project['title'], 0, 2))) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="project-edit.php?id=<?= $project['id'] ?>" style="font-weight: 600; color: var(--admin-text); text-decoration: none;">
                                            <?= htmlspecialchars($project['title']) ?>
                                        </a>
                                        <div style="font-size: 0.8rem; color: var(--admin-text-muted); margin-top: 2px;">
                                            <?= htmlspecialchars($project['industry'] ?? $project['summary'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Primary Category (Prominent Badge) -->
                            <td style="padding: 14px 16px;">
                                <span class="badge-primary-cat" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(37, 99, 235, 0.12); color: var(--admin-primary); border: 1px solid rgba(37, 99, 235, 0.25);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <?= htmlspecialchars($project['primary_category_name']) ?>
                                </span>
                            </td>

                            <!-- Additional Categories (Compact Badges) -->
                            <td style="padding: 14px 16px;">
                                <?php if (!empty($project['additional_categories'])): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 280px;">
                                        <?php foreach ($project['additional_categories'] as $addCat): ?>
                                            <span class="badge-additional-cat" style="display: inline-block; padding: 2px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 500; background: var(--admin-border-light); color: var(--admin-text-muted); border: 1px solid var(--admin-border);">
                                                <?= htmlspecialchars($addCat['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-subtle); font-size: 0.8rem; font-style: italic;">None</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td style="padding: 14px 16px; text-align: center;">
                                <?php
                                $statusBadgeStyles = [
                                    'published' => 'background: rgba(16, 185, 129, 0.12); color: #059669;',
                                    'draft' => 'background: rgba(245, 158, 11, 0.12); color: #d97706;',
                                    'archived' => 'background: rgba(100, 116, 139, 0.12); color: #475569;',
                                ];
                                $style = $statusBadgeStyles[$project['status']] ?? 'background: #f1f5f9; color: #64748b;';
                                ?>
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; <?= $style ?>">
                                    <?= htmlspecialchars($project['status']) ?>
                                </span>
                            </td>

                            <!-- Featured -->
                            <td style="padding: 14px 16px; text-align: center;">
                                <?php if ((int)$project['is_featured'] === 1): ?>
                                    <span title="Featured Highlight" style="color: #f59e0b; display: inline-flex; align-items: center; justify-content: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-subtle); font-size: 0.8rem;">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Updated Date -->
                            <td style="padding: 14px 16px; color: var(--admin-text-muted); font-size: 0.85rem; white-space: nowrap;">
                                <?= date('M j, Y', strtotime($project['updated_at'] ?? $project['created_at'])) ?>
                            </td>

                            <!-- Actions -->
                            <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="project-edit.php?id=<?= $project['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 6px 10px; font-size: 0.8rem;" title="Edit Project">
                                        Edit
                                    </a>
                                    <a href="../projects.html" target="_blank" class="btn btn-secondary btn-sm" style="padding: 6px 10px; font-size: 0.8rem;" title="Preview on Frontend">
                                        View
                                    </a>
                                    <form method="POST" action="projects.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete \'<?= htmlspecialchars(addslashes($project['title'])) ?>\'?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="background: transparent; color: var(--admin-danger); border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 8px;" title="Delete Project">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
