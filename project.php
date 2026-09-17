<?php
/**
 * Knitin Portfolio — Single Project Detail Page
 * URL: /project/{slug}
 * Fetches a single published project from MySQL and renders a rich, high-end detail view.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
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
        // Get gallery images
        $galleryImages = $projectService->getProjectImages((int)$project['id']);

        // Prepare display data
        $title = htmlspecialchars($project['title'] ?? '');
        $catName = htmlspecialchars($project['primary_category_name'] ?? '');
        $catSlug = htmlspecialchars($project['primary_category_slug'] ?? '');
        $description = trim($project['description'] ?? '');
        $summary = htmlspecialchars($project['summary'] ?? '');
        $subtitle = htmlspecialchars($project['subtitle'] ?? '');
        $industry = htmlspecialchars($project['industry'] ?? '');
        $clientName = htmlspecialchars($project['client_name'] ?? '');
        $projectType = htmlspecialchars($project['project_type'] ?? '');
        $projectUrl = htmlspecialchars($project['project_url'] ?? '');
        $demoUrl = htmlspecialchars($project['demo_url'] ?? '');
        $githubUrl = htmlspecialchars($project['github_url'] ?? '');
        $behanceUrl = htmlspecialchars($project['behance_url'] ?? '');
        $caseStudyUrl = htmlspecialchars($project['case_study_url'] ?? '');
        $heroImage = $project['hero_image'] ?? '';
        $createdAt = $project['created_at'] ?? '';
        $projectYear = !empty($project['project_year']) ? (int)$project['project_year'] : (!empty($createdAt) ? (int)date('Y', strtotime($createdAt)) : (int)date('Y'));

        // Lists
        $tags = array_filter(array_map('trim', explode(',', $project['tags'] ?? '')));
        $services = array_filter(array_map('trim', explode(',', $project['services'] ?? '')));
        $technologies = array_filter(array_map('trim', explode(',', $project['technologies'] ?? '')));
        $tools = array_filter(array_map('trim', explode(',', $project['tools'] ?? '')));
        $additionalCats = $project['additional_categories'] ?? [];

        // SEO
        $seoTitle = !empty($project['seo_title']) ? htmlspecialchars($project['seo_title']) : ($title . ' | Nitin Kumar — Portfolio');
        $seoDesc = !empty($project['seo_description']) ? htmlspecialchars($project['seo_description']) : (!empty($summary) ? $summary : htmlspecialchars(mb_substr(strip_tags($description), 0, 160)));

        // Gradients per category
        $gradients = [
            'web-uiux'   => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
            'pharma'     => 'linear-gradient(135deg, #3b0764 0%, #7e22ce 50%, #a855f7 100%)',
            'branding'   => 'linear-gradient(135deg, #18181b 0%, #27272a 50%, #0284c7 100%)',
            'graphics'   => 'linear-gradient(135deg, #451a03 0%, #b45309 50%, #f59e0b 100%)',
            'motion'     => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
            'web-design' => 'linear-gradient(135deg, #064e3b 0%, #059669 50%, #34d399 100%)',
        ];
        $gradient = $gradients[$catSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';

        // High-level default description if empty
        if (empty($description)) {
            if ($catSlug === 'pharma') {
                $description = "Professional pharmaceutical visual communication and medical branding artwork developed with high visual precision and compliance standards. Designed to present therapeutic formulation hierarchy, clinical highlights, and key medical advantages with clean typography, clear diagrams, and pharmaceutical-grade aesthetics.";
            } elseif ($catSlug === 'branding') {
                $description = "Comprehensive brand identity system conceptualized to deliver memorable visual positioning, brand authority, and versatile application across digital platforms and print collateral.";
            } elseif (in_array($catSlug, ['web-uiux', 'web-design'], true)) {
                $description = "Interactive digital user experience and modern web interface design engineered for responsiveness, high-impact aesthetics, and smooth usability across mobile and desktop devices.";
            } else {
                $description = "Tailored graphic design and visual creative asset crafted with modern design standards, balanced composition, and strategic visual communication.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">

    <?php if (empty($pageError)): ?>
        <title><?= $seoTitle ?></title>
        <meta name="description" content="<?= $seoDesc ?>">
        <link rel="canonical" href="https://knitin525.in/project/<?= htmlspecialchars($slug) ?>">
        <meta property="og:title" content="<?= $title ?> | Nitin Kumar">
        <meta property="og:description" content="<?= $seoDesc ?>">
        <?php 
        $ogImg = project_image_url($heroImage);
        if (!empty($ogImg)): 
        ?>
            <meta property="og:image" content="<?= str_starts_with($ogImg, 'http') ? htmlspecialchars($ogImg) : 'https://knitin525.in' . htmlspecialchars($ogImg) ?>">
        <?php endif; ?>
    <?php else: ?>
        <title>Project Not Found | Nitin Kumar</title>
        <meta name="robots" content="noindex">
    <?php endif; ?>

    <meta property="og:type" content="article">
    <link rel="icon" type="image/png" href="/img/favicon.png">

    <!-- Theme Color Meta Tag & Script -->
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

    <!-- Stylesheets with absolute root paths -->
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/projects.css">

    <style>
    /* Custom Enhancement Styles for Single Project Showcase */
    .project-detail-hero__banner {
        border-radius: 16px;
        overflow: hidden;
        position: relative;
        background: <?= $gradient ?? 'linear-gradient(135deg, #0f172a, #1e293b)' ?>;
        min-height: 380px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        text-align: center;
        color: #ffffff;
        box-shadow: 0 12px 36px rgba(0,0,0,0.15);
    }
    .project-detail-hero__banner-badge {
        width: 72px;
        height: 72px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .project-detail-hero__banner-title {
        font-family: var(--font-heading);
        font-size: clamp(1.8rem, 4vw, 2.6rem);
        font-weight: 800;
        margin-bottom: 10px;
        letter-spacing: -0.02em;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        max-width: 800px;
    }
    .project-detail-hero__banner-sub {
        font-size: 1.05rem;
        opacity: 0.9;
        max-width: 620px;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    .project-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .project-action-btn--primary {
        background: var(--primary);
        color: #ffffff;
    }
    .project-action-btn--primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .project-action-btn--secondary {
        background: var(--surface);
        color: var(--text);
        border: 1px solid var(--border);
    }
    .project-action-btn--secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
    }
    .spec-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text);
    }
    </style>
