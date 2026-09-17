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
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="migrate-projects.php" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;" title="Scan projects.html and import projects">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Import from HTML</span>
        </a>
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
                        <?php
                        $imgUrl = project_image_url($project['hero_image'] ?? null, true);
                        $hasImage = !empty($imgUrl);
                        ?>
                        <tr style="border-bottom: 1px solid var(--admin-border); transition: background 0.15s ease;" onmouseover="this.style.background='var(--admin-border-light)'" onmouseout="this.style.background='transparent'">
                            <!-- Project Title & Subtitle with Interactive Icon -->
                            <td style="padding: 14px 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="project-icon-wrapper" 
                                         id="icon-wrapper-<?= $project['id'] ?>"
                                         onclick="openIconUploadModal(<?= $project['id'] ?>, '<?= htmlspecialchars(addslashes($project['title'])) ?>', '<?= htmlspecialchars(addslashes($imgUrl)) ?>')"
                                         title="Click to upload / change icon"
                                         style="position: relative; width: 44px; height: 44px; border-radius: 8px; flex-shrink: 0; cursor: pointer; overflow: hidden;">
                                        
                                        <?php if ($hasImage): ?>
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                                 alt="<?= htmlspecialchars($project['title']) ?>" 
                                                 id="table-icon-img-<?= $project['id'] ?>"
                                                 style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--admin-border); display: block;"
                                                 onerror="this.style.display='none'; document.getElementById('table-icon-badge-<?= $project['id'] ?>').style.display='flex';">
                                            <div id="table-icon-badge-<?= $project['id'] ?>" 
                                                 style="display: none; width: 44px; height: 44px; border-radius: 8px; background: linear-gradient(135deg, #1e293b, #334155); color: #fff; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--admin-border);">
                                                <?= htmlspecialchars(strtoupper(substr($project['title'], 0, 2))) ?>
                                            </div>
                                        <?php else: ?>
                                            <div id="table-icon-badge-<?= $project['id'] ?>" 
                                                 style="display: flex; width: 44px; height: 44px; border-radius: 8px; background: linear-gradient(135deg, #1e293b, #334155); color: #fff; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--admin-border);">
                                                <?= htmlspecialchars(strtoupper(substr($project['title'], 0, 2))) ?>
                                            </div>
                                            <img src="" 
                                                 alt="" 
                                                 id="table-icon-img-<?= $project['id'] ?>"
                                                 style="display: none; width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--admin-border);">
                                        <?php endif; ?>

                                        <!-- Hover Overlay: Camera / Upload Indicator -->
                                        <div class="project-icon-overlay" 
                                             style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.72); border-radius: 8px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s ease; color: #fff;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                        </div>
                                    </div>

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
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            style="padding: 6px 10px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" 
                                            title="Upload or Change Project Icon"
                                            onclick="openIconUploadModal(<?= $project['id'] ?>, '<?= htmlspecialchars(addslashes($project['title'])) ?>', '<?= htmlspecialchars(addslashes($imgUrl)) ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                        <span>Icon</span>
                                    </button>
                                    <a href="project-edit.php?id=<?= $project['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 6px 10px; font-size: 0.8rem;" title="Edit Project">
                                        Edit
                                    </a>
                                    <a href="/project/<?= htmlspecialchars($project['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 6px 10px; font-size: 0.8rem;" title="Preview on Frontend">
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

<style>
.project-icon-wrapper:hover .project-icon-overlay {
    opacity: 1 !important;
}
.icon-dropzone.dragover {
    border-color: var(--admin-primary) !important;
    background: rgba(37, 99, 235, 0.08) !important;
}
</style>

