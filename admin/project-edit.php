<?php
/**
 * Knitin Portfolio — Add / Edit Project
 * Implements Primary Category Dropdown + Searchable Multi-Select Additional Categories UX.
 */

declare(strict_types=1);

$pageTitle = 'Add Project';
$activeNav = 'projects';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/ProjectService.php';

$projectService = new ProjectService();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$project = null;

if ($isEdit) {
    $project = $projectService->getProjectById($id);
    if (!$project) {
        redirect('projects.php');
    }
    $pageTitle = 'Edit Project: ' . $project['title'];
}

$categories = $projectService->getCategories(true); // Active categories only
$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please submit the form again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $primaryCatId = (int)($_POST['primary_category_id'] ?? 0);
        $additionalCatIds = $_POST['additional_category_ids'] ?? [];

        if (empty($title)) {
            $error = 'Project title is required.';
        } elseif ($primaryCatId <= 0) {
            $error = 'Primary Category is required. Please select exactly one Primary Category.';
        } else {
            // Handle image upload if provided
            $heroImage = trim($_POST['hero_image'] ?? ($project['hero_image'] ?? ''));
            if (!empty($_FILES['hero_image_file']['name']) && $_FILES['hero_image_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__) . '/uploads/projects';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['hero_image_file']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
                if (in_array($ext, $allowed, true)) {
                    $fileName = 'proj_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $fileName;
                    if (move_uploaded_file($_FILES['hero_image_file']['tmp_name'], $targetPath)) {
                        $heroImage = 'uploads/projects/' . $fileName;
                    }
                }
            }

            $projectData = [
                'title' => $title,
                'slug' => trim($_POST['slug'] ?? ''),
                'primary_category_id' => $primaryCatId,
                'additional_category_ids' => $additionalCatIds,
                'client_name' => trim($_POST['client_name'] ?? ''),
                'industry' => trim($_POST['industry'] ?? ''),
                'summary' => trim($_POST['summary'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'hero_image' => $heroImage,
                'tags' => trim($_POST['tags'] ?? ''),
                'project_type' => trim($_POST['project_type'] ?? ''),
                'project_url' => trim($_POST['project_url'] ?? ''),
                'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
                'status' => $_POST['status'] ?? 'published',
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];

            try {
                $savedId = $projectService->saveProject($projectData, $isEdit ? $id : null);
                if (!$isEdit) {
                    redirect("project-edit.php?id={$savedId}&created=1");
                } else {
                    $success = 'Project updated successfully!';
                    $project = $projectService->getProjectById($id);
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

if (isset($_GET['created'])) {
    $success = 'Project created successfully!';
}

// Prepare current values
$currentPrimary = (int)($project['primary_category_id'] ?? 0);
$currentAdditionals = $project['additional_category_ids'] ?? [];
?>

<div style="max-width: 1040px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <a href="projects.php" style="display: inline-flex; align-items: center; gap: 6px; color: var(--admin-text-muted); font-size: 0.88rem; margin-bottom: 6px; text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Projects
            </a>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--admin-text);"><?= $isEdit ? 'Edit Project' : 'Add New Project' ?></h2>
        </div>

        <?php if ($isEdit): ?>
            <a href="../projects.html" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                View Frontend Showcase
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--admin-success); color: #065f46; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; font-weight: 500;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--admin-danger); color: #991b1b; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; font-weight: 500;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="project-edit.php<?= $isEdit ? '?id=' . $id : '' ?>" enctype="multipart/form-data" id="projectForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
            <!-- Left Column: Main Information & Category Architecture -->
            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Card: Basic Project Information -->
                <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 24px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 20px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                        Project Overview
                    </h3>

                    <div style="margin-bottom: 16px;">
                        <label for="projectTitle" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                            Project Title <span style="color: var(--admin-danger);">*</span>
                        </label>
                        <input type="text" id="projectTitle" name="title" value="<?= htmlspecialchars($project['title'] ?? '') ?>" required placeholder="e.g. Dermovent-FP Visual Aid, Mind Intelligence Lab..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label for="projectSlug" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                                URL Slug (auto-generated if empty)
                            </label>
                            <input type="text" id="projectSlug" name="slug" value="<?= htmlspecialchars($project['slug'] ?? '') ?>" placeholder="e.g. dermovent-fp" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label for="projectClient" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                                Client / Company Name
                            </label>
                            <input type="text" id="projectClient" name="client_name" value="<?= htmlspecialchars($project['client_name'] ?? '') ?>" placeholder="e.g. DRT Lifesciences, Univentis..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label for="projectIndustry" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                                Subtitle / Industry / Formulation
                            </label>
                            <input type="text" id="projectIndustry" name="industry" value="<?= htmlspecialchars($project['industry'] ?? '') ?>" placeholder="e.g. Griseofulvin 250mg & Cetirizine HCl 5mg" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label for="projectType" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                                Project Type / Discipline Focus
                            </label>
                            <input type="text" id="projectType" name="project_type" value="<?= htmlspecialchars($project['project_type'] ?? '') ?>" placeholder="e.g. Medical Visual Aid, Web Application..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="projectSummary" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                            Short Card Excerpt / Summary
                        </label>
                        <input type="text" id="projectSummary" name="summary" value="<?= htmlspecialchars($project['summary'] ?? '') ?>" placeholder="Brief 1-sentence highlight for cards..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div>
                        <label for="projectDesc" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                            Full Project Description (Modal & Detail View)
                        </label>
                        <textarea id="projectDesc" name="description" rows="4" placeholder="Detailed explanation of the project context, design strategy and execution..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Card: Category System Architecture (PRIMARY + ADDITIONAL) -->
                <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 2px;">
                                Primary + Additional Category System
                            </h3>
                            <p style="color: var(--admin-text-muted); font-size: 0.82rem;">
                                Exactly <strong>One Primary Category</strong> defines core identity; <strong>Multiple Additional Categories</strong> power cross-filtering and discovery.
                            </p>
                        </div>
                        <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; background: rgba(37, 99, 235, 0.1); color: var(--admin-primary); padding: 4px 8px; border-radius: 4px;">
                            Rule Enforced
                        </span>
                    </div>

                    <!-- 1. PRIMARY CATEGORY DROPDOWN -->
                    <div style="margin-bottom: 24px;">
                        <label for="primaryCategory" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; font-weight: 700; color: var(--admin-text); margin-bottom: 6px;">
                            <span>Primary Category <span style="color: var(--admin-danger);">*</span></span>
                            <span style="font-weight: normal; font-size: 0.8rem; color: var(--admin-text-muted);">Main label, breadcrumb &amp; primary filter</span>
                        </label>
                        <select id="primaryCategory" name="primary_category_id" required class="form-control" style="width: 100%; padding: 11px 14px; border: 2px solid var(--admin-primary); border-radius: 8px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); background-color: var(--admin-surface);">
                            <option value="">— Select Category ▼ —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $currentPrimary === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size: 0.8rem; color: var(--admin-text-muted); margin-top: 5px;">
                            Selecting a Primary Category automatically excludes it from Additional Categories below.
                        </div>
                    </div>

                    <!-- 2. ADDITIONAL CATEGORIES SEARCHABLE MULTI-SELECT -->
                    <div>
                        <label style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; font-weight: 700; color: var(--admin-text); margin-bottom: 6px;">
                            <span>Additional Categories</span>
                            <span style="font-weight: normal; font-size: 0.8rem; color: var(--admin-text-muted);">
                                Selected: <strong id="additionalCountBadge">0</strong>
                            </span>
                        </label>

                        <!-- Selected Categories Pills Container -->
                        <div id="selectedPillsContainer" style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; min-height: 32px; padding: 6px; background: var(--admin-border-light); border-radius: 8px; border: 1px dashed var(--admin-border);">
                            <span id="noAdditionalPlaceholder" style="color: var(--admin-text-subtle); font-size: 0.8rem; padding: 4px 6px; font-style: italic;">
                                No additional categories selected yet. Check boxes below to add.
                            </span>
                        </div>

                        <!-- Live Search Filter for Categories -->
                        <div style="position: relative; margin-bottom: 12px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--admin-text-muted);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="categorySearchInput" placeholder="Search categories..." class="form-control" style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
                        </div>

                        <!-- Scrollable Checkbox List -->
                        <div id="additionalCategoryCheckboxList" style="max-height: 240px; overflow-y: auto; border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px; background: var(--admin-surface); display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $catId = (int)$cat['id'];
                                $isAlreadyChecked = in_array($catId, $currentAdditionals, true) && ($catId !== $currentPrimary);
                                ?>
                                <label class="category-checkbox-item" data-cat-id="<?= $catId ?>" data-cat-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>" style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: background 0.15s ease; font-size: 0.85rem; user-select: none;">
                                    <input type="checkbox" name="additional_category_ids[]" value="<?= $catId ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" <?= $isAlreadyChecked ? 'checked' : '' ?> style="cursor: pointer;">
                                    <span class="category-label-text"><?= htmlspecialchars($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Card: Tags & Meta -->
                <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 24px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 20px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                        Tags &amp; Relevant Skills
                    </h3>

                    <div style="margin-bottom: 16px;">
                        <label for="projectTags" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                            Tags (comma-separated, max 2–3 will display on frontend card)
                        </label>
                        <input type="text" id="projectTags" name="tags" value="<?= htmlspecialchars($project['tags'] ?? '') ?>" placeholder="e.g. Visual Aid, Graphic Design, Medical" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div>
                        <label for="projectUrl" style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">
                            Live Project / Case Study External Link
                        </label>
                        <input type="url" id="projectUrl" name="project_url" value="<?= htmlspecialchars($project['project_url'] ?? '') ?>" placeholder="https://..." class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.9rem;">
                    </div>
                </div>

            </div>

            <!-- Right Column: Publishing, Media & Settings -->
            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Publishing Card -->
                <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 20px;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--admin-text); margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                        Publishing Status
                    </h4>

                    <div style="margin-bottom: 16px;">
                        <label for="projectStatus" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">Status</label>
                        <select id="projectStatus" name="status" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
                            <option value="published" <?= ($project['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= ($project['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= ($project['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="sortOrder" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 6px;">Sort Order</label>
                        <input type="number" id="sortOrder" name="sort_order" value="<?= htmlspecialchars((string)($project['sort_order'] ?? 0)) ?>" class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--admin-text);">
                            <input type="checkbox" name="is_featured" value="1" <?= !empty($project['is_featured']) ? 'checked' : '' ?> style="cursor: pointer;">
                            <span style="font-weight: 600;">Featured Highlight</span>
                        </label>
                        <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-left: 24px; margin-top: 2px;">
                            Shows in the top Featured Spotlights section on the showcase page.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; font-size: 0.95rem; border-radius: 8px;">
                        <?= $isEdit ? 'Save Changes' : 'Publish Project' ?>
                    </button>
                </div>

                <!-- Hero Image Card -->
                <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 20px;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--admin-text); margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                        Project Hero Image
                    </h4>

                    <div style="margin-bottom: 14px;">
                        <div id="imagePreviewContainer" style="width: 100%; aspect-ratio: 16/10; border-radius: 8px; border: 1px solid var(--admin-border); overflow: hidden; background: #0f172a; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <?php if (!empty($project['hero_image'])): ?>
                                <img id="imagePreview" src="../<?= htmlspecialchars($project['hero_image']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div id="imagePlaceholder" style="text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 16px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 6px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <div>No image uploaded</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">Upload Image File</label>
                        <input type="file" name="hero_image_file" accept="image/*" class="form-control" style="width: 100%; font-size: 0.85rem; margin-bottom: 10px;">

                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">Or Image URL / Path</label>
                        <input type="text" name="hero_image" value="<?= htmlspecialchars($project['hero_image'] ?? '') ?>" placeholder="img/work/..." class="form-control" style="width: 100%; padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- Dynamic Category Architecture Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const primarySelect = document.getElementById('primaryCategory');
    const searchInput = document.getElementById('categorySearchInput');
    const checkboxList = document.getElementById('additionalCategoryCheckboxList');
    const pillsContainer = document.getElementById('selectedPillsContainer');
    const placeholder = document.getElementById('noAdditionalPlaceholder');
    const countBadge = document.getElementById('additionalCountBadge');

    /**
     * Refresh Additional Categories display & exclude Primary Category
     */
    function syncCategoryState() {
        const selectedPrimaryId = primarySelect.value;
        const items = checkboxList.querySelectorAll('.category-checkbox-item');

        items.forEach(item => {
            const catId = item.dataset.catId;
            const checkbox = item.querySelector('input[type="checkbox"]');

            if (catId === selectedPrimaryId) {
                // Rule: If category is selected as Primary, automatically remove it from Additionals
                checkbox.checked = false;
                checkbox.disabled = true;
                item.style.opacity = '0.4';
                item.style.pointerEvents = 'none';
                item.title = 'Assigned as Primary Category';
            } else {
                checkbox.disabled = false;
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
                item.title = '';
            }
        });

        renderSelectedPills();
    }

    /**
     * Render tag pills for selected additional categories
     */
    function renderSelectedPills() {
        const checkedBoxes = checkboxList.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
        
        // Clear pills except placeholder
        pillsContainer.innerHTML = '';

        if (checkedBoxes.length === 0) {
            pillsContainer.appendChild(placeholder);
            placeholder.style.display = 'block';
            countBadge.textContent = '0';
            return;
        }

        countBadge.textContent = checkedBoxes.length;

        checkedBoxes.forEach(cb => {
            const pill = document.createElement('span');
            pill.style.display = 'inline-flex';
            pill.style.alignItems = 'center';
            pill.style.gap = '6px';
            pill.style.padding = '4px 10px';
            pill.style.background = 'var(--admin-surface)';
            pill.style.border = '1px solid var(--admin-border)';
            pill.style.borderRadius = '50px';
            pill.style.fontSize = '0.8rem';
            pill.style.fontWeight = '500';
            pill.style.color = 'var(--admin-text)';
            pill.style.boxShadow = 'var(--shadow-sm)';

            pill.innerHTML = `
                <span>${cb.dataset.name}</span>
                <button type="button" aria-label="Remove category" style="background: none; border: none; cursor: pointer; color: var(--admin-danger); font-size: 1rem; line-height: 1; padding: 0 2px;">&times;</button>
            `;

            pill.querySelector('button').addEventListener('click', () => {
                cb.checked = false;
                renderSelectedPills();
            });

            pillsContainer.appendChild(pill);
        });
    }

    // Event: Primary Category Change
    primarySelect.addEventListener('change', () => {
        syncCategoryState();
    });

    // Event: Additional Category Checkbox Change
    checkboxList.addEventListener('change', (e) => {
        if (e.target.matches('input[type="checkbox"]')) {
            renderSelectedPills();
        }
    });

    // Event: Live Search Categories
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.trim().toLowerCase();
            const items = checkboxList.querySelectorAll('.category-checkbox-item');

            items.forEach(item => {
                const name = item.dataset.catName || '';
                if (!term || name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Initial state sync
    syncCategoryState();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
