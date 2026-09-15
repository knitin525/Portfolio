<?php
/**
 * Knitin Portfolio — Project Migration Tool
 * Imports static projects from projects.html into MySQL database.
 * Safe, idempotent, preserves existing data without duplicates.
 */

declare(strict_types=1);

$pageTitle = 'Migrate Projects from HTML';
$activeNav = 'projects';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/ProjectService.php';

$projectService = new ProjectService();
$pdo = db();

$htmlFile = dirname(__DIR__) . '/projects.html';
$parsedProjects = [];
$parseError = '';

if (!file_exists($htmlFile)) {
    $parseError = 'projects.html not found in project root directory.';
} else {
    $html = file_get_contents($htmlFile);

    // Parse all <article class="project-card ..."> elements
    preg_match_all('/<article\s+class="([^"]*project-card[^"]*)"([^>]*)>(.*?)<\/article>/is', $html, $matches, PREG_SET_ORDER);

    $sortOrderCounter = 1;
    foreach ($matches as $match) {
        $classes = $match[1];
        $attrs = $match[2];
        $inner = $match[3];

        $isHero = strpos($classes, 'project-card--hero') !== false;

        // Extract attributes
        $getAttr = function($attrName) use ($attrs) {
            if (preg_match('/' . preg_quote($attrName, '/') . '="([^"]*)"/i', $attrs, $m)) {
                return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            return '';
        };

        $title = $getAttr('data-title');
        if (empty($title) && preg_match('/<h3 class="project-card__title">([^<]+)<\/h3>/i', $inner, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if (empty($title)) continue;

        $categorySlug = $getAttr('data-category');
        $primaryLabel = $getAttr('data-primary-label');
        $additionalCatSlugs = array_filter(array_map('trim', explode(',', $getAttr('data-additional-categories'))));
        $industry = $getAttr('data-industry');
        $desc = $getAttr('data-desc');
        $tags = $getAttr('data-tags');

        // Subtitle
        $subtitle = '';
        if (preg_match('/<div class="project-card__subtitle">([^<]+)<\/div>/i', $inner, $m)) {
            $subtitle = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // Image
        $image = '';
        if (preg_match('/<img[^>]+src="([^"]+)"/i', $inner, $m)) {
            $image = trim($m[1]);
        }

        // Generate clean slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

        $parsedProjects[] = [
            'title' => $title,
            'slug' => $slug,
            'category_slug' => $categorySlug ?: 'graphics',
            'primary_label' => $primaryLabel ?: ucfirst(str_replace('-', ' ', $categorySlug)),
            'additional_categories' => $additionalCatSlugs,
            'industry' => $industry,
            'subtitle' => $subtitle,
            'description' => $desc ?: $subtitle,
            'tags' => $tags,
            'hero_image' => $image ?: null,
            'is_featured' => $isHero ? 1 : 0,
            'sort_order' => $sortOrderCounter++,
        ];
    }
}

// Fetch all existing project slugs from DB to detect already imported projects
$existingSlugs = [];
try {
    $existingSlugs = $pdo->query("SELECT slug FROM projects")->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (Throwable $e) {}

// Fetch all existing categories map (slug => id)
$categoryMap = [];
try {
    $catRows = $pdo->query("SELECT id, slug, name FROM project_categories")->fetchAll();
    foreach ($catRows as $c) {
        $categoryMap[$c['slug']] = (int)$c['id'];
    }
} catch (Throwable $e) {}

$migrationResult = null;

// Handle Migration POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_migration') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $parseError = 'CSRF validation failed. Please try again.';
    } else {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($parsedProjects as $p) {
            if (in_array($p['slug'], $existingSlugs, true)) {
                $skipped++;
                continue;
            }

            // Resolve primary category ID
            $catSlug = $p['category_slug'];
            $catId = $categoryMap[$catSlug] ?? null;

            if (!$catId) {
                // Insert category if it doesn't exist
                $catName = !empty($p['primary_label']) ? $p['primary_label'] : ucfirst(str_replace('-', ' ', $catSlug));
                try {
                    $insertCat = $pdo->prepare("INSERT INTO project_categories (name, slug, display_order, is_active, created_at) VALUES (?, ?, 50, 1, NOW())");
                    $insertCat->execute([$catName, $catSlug]);
                    $catId = (int)$pdo->lastInsertId();
                    $categoryMap[$catSlug] = $catId;
                } catch (Throwable $e) {
                    $catId = 1; // Fallback to category 1
                }
            }

            // Resolve additional categories
            $addCatIds = [];
            foreach ($p['additional_categories'] as $addSlug) {
                if (isset($categoryMap[$addSlug])) {
                    $addCatIds[] = $categoryMap[$addSlug];
                }
            }

            $projectData = [
                'title' => $p['title'],
                'slug' => $p['slug'],
                'primary_category_id' => $catId,
                'additional_category_ids' => $addCatIds,
                'client_name' => $p['title'],
                'industry' => $p['industry'],
                'summary' => $p['subtitle'] ?: substr($p['description'], 0, 100),
                'description' => $p['description'],
                'hero_image' => $p['hero_image'],
                'tags' => $p['tags'],
                'project_type' => $p['primary_label'],
                'project_url' => null,
                'is_featured' => $p['is_featured'],
                'status' => 'published',
                'sort_order' => $p['sort_order'],
                'subtitle' => $p['subtitle'],
            ];

            try {
                $projectService->saveProject($projectData);
                $imported++;
                $existingSlugs[] = $p['slug'];
            } catch (Throwable $e) {
                $errors[] = "Error migrating '{$p['title']}': " . $e->getMessage();
            }
        }

        $migrationResult = [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
?>

<div style="max-width: 1040px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <a href="projects.php" style="display: inline-flex; align-items: center; gap: 6px; color: var(--admin-text-muted); font-size: 0.88rem; margin-bottom: 6px; text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Projects
            </a>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--admin-text);">Migrate Projects from HTML</h2>
            <p style="color: var(--admin-text-muted); font-size: 0.9rem;">
                Scan and import <?= count($parsedProjects) ?> hard-coded portfolio projects from <code>projects.html</code> directly into MySQL.
            </p>
        </div>

        <?php if (!empty($parsedProjects)): ?>
            <form method="POST" onsubmit="return confirm('Run migration now? This will insert any projects not yet in the database.');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="run_migration">
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; font-weight: 600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Run Migration Now
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($migrationResult): ?>
        <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 12px;">Migration Results</h3>
            <div style="display: flex; gap: 24px; margin-bottom: 16px;">
                <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--admin-success); padding: 12px 18px; border-radius: 6px;">
                    <span style="font-size: 1.4rem; font-weight: 700; color: #065f46;"><?= $migrationResult['imported'] ?></span>
                    <div style="font-size: 0.85rem; color: #065f46;">Projects Imported</div>
                </div>
                <div style="background: rgba(100, 116, 139, 0.1); border-left: 4px solid #64748b; padding: 12px 18px; border-radius: 6px;">
                    <span style="font-size: 1.4rem; font-weight: 700; color: #334155;"><?= $migrationResult['skipped'] ?></span>
                    <div style="font-size: 0.85rem; color: #334155;">Already in DB (Skipped)</div>
                </div>
                <?php if (!empty($migrationResult['errors'])): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--admin-danger); padding: 12px 18px; border-radius: 6px;">
                        <span style="font-size: 1.4rem; font-weight: 700; color: #991b1b;"><?= count($migrationResult['errors']) ?></span>
                        <div style="font-size: 0.85rem; color: #991b1b;">Errors</div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($migrationResult['errors'])): ?>
                <div style="color: var(--admin-danger); font-size: 0.85rem; margin-top: 10px;">
                    <?php foreach ($migrationResult['errors'] as $err): ?>
                        <div>• <?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 16px;">
                <a href="projects.php" class="btn btn-primary btn-sm">Go to Projects Management &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($parseError): ?>
        <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--admin-danger); color: #991b1b; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px;">
            <?= htmlspecialchars($parseError) ?>
        </div>
    <?php endif; ?>

    <!-- Projects Found in projects.html Table -->
    <div class="card" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--admin-text); margin: 0;">
                Parsed Projects (<?= count($parsedProjects) ?> Found)
            </h3>
            <span style="font-size: 0.82rem; color: var(--admin-text-muted);">
                Green badge = Ready to import &bull; Gray = Already in database
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="background: var(--admin-border-light); border-bottom: 1px solid var(--admin-border); text-align: left;">
                        <th style="padding: 10px 16px; font-weight: 600; color: var(--admin-text);">#</th>
                        <th style="padding: 10px 16px; font-weight: 600; color: var(--admin-text);">Project</th>
                        <th style="padding: 10px 16px; font-weight: 600; color: var(--admin-text);">Primary Category</th>
                        <th style="padding: 10px 16px; font-weight: 600; color: var(--admin-text);">Additional Categories</th>
                        <th style="padding: 10px 16px; font-weight: 600; color: var(--admin-text);">Status in DB</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parsedProjects as $idx => $p): 
                        $exists = in_array($p['slug'], $existingSlugs, true);
                    ?>
                        <tr style="border-bottom: 1px solid var(--admin-border); <?= $exists ? 'opacity: 0.6;' : '' ?>">
                            <td style="padding: 12px 16px; color: var(--admin-text-muted);"><?= $idx + 1 ?></td>
                            <td style="padding: 12px 16px;">
                                <strong style="color: var(--admin-text);"><?= htmlspecialchars($p['title']) ?></strong>
                                <?php if (!empty($p['subtitle'])): ?>
                                    <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><?= htmlspecialchars($p['subtitle']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="display: inline-block; background: rgba(37, 99, 235, 0.1); color: var(--admin-primary); font-weight: 600; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">
                                    <?= htmlspecialchars($p['primary_label']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if (!empty($p['additional_categories'])): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php foreach ($p['additional_categories'] as $ac): ?>
                                            <span style="font-size: 0.72rem; background: var(--admin-border-light); color: var(--admin-text-muted); padding: 2px 6px; border-radius: 4px;">
                                                <?= htmlspecialchars($ac) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-size: 0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($exists): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; color: var(--admin-text-muted); font-weight: 500;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        Already in DB
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; color: var(--admin-success); font-weight: 600;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                        Ready to Import
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