<!-- Modal: Upload / Change Project Icon -->
<div id="iconUploadModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.15); overflow: hidden;">
        
        <!-- Modal Header -->
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin: 0;">Upload Project Icon</h3>
                <p id="modalProjectTitle" style="font-size: 0.85rem; color: var(--admin-text-muted); margin: 3px 0 0 0;"></p>
            </div>
            <button type="button" onclick="closeIconUploadModal()" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; color: var(--admin-text-muted); padding: 4px;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 22px;">
            <div id="modalAlertBox" style="display: none; padding: 10px 14px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 16px;"></div>

            <!-- Preview Section -->
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; background: var(--admin-border-light); padding: 14px; border-radius: 8px; border: 1px solid var(--admin-border);">
                <div style="width: 64px; height: 64px; border-radius: 10px; overflow: hidden; border: 1px solid var(--admin-border); background: #0f172a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <img id="modalIconPreview" src="" alt="Icon Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="modalIconFallback" style="display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; color: #fff; background: linear-gradient(135deg, #1e293b, #334155);">
                        --
                    </div>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.9rem; font-weight: 600; color: var(--admin-text); margin-bottom: 2px;">Project Icon &amp; Thumbnail</div>
                    <div style="font-size: 0.8rem; color: var(--admin-text-muted);">Shown in projects showcase, table listings, and hero cards. Square ratio recommended (e.g. 512×512px).</div>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div id="iconDropzone" class="icon-dropzone" style="border: 2px dashed var(--admin-border); border-radius: 10px; padding: 24px 16px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: var(--admin-surface); margin-bottom: 16px;" onclick="document.getElementById('modalFileInput').click()">
                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="color: var(--admin-primary); margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">Click to browse or drag &amp; drop icon</div>
                <div style="font-size: 0.78rem; color: var(--admin-text-muted);">Supports PNG, JPG, WEBP, SVG, GIF (Max 10MB)</div>
                <input type="file" id="modalFileInput" accept="image/*" style="display: none;" onchange="handleModalFileSelect(this)">
            </div>

            <div id="selectedFileInfo" style="display: none; align-items: center; justify-content: space-between; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.25); padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; color: var(--admin-primary); margin-bottom: 16px;">
                <span id="selectedFileName" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px;"></span>
                <button type="button" onclick="clearSelectedFile()" style="background: none; border: none; color: var(--admin-danger); cursor: pointer; font-size: 1.1rem; line-height: 1;">&times;</button>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 22px; border-top: 1px solid var(--admin-border); background: var(--admin-border-light); display: flex; justify-content: space-between; align-items: center;">
            <button type="button" id="btnRemoveIcon" onclick="removeProjectIcon()" class="btn btn-sm" style="display: none; background: transparent; color: var(--admin-danger); border: 1px solid rgba(239, 68, 68, 0.3);">
                Remove Current Icon
            </button>
            <div style="display: flex; gap: 8px; margin-left: auto;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeIconUploadModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitUpload" onclick="uploadProjectIcon()" disabled style="display: inline-flex; align-items: center; gap: 6px;">
                    <span id="btnUploadText">Save Icon</span>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
let currentModalProjectId = 0;
let currentModalProjectTitle = '';
let currentModalOriginalImage = '';
let selectedModalFile = null;

function openIconUploadModal(projectId, title, currentImgUrl) {
    currentModalProjectId = projectId;
    currentModalProjectTitle = title;
    currentModalOriginalImage = currentImgUrl || '';
    selectedModalFile = null;

    document.getElementById('modalProjectTitle').textContent = title;
    const alertBox = document.getElementById('modalAlertBox');
    alertBox.style.display = 'none';
    alertBox.textContent = '';

    const preview = document.getElementById('modalIconPreview');
    const fallback = document.getElementById('modalIconFallback');
    const initials = title.trim().substring(0, 2).toUpperCase() || 'PR';
    fallback.textContent = initials;

    if (currentModalOriginalImage) {
        preview.src = currentModalOriginalImage;
        preview.style.display = 'block';
        fallback.style.display = 'none';
        preview.onerror = function() {
            preview.style.display = 'none';
            fallback.style.display = 'flex';
        };
        document.getElementById('btnRemoveIcon').style.display = 'inline-block';
    } else {
        preview.style.display = 'none';
        fallback.style.display = 'flex';
        document.getElementById('btnRemoveIcon').style.display = 'none';
    }

    document.getElementById('modalFileInput').value = '';
    document.getElementById('selectedFileInfo').style.display = 'none';
    document.getElementById('btnSubmitUpload').disabled = true;

    const modal = document.getElementById('iconUploadModal');
    modal.style.display = 'flex';
}

function closeIconUploadModal() {
    document.getElementById('iconUploadModal').style.display = 'none';
}

function handleModalFileSelect(input) {
    if (input.files && input.files[0]) {
        setModalFile(input.files[0]);
    }
}

