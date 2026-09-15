<?php
/**
 * Knitin Portfolio — Projects Showcase (Public)
 * Dynamic listing of all published projects from MySQL database.
 * Replaces the old static projects.html
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/ProjectService.php';

$projectService = new ProjectService();

// Get filter parameters
$categoryFilter = trim($_GET['category'] ?? 'all');

// Fetch all published projects
$filters = ['status' => 'published'];
if ($categoryFilter !== 'all') {
    $filters['category_slug'] = $categoryFilter;
}

$allProjects = $projectService->getProjects($filters);
$featuredProjects = $projectService->getProjects([
    'status' => 'published',
    'is_featured' => 1,
]);
$publicCategories = $projectService->getPublishedCategories();

// Separate: featured vs directory (non-featured)
$directoryProjects = array_filter($allProjects, function($p) {
    return empty($p['is_featured']);
});

// Gradient map for placeholder images
$gradients = [
    'web-uiux'  => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
    'pharma'    => 'linear-gradient(135deg, #581c87 0%, #9333ea 50%, #c084fc 100%)',
    'branding'  => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
    'graphics'  => 'linear-gradient(135deg, #78350f 0%, #d97706 50%, #fbbf24 100%)',
    'motion'    => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
];

/**
 * Render a single project card HTML.
 */
