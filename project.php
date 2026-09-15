<?php
/**
 * Knitin Portfolio — Single Project Detail Page
 * URL: /project/{slug}
 * Fetches a single published project from MySQL and renders a rich detail view.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/ProjectService.php';

$projectService = new ProjectService();

// Get slug from URL
$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    http_response_code(404);
    $pageError = true;
} else {
    $project = $projectService->getProjectBySlug($slug);

    if (!$project) {
        http_response_code(404);
        $pageError = true;
    } elseif ($project['status'] !== 'published') {
        // Only published projects visible publicly
        http_response_code(404);
        $pageError = true;
    } else {
        $pageError = false;
        // Get related projects
        $relatedProjects = $projectService->getRelatedProjects((int)$project['id'], 3);
        // Get prev/next navigation
        $adjacent = $projectService->getAdjacentProjects((int)$project['id']);

        // Prepare display data
        $title = htmlspecialchars($project['title'] ?? '');
        $catName = htmlspecialchars($project['primary_category_name'] ?? '');
        $catSlug = htmlspecialchars($project['primary_category_slug'] ?? '');
        $description = $project['description'] ?? '';
        $summary = htmlspecialchars($project['summary'] ?? '');
        $industry = htmlspecialchars($project['industry'] ?? '');
        $clientName = htmlspecialchars($project['client_name'] ?? '');
        $projectType = htmlspecialchars($project['project_type'] ?? '');
        $projectUrl = htmlspecialchars($project['project_url'] ?? '');
        $heroImage = $project['hero_image'] ?? '';
        $tags = array_filter(array_map('trim', explode(',', $project['tags'] ?? '')));
        $additionalCats = $project['additional_categories'] ?? [];
        $createdAt = $project['created_at'] ?? '';

        // SEO
        $seoTitle = $title . ' | Nitin Kumar — Portfolio';
        $seoDesc = !empty($summary) ? $summary : htmlspecialchars(mb_substr(strip_tags($description), 0, 160));

        // Gradients
        $gradients = [
            'web-uiux'  => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
            'pharma'    => 'linear-gradient(135deg, #581c87 0%, #9333ea 50%, #c084fc 100%)',
            'branding'  => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
            'graphics'  => 'linear-gradient(135deg, #78350f 0%, #d97706 50%, #fbbf24 100%)',
            'motion'    => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
        ];
        $gradient = $gradients[$catSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (empty($pageError)): ?>
        <title><?= $seoTitle ?></title>
        <meta name="description" content="<?= $seoDesc ?>">
        <link rel="canonical" href="https://knitin525.in/project/<?= htmlspecialchars($slug) ?>">
        <meta property="og:title" content="<?= $title ?> | Nitin Kumar">
        <meta property="og:description" content="<?= $seoDesc ?>">
        <?php if (!empty($heroImage)): ?>
            <meta property="og:image" content="https://knitin525.in/<?= htmlspecialchars($heroImage) ?>">
        <?php endif; ?>
    <?php else: ?>
        <title>Project Not Found | Nitin Kumar</title>
        <meta name="robots" content="noindex">
    <?php endif; ?>

    <meta property="og:type" content="article">
    <link rel="icon" type="image/png" href="img/favicon.png">

    <!-- Theme -->
    <meta name="theme-color" content="#fafbfc" id="metaThemeColor">
    <script>
        (function() {
            try {
                const saved = localStorage.getItem('portfolio-theme');
                const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = saved || (systemDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                const metaColor = document.getElementById('metaThemeColor');
                if (metaColor) metaColor.setAttribute('content', theme === 'dark' ? '#0B0D10' : '#fafbfc');
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/projects.css">
</head>
<body>
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="/" class="logo"><img src="img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img"></a>
            <ul class="nav-links" id="navLinks">
                <li><a href="/">Home</a></li>
                <li><a href="/#about">About</a></li>
                <li><a href="/#services">Services</a></li>
                <li><a href="/projects" class="active">Work</a></li>
                <li><a href="/#skills">Skills</a></li>
                <li><a href="/#contact">Contact</a></li>
            </ul>
            <div class="nav-actions">
                <button class="theme-toggle-btn" id="themeToggle" type="button" aria-label="Switch theme">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <div class="nav-cta"><a href="/#contact" class="btn btn-primary">Let's Talk</a></div>
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            </div>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/#about">About</a></li>
            <li><a href="/#services">Services</a></li>
            <li><a href="/projects" class="active">Work</a></li>
            <li><a href="/#skills">Skills</a></li>
            <li><a href="/#contact">Contact</a></li>
        </ul>
    </div>

    <?php if (!empty($pageError)): ?>
        <!-- 404 Project Not Found -->
        <section class="project-detail-404">
            <div class="container" style="text-align: center; padding: 120px 20px;">
                <h1 style="font-size: 4rem; font-weight: 800; color: var(--text-primary); margin-bottom: 16px;">404</h1>
                <h2 style="font-size: 1.4rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Project Not Found</h2>
                <p style="color: var(--text-secondary); max-width: 480px; margin: 0 auto 32px;">The project you're looking for doesn't exist or may have been removed.</p>
                <a href="/projects" class="btn btn-primary">Browse All Projects</a>
            </div>
        </section>
    <?php else: ?>
        <!-- Breadcrumb -->
        <section class="project-detail-breadcrumb">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Home</a>
                    <span class="breadcrumb__sep">/</span>
                    <a href="/projects">Projects</a>
                    <span class="breadcrumb__sep">/</span>
                    <span class="breadcrumb__current"><?= $title ?></span>
                </nav>
            </div>
        </section>

        <!-- Project Hero -->
        <section class="project-detail-hero">
            <div class="container">
                <div class="project-detail-hero__meta">
                    <span class="project-detail-hero__category"><?= $catName ?></span>
                    <?php if (!empty($additionalCats)): ?>
                        <?php foreach (array_slice($additionalCats, 0, 3) as $addCat): ?>
                            <span class="project-detail-hero__additional-cat"><?= htmlspecialchars($addCat['name']) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <h1 class="project-detail-hero__title"><?= $title ?></h1>
                <?php if (!empty($industry)): ?>
                    <p class="project-detail-hero__subtitle"><?= $industry ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Hero Image -->
        <section class="project-detail-image">
            <div class="container">
                <?php if (!empty($heroImage)): ?>
                    <div class="project-detail-image__wrap">
                        <img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= $title ?>" loading="eager">
                    </div>
                <?php else: ?>
                    <div class="project-detail-image__placeholder" style="background: <?= $gradient ?>;">
                        <span><?= $title ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Project Content -->
        <section class="project-detail-content">
            <div class="container">
                <div class="project-detail-layout">
                    <!-- Main Content -->
                    <div class="project-detail-main">
                        <?php if (!empty($summary)): ?>
                            <p class="project-detail-summary"><?= $summary ?></p>
                        <?php endif; ?>

                        <?php if (!empty($description)): ?>
                            <div class="project-detail-description">
                                <?= nl2br(htmlspecialchars($description)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($tags)): ?>
                            <div class="project-detail-tags">
                                <h3 class="project-detail-sidebar__label">Technologies & Skills</h3>
                                <div class="project-detail-tags__list">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="project-detail-tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <aside class="project-detail-sidebar">
                        <?php if (!empty($clientName)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Client</span>
                                <span class="project-detail-sidebar__value"><?= $clientName ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($industry)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Industry</span>
                                <span class="project-detail-sidebar__value"><?= $industry ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($projectType)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Project Type</span>
                                <span class="project-detail-sidebar__value"><?= $projectType ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="project-detail-sidebar__item">
                            <span class="project-detail-sidebar__label">Category</span>
                            <span class="project-detail-sidebar__value"><?= $catName ?></span>
                        </div>

                        <?php if (!empty($createdAt)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Year</span>
                                <span class="project-detail-sidebar__value"><?= date('Y', strtotime($createdAt)) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($projectUrl)): ?>
                            <a href="<?= $projectUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%; margin-top: 16px; text-align: center;">
                                View Live Project
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 6px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </section>

        <!-- Prev / Next Navigation -->
        <?php if (!empty($adjacent['prev']) || !empty($adjacent['next'])): ?>
        <section class="project-detail-nav">
            <div class="container">
                <div class="project-detail-nav__inner">
                    <?php if (!empty($adjacent['prev'])): ?>
                        <a href="/project/<?= htmlspecialchars($adjacent['prev']['slug']) ?>" class="project-detail-nav__link project-detail-nav__link--prev">
                            <span class="project-detail-nav__direction">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                Previous Project
                            </span>
                            <span class="project-detail-nav__title"><?= htmlspecialchars($adjacent['prev']['title']) ?></span>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if (!empty($adjacent['next'])): ?>
                        <a href="/project/<?= htmlspecialchars($adjacent['next']['slug']) ?>" class="project-detail-nav__link project-detail-nav__link--next">
                            <span class="project-detail-nav__direction">
                                Next Project
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </span>
                            <span class="project-detail-nav__title"><?= htmlspecialchars($adjacent['next']['title']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Related Projects -->
        <?php if (!empty($relatedProjects)): ?>
        <section class="project-detail-related">
            <div class="container">
                <h2 class="projects-section-heading">Related Projects</h2>
                <div class="projects-grid projects-grid--related">
                    <?php
                    $relGradients = [
                        'web-uiux'  => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
                        'pharma'    => 'linear-gradient(135deg, #581c87 0%, #9333ea 50%, #c084fc 100%)',
                        'branding'  => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
                        'graphics'  => 'linear-gradient(135deg, #78350f 0%, #d97706 50%, #fbbf24 100%)',
                        'motion'    => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
                    ];
                    foreach ($relatedProjects as $rel):
                        $relSlug = htmlspecialchars($rel['primary_category_slug'] ?? '');
                        $relGrad = $relGradients[$relSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';
                        $relTitle = htmlspecialchars($rel['title'] ?? '');
                        $relHero = $rel['hero_image'] ?? '';
                    ?>
                        <a href="/project/<?= htmlspecialchars($rel['slug'] ?? '') ?>" class="related-project-card">
                            <?php if (!empty($relHero)): ?>
                                <div class="related-project-card__image">
                                    <img src="<?= htmlspecialchars($relHero) ?>" alt="<?= $relTitle ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="related-project-card__image" style="background: <?= $relGrad ?>;">
                                    <span class="related-project-card__placeholder"><?= $relTitle ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="related-project-card__body">
                                <span class="related-project-card__category"><?= htmlspecialchars($rel['primary_category_name'] ?? '') ?></span>
                                <h3 class="related-project-card__title"><?= $relTitle ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA -->
        <section class="project-detail-cta">
            <div class="container" style="text-align: center; padding: 60px 20px;">
                <h2 style="font-size: 1.6rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px;">Interested in working together?</h2>
                <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px;">Let's discuss your project and see how I can help bring your vision to life.</p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/#contact" class="btn btn-primary">Get In Touch</a>
                    <a href="/projects" class="btn btn-secondary">View All Projects</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer" itemscope itemtype="https://schema.org/WPFooter">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo"><img src="img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img"></a>
                    <p>Professional graphic design and web development services in Chandigarh.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="/projects">All Projects</a></li>
                            <li><a href="/#contact">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Knitin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