</head>
<body>
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navigation Bar -->
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

    <?php if (!empty($pageError)): ?>
        <!-- 404 Project Not Found -->
        <section class="project-detail-404" style="padding: 140px 0 100px; text-align: center;">
            <div class="container">
                <div style="max-width: 520px; margin: 0 auto;">
                    <div style="font-size: 5rem; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 16px;">404</div>
                    <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text); margin-bottom: 12px;">Project Not Found</h1>
                    <p style="color: var(--text-secondary); line-height: 1.7; margin-bottom: 28px;">The project you are looking for does not exist or may have been updated.</p>
                    <a href="/projects" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Browse All Portfolio Projects
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Breadcrumb Navigation -->
        <section class="project-detail-breadcrumb">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Home</a>
                    <span class="breadcrumb__sep">/</span>
                    <a href="/projects">Projects</a>
                    <span class="breadcrumb__sep">/</span>
                    <a href="/projects?category=<?= htmlspecialchars($catSlug) ?>"><?= $catName ?></a>
                    <span class="breadcrumb__sep">/</span>
                    <span class="breadcrumb__current"><?= $title ?></span>
                </nav>
            </div>
        </section>

        <!-- Project Case Study Hero -->
        <section class="case-study-hero">
            <div class="container">
                <div class="case-study-hero__content">
                    <div class="case-study-hero__meta-top">
                        <span class="case-study-hero__category"><?= $catName ?></span>
                        <?php if (!empty($projectYear)): ?>
                            <span class="case-study-hero__year"><?= $projectYear ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="case-study-hero__title"><?= $title ?></h1>
                    <?php if (!empty($subtitle)): ?>
                        <p class="case-study-hero__subtitle"><?= $subtitle ?></p>
                    <?php elseif (!empty($summary)): ?>
                        <p class="case-study-hero__subtitle"><?= $summary ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Project Metadata Strip -->
        <section class="case-study-meta-strip">
            <div class="container">
                <div class="case-study-meta-strip__grid">
                    <?php if (!empty($catName)): ?>
                        <div class="case-study-meta-strip__item">
                            <span class="case-study-meta-strip__label">Category</span>
                            <span class="case-study-meta-strip__value"><?= $catName ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($clientName)): ?>
                        <div class="case-study-meta-strip__item">
                            <span class="case-study-meta-strip__label">Client</span>
                            <span class="case-study-meta-strip__value"><?= $clientName ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($industry)): ?>
                        <div class="case-study-meta-strip__item">
                            <span class="case-study-meta-strip__label">Industry</span>
                            <span class="case-study-meta-strip__value"><?= $industry ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($projectYear)): ?>
                        <div class="case-study-meta-strip__item">
                            <span class="case-study-meta-strip__label">Year</span>
                            <span class="case-study-meta-strip__value"><?= $projectYear ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($services)): ?>
                        <div class="case-study-meta-strip__item">
                            <span class="case-study-meta-strip__label">Services</span>
                            <span class="case-study-meta-strip__value"><?= htmlspecialchars(implode(', ', array_slice($services, 0, 3))) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Hero Media Showcase -->
        <section class="project-detail-image">
            <div class="container">
                <?php 
                $heroImageUrl = project_image_url($heroImage);
                ?>
                <?php if (!empty($heroImageUrl)): ?>
                    <div class="project-detail-image__wrap">
                        <img src="<?= htmlspecialchars($heroImageUrl) ?>" 
                             alt="<?= $title ?>" 
                             loading="eager" 
                             onerror="this.parentElement.style.display='none'; document.getElementById('projectHeroFallback').style.display='flex';">
                    </div>
                    <!-- Graceful Editorial Fallback if image fails to load -->
                    <div id="projectHeroFallback" class="project-detail-hero__banner" style="display: none;">
                        <div class="project-detail-hero__banner-badge">
                            <?= htmlspecialchars(strtoupper(substr($title, 0, 2))) ?>
                        </div>
                        <h2 class="project-detail-hero__banner-title"><?= $title ?></h2>
                        <?php if (!empty($subtitle) || !empty($industry)): ?>
                            <p class="project-detail-hero__banner-sub"><?= $subtitle ?: $industry ?></p>
                        <?php endif; ?>
                        <div style="display: inline-flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                                <?= $catName ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Editorial Showcase Banner (When no hero image file is uploaded) -->
                    <div class="project-detail-hero__banner">
                        <div class="project-detail-hero__banner-badge">
                            <?= htmlspecialchars(strtoupper(substr($title, 0, 2))) ?>
                        </div>
                        <h2 class="project-detail-hero__banner-title"><?= $title ?></h2>
                        <?php if (!empty($subtitle) || !empty($industry)): ?>
                            <p class="project-detail-hero__banner-sub"><?= $subtitle ?: $industry ?></p>
                        <?php endif; ?>
                        <div style="display: inline-flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                                <?= $catName ?>
                            </span>
                            <?php if (!empty($projectYear)): ?>
                                <span class="badge" style="background: rgba(255,255,255,0.15); color: #fff; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 500;">
                                    Case Study &bull; <?= $projectYear ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Project Detail & Specifications Layout -->
        <section class="project-detail-content">
            <div class="container">
                <div class="project-detail-layout">
                    <!-- Main Narrative -->
                    <div class="project-detail-main">
                        <?php if (!empty($summary)): ?>
                            <p class="project-detail-summary">
                                <?= $summary ?>
                            </p>
                        <?php endif; ?>

                        <div class="project-detail-description" style="font-size: 1.05rem; line-height: 1.9; color: var(--text);">
                            <?= nl2br(htmlspecialchars($description)) ?>
                        </div>

                        <!-- Services & Deliverables -->
                        <?php if (!empty($services)): ?>
                            <div style="margin-top: 36px; padding: 24px; background: var(--surface-alt); border: 1px solid var(--border-light); border-radius: 12px;">
                                <h3 style="font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 14px;">
                                    Services &amp; Scope of Work
                                </h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <?php foreach ($services as $srv): ?>
                                        <span class="spec-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <?= htmlspecialchars($srv) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tools & Technologies -->
                        <?php if (!empty($technologies) || !empty($tools)): ?>
                            <div style="margin-top: 24px; padding: 24px; background: var(--surface-alt); border: 1px solid var(--border-light); border-radius: 12px;">
                                <h3 style="font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 14px;">
                                    Tools &amp; Technologies
                                </h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <?php foreach (array_merge($technologies, $tools) as $tool): ?>
                                        <span class="spec-badge" style="background: var(--surface);">
                                            <?= htmlspecialchars($tool) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tags -->
                        <?php if (!empty($tags)): ?>
                            <div class="project-detail-tags" style="margin-top: 32px;">
                                <h3 class="project-detail-sidebar__label">Project Tags</h3>
                                <div class="project-detail-tags__list">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="project-detail-tag">#<?= htmlspecialchars($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- External Links Bar -->
                        <?php if (!empty($projectUrl) || !empty($demoUrl) || !empty($behanceUrl) || !empty($githubUrl) || !empty($caseStudyUrl)): ?>
                            <div style="margin-top: 36px; display: flex; gap: 12px; flex-wrap: wrap;">
                                <?php if (!empty($projectUrl)): ?>
                                    <a href="<?= $projectUrl ?>" target="_blank" rel="noopener noreferrer" class="project-action-btn project-action-btn--primary">
                                        <span>Visit Live Project</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($demoUrl)): ?>
                                    <a href="<?= $demoUrl ?>" target="_blank" rel="noopener noreferrer" class="project-action-btn project-action-btn--secondary">
                                        <span>Interactive Prototype</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($behanceUrl)): ?>
                                    <a href="<?= $behanceUrl ?>" target="_blank" rel="noopener noreferrer" class="project-action-btn project-action-btn--secondary">
                                        <span>Behance Presentation</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($githubUrl)): ?>
                                    <a href="<?= $githubUrl ?>" target="_blank" rel="noopener noreferrer" class="project-action-btn project-action-btn--secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                                        <span>Source Code</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($caseStudyUrl)): ?>
                                    <a href="<?= $caseStudyUrl ?>" target="_blank" rel="noopener noreferrer" class="project-action-btn project-action-btn--secondary">
                                        <span>Case Study PDF</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sticky Sidebar -->
                    <aside class="project-detail-sidebar">
                        <div style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between;">
                            <span>Project Details</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 700; color: #059669; background: rgba(16, 185, 129, 0.1); padding: 2px 8px; border-radius: 50px;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                                Active
                            </span>
                        </div>

                        <div class="project-detail-sidebar__item">
                            <span class="project-detail-sidebar__label">Primary Category</span>
                            <span class="project-detail-sidebar__value" style="color: var(--primary);">
                                <?= $catName ?>
                            </span>
                        </div>

                        <?php if (!empty($additionalCats)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Additional Categories</span>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">
                                    <?php foreach ($additionalCats as $ac): ?>
                                        <span style="font-size: 0.75rem; background: var(--surface); border: 1px solid var(--border); padding: 2px 8px; border-radius: 50px; color: var(--text-secondary);">
                                            <?= htmlspecialchars($ac['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($clientName)): ?>
                            <div class="project-detail-sidebar__item">
                                <span class="project-detail-sidebar__label">Client / Brand</span>
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
                                <span class="project-detail-sidebar__label">Deliverable Format</span>
                                <span class="project-detail-sidebar__value"><?= $projectType ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="project-detail-sidebar__item">
                            <span class="project-detail-sidebar__label">Release Year</span>
                            <span class="project-detail-sidebar__value"><?= $projectYear ?></span>
                        </div>

                        <div style="margin-top: 24px;">
                            <a href="/#contact" class="btn btn-primary" style="width: 100%; text-align: center; justify-content: center;">
                                Request Similar Design
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <!-- Project Gallery Section -->
        <?php if (!empty($galleryImages)): ?>
            <section class="project-gallery-section" id="gallery">
                <div class="container">
                    <div class="section-header" style="text-align: center; margin-bottom: 36px;">
                        <span class="section-tag">Showcase</span>
                        <h2 class="section-title">Project Gallery &amp; Detail Views</h2>
                        <p class="section-desc">Visual design deliverables, responsive layouts, and close-up views</p>
                    </div>
                    <div class="project-gallery-grid">
                        <?php foreach ($galleryImages as $index => $gImg): 
                            $imgPath = project_image_url($gImg['image_path']);
                            $imgAlt = !empty($gImg['image_alt']) ? htmlspecialchars($gImg['image_alt']) : ($title . ' — Detail ' . ($index + 1));
                        ?>
                            <div class="project-gallery-item" data-src="<?= htmlspecialchars($imgPath) ?>" data-caption="<?= $imgAlt ?>" onclick="openLightbox(this)">
                                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= $imgAlt ?>" loading="lazy">
                                <div class="project-gallery-item__overlay">
                                    <span class="project-gallery-item__caption"><?= $imgAlt ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Previous / Next Project Navigation -->
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
                    <h2 class="projects-section-heading">More Projects You Might Like</h2>
                    <div class="projects-grid projects-grid--related">
                        <?php
                        $relGradients = [
                            'web-uiux'   => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
                            'pharma'     => 'linear-gradient(135deg, #3b0764 0%, #7e22ce 50%, #a855f7 100%)',
                            'branding'   => 'linear-gradient(135deg, #18181b 0%, #27272a 50%, #0284c7 100%)',
                            'graphics'   => 'linear-gradient(135deg, #451a03 0%, #b45309 50%, #f59e0b 100%)',
                            'motion'     => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
                            'web-design' => 'linear-gradient(135deg, #064e3b 0%, #059669 50%, #34d399 100%)',
                        ];
                        foreach ($relatedProjects as $rel):
                            $relSlug = htmlspecialchars($rel['primary_category_slug'] ?? '');
                            $relGrad = $relGradients[$relSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';
                            $relTitle = htmlspecialchars($rel['title'] ?? '');
                            $relHero = $rel['hero_image'] ?? '';
                            $relHeroUrl = project_image_url($relHero);
                        ?>
                            <a href="/project/<?= htmlspecialchars($rel['slug'] ?? '') ?>" class="related-project-card">
                                <?php if (!empty($relHeroUrl)): ?>
                                    <div class="related-project-card__image">
                                        <img src="<?= htmlspecialchars($relHeroUrl) ?>" 
                                             alt="<?= $relTitle ?>" 
                                             loading="lazy" 
                                             onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                        <span class="related-project-card__placeholder" style="display: none; background: <?= $relGrad ?>;"><?= $relTitle ?></span>
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

        <!-- Call to Action -->
        <section class="project-detail-cta">
            <div class="container" style="text-align: center; padding: 70px 20px;">
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--text); margin-bottom: 12px; letter-spacing: -0.02em;">
                    Have a Vision for Your Brand or Product?
                </h2>
                <p style="color: var(--text-secondary); max-width: 520px; margin: 0 auto 28px; line-height: 1.7;">
                    Let's collaborate to create design solutions that elevate your brand and deliver measurable impact.
                </p>
                <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                    <a href="/#contact" class="btn btn-primary" style="padding: 12px 28px; font-weight: 600;">
                        Start a Conversation
                    </a>
                    <a href="/projects" class="btn btn-secondary" style="padding: 12px 24px;">
                        Explore More Work
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Site Footer -->
    <footer class="footer" itemscope itemtype="https://schema.org/WPFooter">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <img src="/img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
                    </a>
                    <p>Professional graphic design, UI/UX, and web development services in Chandigarh.</p>
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
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Knitin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Lightbox Modal for Gallery Images -->
    <div class="lightbox-modal" id="lightboxModal" role="dialog" aria-hidden="true">
        <button class="lightbox-modal__close" id="lightboxClose" aria-label="Close lightbox" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <button class="lightbox-modal__prev" id="lightboxPrev" aria-label="Previous image" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="lightbox-modal__next" id="lightboxNext" aria-label="Next image" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
        <div class="lightbox-modal__content">
            <img src="" alt="" class="lightbox-modal__image" id="lightboxImg">
            <div class="lightbox-modal__caption" id="lightboxCaption"></div>
        </div>
    </div>

    <!-- Global JavaScript with absolute path -->
    <script src="/js/main.js"></script>

    <!-- Lightbox Script -->
    <script>
    (function() {
        const modal = document.getElementById('lightboxModal');
        if (!modal) return;
        const img = document.getElementById('lightboxImg');
        const caption = document.getElementById('lightboxCaption');
        const closeBtn = document.getElementById('lightboxClose');
        const prevBtn = document.getElementById('lightboxPrev');
        const nextBtn = document.getElementById('lightboxNext');
        const items = Array.from(document.querySelectorAll('.project-gallery-item'));
        let currentIndex = 0;

        function showImage(index) {
            if (index < 0) index = items.length - 1;
            if (index >= items.length) index = 0;
            currentIndex = index;
            const item = items[currentIndex];
            img.src = item.dataset.src;
            img.alt = item.dataset.caption || '';
            caption.textContent = item.dataset.caption || '';
        }

        window.openLightbox = function(el) {
            currentIndex = items.indexOf(el);
            if (currentIndex === -1) currentIndex = 0;
            showImage(currentIndex);
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        function closeLightbox() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            img.src = '';
        }

        closeBtn.addEventListener('click', closeLightbox);
        prevBtn.addEventListener('click', function(e) { e.stopPropagation(); showImage(currentIndex - 1); });
        nextBtn.addEventListener('click', function(e) { e.stopPropagation(); showImage(currentIndex + 1); });
        modal.addEventListener('click', function(e) { if (e.target === modal) closeLightbox(); });

        document.addEventListener('keydown', function(e) {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
            if (e.key === 'ArrowRight') showImage(currentIndex + 1);
        });
    })();
    </script>
</body>
</html>