function setModalFile(file) {
    selectedModalFile = file;
    document.getElementById('selectedFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('selectedFileInfo').style.display = 'flex';
    document.getElementById('btnSubmitUpload').disabled = false;

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('modalIconPreview');
        const fallback = document.getElementById('modalIconFallback');
        preview.src = e.target.result;
        preview.style.display = 'block';
        fallback.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function clearSelectedFile() {
    selectedModalFile = null;
    document.getElementById('modalFileInput').value = '';
    document.getElementById('selectedFileInfo').style.display = 'none';
    document.getElementById('btnSubmitUpload').disabled = true;

    const preview = document.getElementById('modalIconPreview');
    const fallback = document.getElementById('modalIconFallback');
    if (currentModalOriginalImage) {
        preview.src = currentModalOriginalImage;
        preview.style.display = 'block';
        fallback.style.display = 'none';
    } else {
        preview.style.display = 'none';
        fallback.style.display = 'flex';
    }
}

// Drag & drop handlers
document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('iconDropzone');
    if (!dropzone) return;

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            setModalFile(files[0]);
        }
    }, false);

    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeIconUploadModal();
        }
    });

    // Close when clicking modal backdrop
    const modal = document.getElementById('iconUploadModal');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeIconUploadModal();
        }
    });
});

async function uploadProjectIcon() {
    if (!selectedModalFile || !currentModalProjectId) return;

    const btn = document.getElementById('btnSubmitUpload');
    const btnText = document.getElementById('btnUploadText');
    const alertBox = document.getElementById('modalAlertBox');

    btn.disabled = true;
    btnText.textContent = 'Uploading...';
    alertBox.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'upload_project_icon');
    formData.append('project_id', currentModalProjectId);
    formData.append('icon_file', selectedModalFile);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    try {
        const response = await fetch('ajax.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(16, 185, 129, 0.12)';
            alertBox.style.border = '1px solid var(--admin-success)';
            alertBox.style.color = '#065f46';
            alertBox.textContent = result.message || 'Icon uploaded successfully!';

            // Live update the table row without page refresh
            const tableImg = document.getElementById('table-icon-img-' + currentModalProjectId);
            const tableBadge = document.getElementById('table-icon-badge-' + currentModalProjectId);
            if (tableImg) {
                tableImg.src = result.display_url + '?t=' + Date.now();
                tableImg.style.display = 'block';
                if (tableBadge) tableBadge.style.display = 'none';
            }

            setTimeout(() => {
                closeIconUploadModal();
            }, 1200);
        } else {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
            alertBox.style.border = '1px solid var(--admin-danger)';
            alertBox.style.color = '#991b1b';
            alertBox.textContent = result.message || 'Failed to upload icon.';
            btn.disabled = false;
            btnText.textContent = 'Save Icon';
        }
    } catch (err) {
        alertBox.style.display = 'block';
        alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
        alertBox.style.border = '1px solid var(--admin-danger)';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = 'Network or server error occurred during upload.';
        btn.disabled = false;
        btnText.textContent = 'Save Icon';
    }
}

async function removeProjectIcon() {
    if (!currentModalProjectId || !confirm('Are you sure you want to remove the icon for this project?')) return;

    const alertBox = document.getElementById('modalAlertBox');
    const formData = new FormData();
    formData.append('action', 'remove_project_icon');
    formData.append('project_id', currentModalProjectId);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    try {
        const response = await fetch('ajax.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            // Live update table row
            const tableImg = document.getElementById('table-icon-img-' + currentModalProjectId);
            const tableBadge = document.getElementById('table-icon-badge-' + currentModalProjectId);
            if (tableImg) {
                tableImg.style.display = 'none';
                tableImg.src = '';
            }
            if (tableBadge) {
                tableBadge.style.display = 'flex';
            }
            closeIconUploadModal();
        } else {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
            alertBox.style.border = '1px solid var(--admin-danger)';
            alertBox.style.color = '#991b1b';
            alertBox.textContent = result.message || 'Failed to remove icon.';
        }
    } catch (err) {
        alertBox.style.display = 'block';
        alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
        alertBox.style.border = '1px solid var(--admin-danger)';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = 'Network error occurred.';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
