<?php
/**
 * Knitin Portfolio — Projects Showcase (Public)
 * Dynamic listing of all published projects from MySQL database.
 * Premium Agency-Grade Design & High-Impact UI/UX.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
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
    'web-uiux'   => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
    'pharma'     => 'linear-gradient(135deg, #3b0764 0%, #7e22ce 50%, #a855f7 100%)',
    'branding'   => 'linear-gradient(135deg, #18181b 0%, #27272a 50%, #0284c7 100%)',
    'graphics'   => 'linear-gradient(135deg, #451a03 0%, #b45309 50%, #f59e0b 100%)',
    'motion'     => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
    'web-design' => 'linear-gradient(135deg, #064e3b 0%, #059669 50%, #34d399 100%)',
];

/**
 * Render a single project card HTML with polished aesthetics and intelligent fallbacks.
 */
function renderProjectCard(array $proj, array $gradients, bool $isFeaturedSection = false): string {
    $catSlug = htmlspecialchars($proj['primary_category_slug'] ?? '');
    $catName = htmlspecialchars($proj['primary_category_name'] ?? '');
    $title = htmlspecialchars($proj['title'] ?? '');
    $desc = trim($proj['summary'] ?? $proj['description'] ?? '');
    $slug = htmlspecialchars($proj['slug'] ?? '');
    $heroImage = $proj['hero_image'] ?? '';
    $industry = htmlspecialchars($proj['industry'] ?? '');
    $subtitle = htmlspecialchars($proj['subtitle'] ?? '');
    $gradient = $gradients[$catSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';
    $projectYear = !empty($proj['project_year']) ? (int)$proj['project_year'] : (!empty($proj['created_at']) ? (int)date('Y', strtotime($proj['created_at'])) : (int)date('Y'));

    // Intelligent description fallback so cards never appear empty
    if (empty($desc)) {
        if ($catSlug === 'pharma') {
            $desc = 'Pharmaceutical packaging & visual communication engineered for medical precision, clinical hierarchy, and brand compliance.';
        } elseif ($catSlug === 'branding') {
            $desc = 'Strategic corporate identity system and visual brand collateral crafted for distinct market presence across print and digital.';
        } elseif ($catSlug === 'visual-aid') {
            $desc = 'Specialized medical visual aid presentation materials structured for healthcare representative detailing and impact.';
        } elseif (in_array($catSlug, ['web-uiux', 'web-design'], true)) {
            $desc = 'Interactive user experience and high-performance digital interface designed with modern responsiveness and visual polish.';
        } else {
            $desc = 'Creative visual design asset developed with balanced typography, modern composition, and strategic visual communication.';
        }
    }

    // Tags & fallbacks
    $tags = array_filter(array_map('trim', explode(',', $proj['tags'] ?? '')));
    if (empty($tags)) {
        if ($catSlug === 'pharma') {
            $tags = ['Packaging', 'Visual Aid', 'Pharma Design'];
        } elseif ($catSlug === 'branding') {
            $tags = ['Brand Identity', 'Logo System', 'Vector'];
        } elseif ($catSlug === 'visual-aid') {
            $tags = ['Visual Aid', 'Medical Detailing', 'Print'];
        } elseif (in_array($catSlug, ['web-uiux', 'web-design'], true)) {
            $tags = ['UI/UX', 'Web Design', 'Responsive'];
        } else {
            $tags = ['Graphic Design', 'Creative', 'Print'];
        }
    }

    // Build additional category slugs for multi-category filtering
    $additionalSlugs = array_map(function($c) { return $c['slug'] ?? ''; }, $proj['additional_categories'] ?? []);
    $additionalLabels = array_map(function($c) { return $c['name'] ?? ''; }, $proj['additional_categories'] ?? []);

    $cardClass = 'project-card' . ($isFeaturedSection ? ' project-card--featured' : '');
    $dataAttrs = 'data-category="' . $catSlug . '"'
        . ' data-additional-categories="' . htmlspecialchars(implode(',', $additionalSlugs)) . '"'
        . ' data-title="' . $title . '"'
        . ' data-industry="' . $industry . '"'
        . ' data-desc="' . htmlspecialchars($desc) . '"'
        . ' data-tags="' . htmlspecialchars(implode(',', $tags)) . '"'
        . ' data-primary-label="' . $catName . '"'
        . ' data-additional-labels="' . htmlspecialchars(implode(',', $additionalLabels)) . '"';

    $imageUrl = project_image_url($heroImage);
    $imageHtml = '';
    if (!empty($imageUrl)) {
        $imageHtml = '
        <div class="project-card__image">
            <span class="card-float-cat"><span class="cat-dot"></span>' . $catName . '</span>
            <span class="card-float-year">' . $projectYear . '</span>
            <img src="' . htmlspecialchars($imageUrl) . '" alt="' . $title . ' — Project by Nitin Kumar" loading="lazy" onerror="this.style.display=\'none\'; if(this.nextElementSibling) this.nextElementSibling.style.display=\'flex\';">
            <div class="project-card__placeholder" style="display: none; background: ' . $gradient . ';">
                <div class="project-card__placeholder-badge">' . htmlspecialchars(strtoupper(substr($title, 0, 2))) . '</div>
                <span class="project-card__placeholder-title">' . $title . '</span>
            </div>
            <a href="/project/' . $slug . '" class="card-image-hover-overlay" aria-label="Explore ' . $title . '">
                <span class="card-hover-btn">
                    <span>Explore Case</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </span>
            </a>
        </div>';
    } else {
        $imageHtml = '
        <div class="project-card__image">
            <span class="card-float-cat"><span class="cat-dot"></span>' . $catName . '</span>
            <span class="card-float-year">' . $projectYear . '</span>
            <div class="project-card__placeholder" style="background: ' . $gradient . ';">
                <div class="project-card__placeholder-badge">' . htmlspecialchars(strtoupper(substr($title, 0, 2))) . '</div>
                <span class="project-card__placeholder-title">' . $title . '</span>
            </div>
            <a href="/project/' . $slug . '" class="card-image-hover-overlay" aria-label="Explore ' . $title . '">
                <span class="card-hover-btn">
                    <span>Explore Case</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </span>
            </a>
        </div>';
    }

    $tagsHtml = '';
    if (!empty($tags)) {
        $tagsHtml = '<div class="project-card__tags">';
        foreach (array_slice($tags, 0, 3) as $tag) {
            $tagsHtml .= '<span class="project-card__tag">#' . htmlspecialchars($tag) . '</span>';
        }
        $tagsHtml .= '</div>';
    }

    $industryLine = !empty($industry) ? $industry : (!empty($subtitle) ? $subtitle : $catName);

    return '
    <article class="' . $cardClass . '" ' . $dataAttrs . '>
        ' . $imageHtml . '
        <div class="project-card__body">
            <div class="project-card__meta-bar">
                <span class="project-card__industry">' . $industryLine . '</span>
            </div>
            <h3 class="project-card__title">
                <a href="/project/' . $slug . '">' . $title . '</a>
            </h3>
            <p class="project-card__desc">' . htmlspecialchars($desc) . '</p>
            ' . $tagsHtml . '
            <div class="project-card__footer">
                <a href="/project/' . $slug . '" class="project-card__link">
                    <span>View Project</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <span class="project-card__cat-pill">' . $catName . '</span>
            </div>
        </div>
    </article>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">

    <title>Projects Showcase | Nitin Kumar — Graphic Designer &amp; Web Developer</title>
    <meta name="description" content="Explore selected multidisciplinary works across pharmaceutical packaging, visual brand identities, UI/UX architecture, and interactive web design by Nitin Kumar.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://knitin525.in/projects">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://knitin525.in/projects">
    <meta property="og:title" content="Projects Showcase | Nitin Kumar — Creative Portfolio">
    <meta property="og:description" content="Selected pharmaceutical design, brand identities, UI/UX, and web development projects by Nitin Kumar.">
    <meta property="og:image" content="https://knitin525.in/img/og-image.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/favicon.png">
    <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">

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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheets with Root Absolute Paths -->
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/projects.css">
</head>

<body>
    <!-- Scroll Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="/" class="logo">
                <img src="/img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
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
                <div class="nav-social" aria-label="Social Profiles">
                    <a href="https://www.facebook.com/knitin525" target="_blank" rel="noopener noreferrer" class="nav-social-link social-fb" aria-label="Facebook" title="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/knit.in/" target="_blank" rel="noopener noreferrer" class="nav-social-link social-insta" aria-label="Instagram" title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="https://www.linkedin.com/in/knitin0706/" target="_blank" rel="noopener noreferrer" class="nav-social-link social-linkedin" aria-label="LinkedIn" title="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@knitin525" target="_blank" rel="noopener noreferrer" class="nav-social-link social-yt" aria-label="YouTube" title="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
                <span class="nav-divider" aria-hidden="true"></span>
                <div class="nav-cta">
                    <a href="/#contact" class="nav-cta-link">Let's Talk <span class="cta-arrow">↗</span></a>
                </div>
                <button class="theme-toggle-btn" id="themeToggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/#about">About</a></li>
            <li><a href="/#services">Services</a></li>
            <li><a href="/projects" class="active">Work</a></li>
            <li><a href="/#skills">Skills</a></li>
            <li><a href="/#contact">Contact</a></li>
        </ul>
        <div class="mobile-social">
            <span class="mobile-social-title">Connect with me</span>
            <div class="mobile-social-icons">
                <a href="https://www.facebook.com/knitin525" target="_blank" rel="noopener noreferrer" class="nav-social-link social-fb" aria-label="Facebook" title="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <a href="https://www.instagram.com/knit.in/" target="_blank" rel="noopener noreferrer" class="nav-social-link social-insta" aria-label="Instagram" title="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                <a href="https://www.linkedin.com/in/knitin0706/" target="_blank" rel="noopener noreferrer" class="nav-social-link social-linkedin" aria-label="LinkedIn" title="LinkedIn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                    </svg>
                </a>
                <a href="https://www.youtube.com/@knitin525" target="_blank" rel="noopener noreferrer" class="nav-social-link social-yt" aria-label="YouTube" title="YouTube">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Executive Studio Hero Section -->
    <section class="projects-hero">
        <div class="container">
            <div class="projects-hero__grid">
                <!-- Left: Headline & Strategic Introduction -->
                <div class="projects-hero__intro">
                    <div class="projects-hero__badge">
                        <span class="projects-hero__dot"></span>
                        <span>Selected Works &bull; 10+ Years Creative Craft</span>
                    </div>
                    <h1 class="projects-hero__title">
                        Selected Works &amp; <span class="title-gradient">Projects Showcase</span>
                    </h1>
                    <p class="projects-hero__desc">
                        A curated collection of multidisciplinary work spanning pharmaceutical packaging, visual brand systems, medical visual aids, and interactive web experiences.
                    </p>
                    <div class="projects-hero__specialties">
                        <span class="hero-skill-pill">💊 Pharma Packaging</span>
                        <span class="hero-skill-pill">🎨 Brand Identity</span>
                        <span class="hero-skill-pill">💻 Web &amp; UI/UX</span>
                        <span class="hero-skill-pill">📐 Medical Visual Aids</span>
                    </div>
                </div>

                <!-- Right: High-Trust Impact Stats Card -->
                <div class="projects-hero__stats-card">
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">10+</div>
                        <div class="hero-stat-label">Years Experience</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">50+</div>
                        <div class="hero-stat-label">Brand Artworks</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">100%</div>
                        <div class="hero-stat-label">Compliance Ready</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">4+</div>
                        <div class="hero-stat-label">Core Disciplines</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Unified Filter & Live Search Toolbar (Sticky, Full-Width Glassmorphism) -->
    <section class="projects-toolbar-wrapper">
        <div class="container">
            <div class="projects-toolbar-bar">
                <!-- Category Filter Buttons — Dynamic & Scrollable -->
                <div class="projects-filter-pills" role="tablist" aria-label="Filter projects by category">
                    <button class="projects-filter-btn active" data-filter="all" type="button" role="tab" aria-selected="true">
                        <span>All</span>
                        <span class="projects-filter-count" id="countAll"><?= count($allProjects) ?></span>
                    </button>
                    <?php foreach ($publicCategories as $cat): ?>
                        <button class="projects-filter-btn" data-filter="<?= htmlspecialchars($cat['slug']) ?>" type="button" role="tab" aria-selected="false">
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                            <span class="projects-filter-count"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Live Search Box with Icon and Clear Action -->
                <div class="projects-search-wrap">
                    <svg class="projects-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="projectSearchInput" class="projects-search-input" placeholder="Search projects by title, tag..." aria-label="Search projects">
                    <button type="button" id="projectSearchClear" class="projects-search-clear" style="display: none;" aria-label="Clear search">&times;</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Spotlights (If any featured projects) -->
    <?php if (!empty($featuredProjects)): ?>
    <section class="projects-section-wrap projects-featured-wrap">
        <div class="container">
            <div class="projects-section-header">
                <h2 class="projects-section-heading" id="featuredHeading">
                    <span class="heading-accent-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </span>
                    Featured Spotlights
                </h2>
                <span class="projects-section-subtitle">Specially curated marquee highlights</span>
            </div>
            <div class="projects-grid" id="featuredGrid">
                <?php foreach ($featuredProjects as $proj): ?>
                    <?= renderProjectCard($proj, $gradients, true) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Complete Portfolio Directory -->
    <section class="projects-section-wrap projects-directory-wrap">
        <div class="container">
            <div class="projects-section-header" style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 12px; margin-bottom: 28px;">
                <div>
                    <h2 class="projects-section-heading" style="margin-bottom: 4px;">
                        Complete Portfolio Showcase
                    </h2>
                    <span class="projects-section-subtitle">Explore client deliverables, brand systems, and creative executions</span>
                </div>
                <div class="projects-directory-counter">
                    <span class="counter-dot"></span>
                    <span id="directoryCount"><?= count($directoryProjects) ?> Projects</span>
                </div>
            </div>

            <!-- Card Grid -->
            <div class="projects-grid" id="directoryGrid">
                <?php if (empty($directoryProjects)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <p style="font-size: 1.1rem;">All published projects are showcased in the Featured section above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($directoryProjects as $proj): ?>
                        <?= renderProjectCard($proj, $gradients) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No Results Found State (Live Search / Filter) -->
            <div class="projects-no-results" id="projectsNoResults" style="display: none;">
                <div class="no-results-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--text); margin-bottom: 8px;">No matching projects found</h3>
                <p style="color: var(--text-secondary); max-width: 440px; margin: 0 auto 20px;">We could not find any projects matching your search or active filters. Try searching for a different keyword or resetting filters.</p>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelector('.projects-filter-btn[data-filter=\'all\']').click(); document.getElementById('projectSearchInput').value='';">
                    View All Projects
                </button>
            </div>
        </div>
    </section>

    <!-- Studio Call-To-Action Banner -->
    <section class="projects-cta-section">
        <div class="container">
            <div class="projects-cta-box">
                <div style="max-width: 600px;">
                    <span class="cta-mini-tag">START A PROJECT</span>
                    <h2 class="cta-heading">Ready to elevate your visual identity or packaging?</h2>
                    <p class="cta-desc">Let's discuss how customized design strategy, clinical precision, and creative craftsmanship can scale your brand presence.</p>
                </div>
                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <a href="/#contact" class="btn btn-primary" style="padding: 13px 28px; font-weight: 600;">
                        Let's Discuss Your Project
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Site Footer -->
    <footer class="footer" itemscope itemtype="https://schema.org/WPFooter">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <img src="/img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
                    </a>
                    <p>Professional graphic design, pharmaceutical packaging, and web development services in Chandigarh.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Navigation</h4>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="/projects">All Projects</a></li>
                            <li><a href="/#services">Services</a></li>
                            <li><a href="/#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Specializations</h4>
                        <ul>
                            <li><a href="/projects?category=pharma">Pharma Packaging</a></li>
                            <li><a href="/projects?category=branding">Brand Identities</a></li>
                            <li><a href="/projects?category=web-uiux">Web &amp; UI/UX</a></li>
                            <li><a href="/projects?category=graphics">Marketing Print</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Knitin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Interactive Project Modal (If JS modal is used) -->
    <div id="projectModalBackdrop" class="project-modal-backdrop">
        <div class="project-modal-card">
            <button type="button" id="projectModalClose" class="project-modal-close" aria-label="Close modal">&times;</button>
            <div id="modalImageWrap" class="project-modal__image-wrap"></div>
            <div class="project-modal__body">
                <div id="modalCategory" class="project-modal__category"></div>
                <h2 id="modalTitle" class="project-modal__title"></h2>
                <div id="modalSubtitle" class="project-modal__subtitle"></div>
                <div id="modalDesc" class="project-modal__desc"></div>
                <div id="modalTags" class="project-modal__tags"></div>
                <div id="modalAdditionalWrap" style="display: none; margin-top: 16px;">
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary);">Also tagged in:</span>
                    <div id="modalAdditionalCategories" style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts with absolute paths -->
    <script src="/js/main.js"></script>
    <script src="/js/projects.js"></script>
</body>
</html>