function renderProjectCard(array $proj, array $gradients, bool $isFeaturedSection = false): string {
    $catSlug = htmlspecialchars($proj['primary_category_slug'] ?? '');
    $catName = htmlspecialchars($proj['primary_category_name'] ?? '');
    $title = htmlspecialchars($proj['title'] ?? '');
    $desc = htmlspecialchars($proj['summary'] ?? $proj['description'] ?? '');
    $slug = htmlspecialchars($proj['slug'] ?? '');
    $heroImage = $proj['hero_image'] ?? '';
    $tags = array_filter(array_map('trim', explode(',', $proj['tags'] ?? '')));
    $industry = htmlspecialchars($proj['industry'] ?? '');
    $gradient = $gradients[$catSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';

    // Build additional category slugs for multi-category filtering
    $additionalSlugs = array_map(function($c) { return $c['slug'] ?? ''; }, $proj['additional_categories'] ?? []);
    $additionalLabels = array_map(function($c) { return $c['name'] ?? ''; }, $proj['additional_categories'] ?? []);

    $cardClass = 'project-card';
    $dataAttrs = 'data-category="' . $catSlug . '"'
        . ' data-additional-categories="' . htmlspecialchars(implode(',', $additionalSlugs)) . '"'
        . ' data-title="' . $title . '"'
        . ' data-industry="' . $industry . '"'
        . ' data-desc="' . $desc . '"'
        . ' data-tags="' . htmlspecialchars($proj['tags'] ?? '') . '"'
        . ' data-primary-label="' . $catName . '"'
        . ' data-additional-labels="' . htmlspecialchars(implode(',', $additionalLabels)) . '"';

    $imageHtml = '';
    if (!empty($heroImage)) {
        $imageHtml = '<div class="project-card__image"><img src="' . htmlspecialchars($heroImage) . '" alt="' . $title . ' — Project by Nitin Kumar" loading="lazy"></div>';
    } else {
        $imageHtml = '<div class="project-card__image"><div class="project-card__placeholder" style="background: ' . $gradient . ';"><span class="project-card__placeholder-title">' . $title . '</span></div></div>';
    }

    $tagsHtml = '';
    if (!empty($tags)) {
        $tagsHtml = '<div class="project-card__tags">';
        foreach (array_slice($tags, 0, 3) as $tag) {
            $tagsHtml .= '<span>' . htmlspecialchars($tag) . '</span>';
        }
        $tagsHtml .= '</div>';
    }

    return '
    <article class="' . $cardClass . '" ' . $dataAttrs . '>
        ' . $imageHtml . '
        <div class="project-card__body">
            <span class="project-card__category">' . $catName . '</span>
            <h3 class="project-card__title">' . $title . '</h3>
            <p class="project-card__desc">' . $desc . '</p>
            ' . $tagsHtml . '
            <a href="/project/' . $slug . '" class="project-card__link open-modal-btn">
                View Project
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </article>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projects Showcase | Nitin Kumar — Graphic Designer & Web Developer</title>
    <meta name="description" content="Explore the complete portfolio of Nitin Kumar. Projects spanning web design, UI/UX, pharmaceutical packaging, branding, graphic design and motion graphics.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://knitin525.in/projects">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://knitin525.in/projects">
    <meta property="og:title" content="Projects Showcase | Nitin Kumar">
    <meta property="og:description" content="Explore the complete portfolio spanning web, UI/UX, pharma, branding, and motion design.">
    <meta property="og:image" content="https://knitin525.in/img/og-image.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">

    <!-- Theme Color & Anti-Flash -->
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

    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/projects.css">
</head>

<body>
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="/" class="logo">
                <img src="img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="/">Home</a></li>
                <li><a href="/#about">About</a></li>
                <li><a href="/#services">Services</a></li>
                <li><a href="/projects" class="active">Work</a></li>
                <li><a href="/#skills">Skills</a></li>
                <li><a href="/#contact">Contact</a></li>
            </ul>
            <div class="nav-actions">
                <button class="theme-toggle-btn" id="themeToggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <div class="nav-cta">
                    <a href="/#contact" class="btn btn-primary">Let's Talk</a>
                </div>
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
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

    <!-- Projects Hero -->
    <section class="projects-hero">
        <div class="container">
            <div class="projects-hero__content">
                <span class="projects-hero__eyebrow">
                    <span class="projects-hero__dot"></span>
                    Selected Work
                </span>
                <h1 class="projects-hero__title">Projects Showcase</h1>
                <p class="projects-hero__desc">A curated collection of work across web design, UI/UX, pharmaceutical packaging, branding, graphic design and motion graphics.</p>
            </div>
        </div>
    </section>

    <!-- Filter & Search Toolbar -->
    <section class="projects-toolbar">
        <div class="container">
            <div class="projects-toolbar__inner">
                <!-- Category Filter Buttons — Dynamic -->
                <div class="projects-filter-bar" role="tablist" aria-label="Filter projects by category">
                    <button class="projects-filter-btn active" data-filter="all" type="button" role="tab" aria-selected="true">
                        All <span class="projects-filter-count" id="countAll"><?= count($allProjects) ?></span>
                    </button>
                    <?php foreach ($publicCategories as $cat): ?>
                        <button class="projects-filter-btn" data-filter="<?= htmlspecialchars($cat['slug']) ?>" type="button" role="tab" aria-selected="false">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Live Search -->
                <div class="projects-search-wrap">
                    <svg class="projects-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="projectSearchInput" class="projects-search-input" placeholder="Search projects..." aria-label="Search projects">
                    <button type="button" id="projectSearchClear" class="projects-search-clear" style="display: none;" aria-label="Clear search">&times;</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Highlights Section -->
    <?php if (!empty($featuredProjects)): ?>
    <section class="projects-featured-section">
        <div class="container">
            <h2 class="projects-section-heading" id="featuredHeading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Featured Spotlights
            </h2>
            <div class="projects-grid projects-grid--featured" id="featuredGrid">
                <?php foreach ($featuredProjects as $proj): ?>
                    <?= renderProjectCard($proj, $gradients, true) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Complete Portfolio Directory -->
    <section class="projects-directory-section">
        <div class="container">
            <div class="projects-directory-header">
                <h2 class="projects-section-heading">
                    Complete Portfolio
                </h2>
                <span class="projects-directory-count" id="directoryCount"><?= count($directoryProjects) ?> Projects</span>
            </div>
            <div class="projects-grid" id="directoryGrid">
                <?php if (empty($directoryProjects)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <p style="font-size: 1.1rem;">All published projects are shown as Featured Spotlights above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($directoryProjects as $proj): ?>
                        <?= renderProjectCard($proj, $gradients) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No Results State -->
            <div class="projects-no-results" id="projectsNoResults" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.4; margin-bottom: 12px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <h3>No projects found</h3>
                <p>Try adjusting your search terms or clearing category filters.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" itemscope itemtype="https://schema.org/WPFooter">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <img src="img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
                    </a>
                    <p>Professional graphic design and web development services in Chandigarh. Creating digital experiences that inspire and deliver results.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="/#about">About</a></li>
                            <li><a href="/#services">Services</a></li>
                            <li><a href="/projects">All Projects</a></li>
                            <li><a href="/#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Services</h4>
                        <ul>
                            <li><a href="/#services">UI/UX Design</a></li>
                            <li><a href="/#services">Web Development</a></li>
                            <li><a href="/#services">Graphic Design</a></li>
                            <li><a href="/#services">Branding</a></li>
                            <li><a href="/#services">Logo Design</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Contact</h4>
                        <ul>
                            <li><a href="mailto:knitin525@gmail.com">knitin525@gmail.com</a></li>
                            <li><span>Panchkula, Chandigarh</span></li>
                            <li><span>Open for freelance projects</span></li>
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
    <script src="js/projects.js"></script>
</body>
</html>
