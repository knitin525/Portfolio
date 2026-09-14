<?php
/**
 * Knitin Portfolio — Category Management
 * List, create, and edit categories; displays Primary & Additional project usage counts.
 */

declare(strict_types=1);

$pageTitle = 'Category Management';
$activeNav = 'categories';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/ProjectService.php';

$projectService = new ProjectService();

$message = '';
$error = '';

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $catId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'display_order' => (int)($_POST['display_order'] ?? 0),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            ];

            try {
                $savedId = $projectService->saveCategory($data, $catId);
                $message = $catId ? 'Category updated successfully!' : 'Category created successfully!';
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['category_id'] ?? 0);
            try {
                if ($projectService->deleteCategory($catId)) {
                    $message = 'Category deleted successfully.';
                } else {
                    $error = 'Category could not be deleted.';
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$categories = $projectService->getCategories(false);
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCategory = $projectService->getCategoryById($editId);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--admin-text); margin-bottom: 4px;">Project Categories</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">
            Define taxonomy for project Primary identities and Additional cross-category discovery.
        </p>
    </div>
    <a href="projects.php" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        <span>Back to Projects</span>
    </a>
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

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    <!-- Add / Edit Category Form -->
    <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
        </h3>

        <form method="POST" action="categories.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($editCategory): ?>
                <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">
                    Category Name <span style="color: var(--admin-danger);">*</span>
                </label>
                <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" required placeholder="e.g. Pharma Design, Web & UI/UX..." class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">
                    Slug (URL Key)
                </label>
                <input type="text" name="slug" value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>" placeholder="e.g. pharma-design" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">
                    Description
                </label>
                <textarea name="description" rows="3" placeholder="Category purpose and scope..." class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem; resize: vertical;"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">Display Order</label>
                    <input type="number" name="display_order" value="<?= htmlspecialchars((string)($editCategory['display_order'] ?? 0)) ?>" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 8px;">Active Status</label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; margin-top: 6px;">
                        <input type="checkbox" name="is_active" value="1" <?= (!isset($editCategory) || !empty($editCategory['is_active'])) ? 'checked' : '' ?>>
                        <span>Active</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px; font-weight: 600;">
                    <?= $editCategory ? 'Update Category' : 'Create Category' ?>
                </button>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-secondary" style="padding: 10px 14px;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories List Table -->
    <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; font-size: 0.95rem; color: var(--admin-text);">
                All Categories (<?= count($categories) ?>)
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead>
                    <tr style="background: var(--admin-border-light); border-bottom: 1px solid var(--admin-border); color: var(--admin-text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 12px 16px;">Category</th>
                        <th style="padding: 12px 16px;">Slug</th>
                        <th style="padding: 12px 16px; text-align: center;">Primary In</th>
                        <th style="padding: 12px 16px; text-align: center;">Additional In</th>
                        <th style="padding: 12px 16px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr style="border-bottom: 1px solid var(--admin-border); transition: background 0.15s ease;" onmouseover="this.style.background='var(--admin-border-light)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px 16px; font-weight: 600; color: var(--admin-text);">
                                <?= htmlspecialchars($c['name']) ?>
                                <?php if (!empty($c['description'])): ?>
                                    <div style="font-weight: normal; font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;">
                                        <?= htmlspecialchars($c['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; color: var(--admin-text-muted); font-family: monospace; font-size: 0.82rem;">
                                <?= htmlspecialchars($c['slug']) ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 50px; font-weight: 700; font-size: 0.78rem; background: rgba(37, 99, 235, 0.1); color: var(--admin-primary);">
                                    <?= (int)$c['primary_project_count'] ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 50px; font-weight: 600; font-size: 0.78rem; background: var(--admin-border-light); color: var(--admin-text-muted);">
                                    <?= (int)$c['additional_project_count'] ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <?php if ((int)$c['is_active'] === 1): ?>
                                    <span style="color: var(--admin-success); font-weight: 600; font-size: 0.8rem;">Active</span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-subtle); font-size: 0.8rem;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="categories.php?edit=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.78rem;">Edit</a>
                                    <?php if ((int)$c['primary_project_count'] === 0): ?>
                                        <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Delete category \'<?= htmlspecialchars(addslashes($c['name'])) ?>\'?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-sm" style="background: none; border: none; color: var(--admin-danger); cursor: pointer; padding: 4px 6px; font-size: 0.78rem;">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span title="Cannot delete: Assigned as Primary Category" style="color: var(--admin-text-subtle); font-size: 0.78rem; padding: 4px 6px; cursor: not-allowed;">In use</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
