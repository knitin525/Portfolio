<?php
/**
 * Knitin Portfolio Ã¢â‚¬â€ Dynamic Homepage
 * Fetches featured projects from MySQL database via ProjectService.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/ProjectService.php';

$projectService = new ProjectService();

// Fetch featured published projects for homepage (max 6)
$featuredProjects = $projectService->getProjects([
    'status' => 'published',
    'is_featured' => 1,
    'limit' => 6,
]);

// Fallback: if no featured projects, show latest published projects
if (empty($featuredProjects)) {
    $featuredProjects = $projectService->getProjects([
        'status' => 'published',
        'limit' => 6,
    ]);
}

// Get categories that have published projects (for filter buttons)
$publicCategories = $projectService->getPublishedCategories();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <title>Nitin Kumar | Graphic Designer & Web Developer | 12+ Years Experience</title>
    <meta name="title" content="Nitin Kumar | Graphic Designer & Web Developer | 12+ Years Experience">
    <meta name="description"
        content="Professional freelance graphic designer and web developer in Chandigarh. 12+ years experience in UI/UX design, web development, branding, and logo design. 500+ projects completed. Call today for a free consultation.">
    <meta name="keywords"
        content="graphic designer, web developer, UI/UX designer, freelance designer, logo design, branding, website development, Chandigarh, portfolio, logo designer, web designer, logo maker">
    <meta name="author" content="Nitin Kumar">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://knitin525.in/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://knitin525.in/">
    <meta property="og:title" content="Nitin Kumar | Graphic Designer & Web Developer">
    <meta property="og:description"
        content="Professional freelance graphic designer and web developer with 12+ years of experience. Specializing in UI/UX design, branding, web development, and logo design.">
    <meta property="og:image" content="https://knitin525.in/img/og-image.jpg">
    <meta property="og:site_name" content="Knitin Portfolio">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://knitin525.in/">
    <meta property="twitter:title" content="Nitin Kumar | Graphic Designer & Web Developer">
    <meta property="twitter:description"
        content="Professional freelance graphic designer and web developer with 12+ years of experience.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">

    <!-- Theme Color Meta Tag & Anti-Flash Theme Script -->
    <meta name="theme-color" content="#fafbfc" id="metaThemeColor">
    <script>
        (function() {
            try {
                const saved = localStorage.getItem('portfolio-theme');
                const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = saved || (systemDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                const metaColor = document.getElementById('metaThemeColor');
                if (metaColor) {
                    metaColor.setAttribute('content', theme === 'dark' ? '#0B0D10' : '#fafbfc');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": "Nitin Kumar",
        "url": "https://knitin525.in",
        "jobTitle": "Graphic Designer & Web Developer",
        "description": "Professional freelance graphic designer and web developer with 12+ years of experience in UI/UX design, web development, branding, and logo design.",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Chandigarh",
            "addressRegion": "Punjab",
            "addressCountry": "IN"
        },
        "email": "mailto:knitin525@gmail.com",
        "telephone": "+91-9876543210",
        "image": "https://knitin525.in/img/about-profile-pik.webp",
        "sameAs": [
            "https://github.com/knitin",
            "https://linkedin.com/in/knitin",
            "https://twitter.com/knitin"
        ],
        "worksFor": {
            "@type": "Organization",
            "name": "Freelance"
        },
        "alumniOf": {
            "@type": "EducationalOrganization",
            "name": "Design Institute"
        },
        "knowsAbout": ["Graphic Design", "Web Development", "UI/UX Design", "Logo Design", "Branding", "WordPress", "HTML", "CSS", "JavaScript", "Adobe Photoshop", "Adobe Illustrator", "Figma"]
    }
    </script>

    <!-- Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfessionalService",
        "name": "Knitin Design Studio",
        "image": "https://knitin525.in/img/about-profile-pik.webp",
        "url": "https://knitin525.in",
        "telephone": "+91-9876543210",
        "email": "knitin525@gmail.com",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Sector 20",
            "addressLocality": "Panchkula",
            "addressRegion": "Chandigarh",
            "postalCode": "134116",
            "addressCountry": "IN"
        },
        "priceRange": "$$",
        "openingHours": "Mo-Fr 09:00-18:00",
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": "30.6900",
            "longitude": "76.8500"
        },
        "serviceType": ["Graphic Design", "Web Development", "UI/UX Design", "Logo Design", "Branding", "Motion Graphics"]
    }
    </script>
</head>

<body>
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="#" class="logo">
                <img src="img/Logo knitin.png" alt="Knitin - Create A Better Tomorrow" class="logo-img">
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#portfolio">Work</a></li>
                <li><a href="#skills">Skills</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-actions">
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
                <div class="nav-cta">
                    <a href="#contact" class="btn btn-primary">Let's Talk</a>
                </div>
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
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#portfolio">Work</a></li>
            <li><a href="#skills">Skills</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg">
            <div class="hero-gradient"></div>
            <div class="hero-pattern"></div>
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        <div class="container hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    Available for Projects
                </div>
                <h1 class="hero-title">
                    <span class="title-line">Hello, I'm</span>
                    <span class="title-name">Nitin Kumar</span>
                </h1>
                <div class="hero-subtitle">
                    <span class="subtitle-text">Creative Designer</span>
                    <span class="subtitle-divider">&</span>
                    <span class="subtitle-text">Web Developer</span>
                </div>
                <p class="hero-description">
                    Crafting digital experiences that inspire, engage, and deliver results.
                    With 12+ years of expertise in transforming ideas into stunning visual realities.
                </p>
                <div class="hero-ctas">
                    <a href="#portfolio" class="btn btn-primary btn-lg">
                        View My Work
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <a href="#contact" class="btn btn-outline btn-lg">Get In Touch</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">12+</span>
                        <span class="stat-label">Years Experience</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Projects Done</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">200+</span>
                        <span class="stat-label">Happy Clients</span>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="profile-frame">
                    <div class="profile-image">
                        <img src="img\about img.webp" alt="Nitin Kumar">
                    </div>
                    <div class="frame-accent"></div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <span>Scroll</span>
            <div class="scroll-line"></div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-v2" id="about" itemscope itemtype="https://schema.org/AboutPage">
        <div class="about-v2__bg-accent"></div>
        <div class="container">
            <div class="about-v2__grid">
                <!-- Left Column -->
                <div class="about-v2__left">
                    <div class="about-v2__header">
                        <span class="about-v2__tag">
                            <span class="about-v2__tag-dot"></span>
                            About Me
                        </span>
                        <h2 class="about-v2__title">
                            Where Design<br>
                            Meets <span class="about-v2__title-accent">Development</span>
                        </h2>
                        <div class="about-v2__divider">
                            <span class="about-v2__divider-line"></span>
                            <span class="about-v2__divider-diamond"></span>
                            <span class="about-v2__divider-line"></span>
                        </div>
                        <p class="about-v2__subtitle">Blending creative design with modern web development to build meaningful digital experiences.</p>
                    </div>

                    <div class="about-v2__body">
                        <p class="about-v2__lead">
                            I'm a passionate <strong>graphic designer, web developer, and UI/UX designer</strong> based
                            in Chandigarh, specializing in creating stunning <strong>logo designs, brand identities, and
                            professional websites</strong> that make a lasting impact on your business.
                        </p>
                        <p>
                            Over the past <strong>12+ years</strong>, I've had the privilege of working with diverse
                            clients across healthcare, education, e-commerce, pharmaceutical, and corporate sectors. My
                            approach combines creative excellence with strategic thinking, ensuring every project not
                            only looks beautiful but delivers <strong>measurable business results</strong>.
                        </p>
                        <p>
                            As a professional <strong>freelance designer</strong>, I offer comprehensive digital
                            solutions including <strong>logo design, website development, graphic design, motion
                            graphics, and branding services</strong>. From conceptualization to execution, I bring a
                            holistic understanding of design principles, user experience, and modern web technologies to
                            create solutions that stand out in today's competitive landscape.
                        </p>
                    </div>

                    <!-- Highlight Pills -->
                    <div class="about-v2__pills">
                        <div class="about-v2__pill">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>12+ Years Experience</span>
                        </div>
                        <div class="about-v2__pill">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Graphic &amp; Web Design</span>
                        </div>
                        <div class="about-v2__pill">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>UI/UX &amp; Front-End Development</span>
                        </div>
                        <div class="about-v2__pill">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Pharma Branding &amp; Digital Solutions</span>
                        </div>
                    </div>

                    <div class="about-v2__cta">
                        <a href="#" class="btn btn-primary" aria-label="Download CV">
                            Download CV
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </a>
                        <a href="/projects" class="link-arrow" aria-label="View all projects">
                            View Portfolio
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="about-v2__right">
                    <div class="about-v2__image-container">
                        <div class="about-v2__image-frame">
                            <img src="img/about profile pik.webp" alt="Nitin Kumar - Graphic Designer & Web Developer in Chandigarh" loading="lazy">
                        </div>
                        <div class="about-v2__image-border"></div>
                        <div class="about-v2__image-glow"></div>
                    </div>

                    <!-- Floating Stats -->
                    <div class="about-v2__stats">
                        <div class="about-v2__stat-card about-v2__stat-card--projects">
                            <span class="about-v2__stat-number">500+</span>
                            <span class="about-v2__stat-label">Projects Delivered</span>
                        </div>
                        <div class="about-v2__stat-card about-v2__stat-card--clients">
                            <span class="about-v2__stat-number">200+</span>
                            <span class="about-v2__stat-label">Happy Clients</span>
                        </div>
                        <div class="about-v2__stat-card about-v2__stat-card--experience">
                            <span class="about-v2__stat-number">12+</span>
                            <span class="about-v2__stat-label">Years of Excellence</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">What I Do</span>
                <h2 class="section-title">Services I Offer</h2>
                <p class="section-desc">Delivering comprehensive digital solutions tailored to your unique needs</p>
            </div>
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3 class="service-title">UI/UX Design</h3>
                    <p class="service-desc">Creating intuitive, user-centered interfaces that balance aesthetics with
                        functionality for seamless digital experiences.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <h3 class="service-title">Web Development</h3>
                    <p class="service-desc">Building responsive, high-performance websites using modern technologies for
                        optimal user engagement.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"></path>
                        </svg>
                    </div>
                    <h3 class="service-title">Graphic Design</h3>
                    <p class="service-desc">Crafting compelling visual identities that communicate your brand story
                        effectively across all platforms.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"></path>
                        </svg>
                    </div>
                    <h3 class="service-title">Motion Graphics</h3>
                    <p class="service-desc">Bringing ideas to life through captivating animations and video content that
                        engage and convert.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </div>
                    <h3 class="service-title">SEO & Marketing</h3>
                    <p class="service-desc">Amplifying your digital presence with data-driven strategies that drive
                        traffic and generate leads.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="600">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon
                                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                            </polygon>
                        </svg>
                    </div>
                    <h3 class="service-title">Branding</h3>
                    <p class="service-desc">Building memorable brand experiences with strategic identity design that
                        resonates with your audience.</p>
                    <div class="service-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio / Work Section Ã¢â‚¬â€ Dynamic from Database -->
    <section class="work-v2" id="portfolio">
        <div class="container">
            <div class="work-v2__header">
                <span class="work-v2__tag">
                    <span class="work-v2__tag-dot"></span>
                    My Work
                </span>
                <h2 class="work-v2__title">Selected Work &amp; Creative Projects</h2>
                <div class="work-v2__divider">
                    <span class="work-v2__divider-line"></span>
                    <span class="work-v2__divider-diamond"></span>
                    <span class="work-v2__divider-line"></span>
                </div>
                <p class="work-v2__desc">Selected projects across web, UI/UX, branding, pharmaceutical design and digital experiences.</p>
            </div>

            <!-- Filter Bar Ã¢â‚¬â€ Dynamic from Database -->
            <div class="work-v2__filters-wrap">
                <div class="work-v2__filters">
                    <button class="work-v2__filter-btn active" data-filter="all" type="button">All</button>
                    <?php foreach ($publicCategories as $cat): ?>
                        <button class="work-v2__filter-btn" data-filter="<?= htmlspecialchars($cat['slug']) ?>" type="button"><?= htmlspecialchars($cat['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Project Grid Ã¢â‚¬â€ Dynamic from Database -->
            <div class="work-v2__grid">
                <?php if (empty($featuredProjects)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <p style="font-size: 1.1rem; margin-bottom: 8px;">No projects published yet.</p>
                        <p style="font-size: 0.9rem;">Projects added from the admin dashboard will appear here automatically.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($featuredProjects as $i => $proj):
                        $isFeaturedCard = ($i === 0 && !empty($proj['is_featured']));
                        $catSlug = htmlspecialchars($proj['primary_category_slug'] ?? '');
                        $catName = htmlspecialchars($proj['primary_category_name'] ?? '');
                        $title = htmlspecialchars($proj['title'] ?? '');
                        $desc = htmlspecialchars($proj['summary'] ?? $proj['description'] ?? '');
                        $slug = htmlspecialchars($proj['slug'] ?? '');
                        $heroImage = $proj['hero_image'] ?? '';
                        $tags = array_filter(array_map('trim', explode(',', $proj['tags'] ?? '')));
                        // Build gradient placeholder colors based on category
                        $gradients = [
                            'web-uiux'  => 'linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%)',
                            'pharma'    => 'linear-gradient(135deg, #581c87 0%, #9333ea 50%, #c084fc 100%)',
                            'branding'  => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
                            'graphics'  => 'linear-gradient(135deg, #78350f 0%, #d97706 50%, #fbbf24 100%)',
                            'motion'    => 'linear-gradient(135deg, #450a0a 0%, #dc2626 50%, #f87171 100%)',
                        ];
                        $gradient = $gradients[$catSlug] ?? 'linear-gradient(135deg, #0f172a 0%, #334155 50%, #1e293b 100%)';
                    ?>
                        <article class="work-v2__card<?= $isFeaturedCard ? ' work-v2__card--featured' : '' ?>" data-category="<?= $catSlug ?>">
                            <?php if (!empty($heroImage)): ?>
                                <div class="work-v2__card-image">
                                    <img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= $title ?> Ã¢â‚¬â€ Project by Nitin Kumar" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="work-v2__card-image work-v2__card-image--placeholder" style="background: <?= $gradient ?>;">
                                    <span class="work-v2__placeholder-text"><?= $title ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="work-v2__card-body">
                                <span class="work-v2__card-category"><?= $catName ?></span>
                                <h3 class="work-v2__card-title"><?= $title ?></h3>
                                <p class="work-v2__card-desc"><?= $desc ?></p>
                                <?php if (!empty($tags)): ?>
                                    <div class="work-v2__card-tags">
                                        <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                            <span><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <a href="/project/<?= $slug ?>" class="work-v2__card-link">
                                    View Project
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="work-v2__cta">
                <a href="/projects" class="btn btn-secondary">
                    View All Projects
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>
    </section>


    <!-- Skills Section -->
    <section class="skills" id="skills">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Expertise</span>
                <h2 class="section-title">Skills & Technologies</h2>
                <p class="section-desc">Tools and technologies I use to bring your vision to life</p>
            </div>
            <div class="skills-tabs">
                <button class="skill-tab active" data-tab="design">Design</button>
                <button class="skill-tab" data-tab="development">Development</button>
                <button class="skill-tab" data-tab="tools">Tools</button>
            </div>
            <div class="skills-content">
                <div class="skill-category active" data-category="design">
                    <div class="skill-grid">
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Adobe Photoshop</span>
                                <span class="skill-percent">95%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 95%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Adobe Illustrator</span>
                                <span class="skill-percent">90%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 90%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Figma</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">After Effects</span>
                                <span class="skill-percent">80%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 80%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Premiere Pro</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">CorelDRAW</span>
                                <span class="skill-percent">90%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 90%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="skill-category" data-category="development">
                    <div class="skill-grid">
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">HTML5/CSS3</span>
                                <span class="skill-percent">95%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 95%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">JavaScript</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">WordPress</span>
                                <span class="skill-percent">90%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 90%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">PHP</span>
                                <span class="skill-percent">80%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 80%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Bootstrap</span>
                                <span class="skill-percent">95%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 95%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Tailwind CSS</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="skill-category" data-category="tools">
                    <div class="skill-grid">
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Git/GitHub</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">VS Code</span>
                                <span class="skill-percent">95%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 95%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">SEO</span>
                                <span class="skill-percent">80%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 80%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Analytics</span>
                                <span class="skill-percent">85%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 85%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">MySQL</span>
                                <span class="skill-percent">75%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 75%"></div>
                            </div>
                        </div>
                        <div class="skill-item">
                            <div class="skill-info">
                                <span class="skill-name">Laravel</span>
                                <span class="skill-percent">75%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Testimonials</span>
                <h2 class="section-title">Client Feedback</h2>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <p class="testimonial-text">Nitin transformed our online presence completely. The website design
                        exceeded our expectations and the results speak for themselves - our conversions increased by
                        45% within the first month.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">SJ</div>
                        <div class="author-info">
                            <h4>Sarah Johnson</h4>
                            <span>CEO, FJ Group Africa</span>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <p class="testimonial-text">Exceptional work on our pharmaceutical packaging. Nitin understood our
                        requirements perfectly and delivered designs that are both regulatory-compliant and visually
                        striking.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RK</div>
                        <div class="author-info">
                            <h4>Dr. Rajesh Kumar</h4>
                            <span>Director, DRT Lifesciences</span>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-quote">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <p class="testimonial-text">Working with Nitin was a pleasure. He delivered our college website on
                        time with all the features we needed. The design is modern, user-friendly, and our prospective
                        students love it.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">NZ</div>
                        <div class="author-info">
                            <h4>Norman Zboray</h4>
                            <span>Director, On Point Prep College</span>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <span class="section-tag">Get In Touch</span>
                    <h2 class="section-title">Let's Work Together</h2>
                    <p class="contact-desc">Have a project in mind? I'd love to hear about it. Let's discuss how we can
                        bring your vision to life.</p>
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                    </path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                            <div class="contact-text">
                                <span class="contact-label">Email</span>
                                <a href="mailto:knitin525@gmail.com">knitin525@gmail.com</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <div class="contact-text">
                                <span class="contact-label">Location</span>
                                <span>Panchkula, Chandigarh</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="contact-text">
                                <span class="contact-label">Availability</span>
                                <span>Open for freelance projects</span>
                            </div>
                        </div>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="GitHub">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                            </svg>
                        </a>
                        <a href="#" class="social-link" aria-label="LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                            </svg>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                        <a href="mailto:knitin525@gmail.com" class="social-link" aria-label="Email">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                </path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="contact-form-wrapper">
                    <div class="form-error-banner" id="formError"></div>
                    <form class="contact-form" id="contactForm" enctype="multipart/form-data">
                        <!-- Security CSRF & Honeypot -->
                        <input type="hidden" name="csrf_token" id="csrfToken" value="">
                        <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your Name *</label>
                                <input type="text" id="name" name="name" placeholder="John Doe" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" placeholder="john@example.com" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number <span style="font-weight: 400; color: var(--text-secondary);">(Optional)</span></label>
                                <input type="tel" id="phone" name="phone" placeholder="+91 98765 43210">
                            </div>
                            <div class="form-group">
                                <label for="company">Company / Organization <span style="font-weight: 400; color: var(--text-secondary);">(Optional)</span></label>
                                <input type="text" id="company" name="company" placeholder="e.g. Acme Corp">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="service">Service Interested In</label>
                                <select id="service" name="service">
                                    <option value="Web Design & Development">Web Design &amp; Development</option>
                                    <option value="UI/UX Design">UI/UX Design</option>
                                    <option value="Pharma Packaging & Visual Aids">Pharma Packaging &amp; Visual Aids</option>
                                    <option value="Branding & Identity">Branding &amp; Identity</option>
                                    <option value="Graphic Design & Creatives">Graphic Design &amp; Creatives</option>
                                    <option value="Motion & Video Graphics">Motion &amp; Video Graphics</option>
                                    <option value="Other Project Inquiry">Other Project Inquiry</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" placeholder="Project Inquiry">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Your Message *</label>
                            <textarea id="message" name="message" placeholder="Tell me about your project, requirements, or timeline..."
                                required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="attachment">Attach Project Brief or File <span style="font-weight: 400; color: var(--text-secondary);">(Optional)</span></label>
                            <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.zip">
                            <div class="form-file-hint">Supported: PDF, JPG, PNG, DOCX, ZIP (Max 10 MB)</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" id="contactSubmitBtn">
                            Send Message
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </form>
                    <div class="form-success" id="formSuccess">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <h3>Message Sent Successfully!</h3>
                        <p id="formSuccessMessage">Thank you for reaching out. Nitin Kumar has received your message and will review it shortly.</p>
                    </div>
                </div>
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
                    <p>Professional graphic design and web development services in Chandigarh. Creating digital
                        experiences that inspire and deliver results.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="#home">Home</a></li>
                            <li><a href="#about">About</a></li>
                            <li><a href="#services">Services</a></li>
                            <li><a href="#portfolio">Work</a></li>
                            <li><a href="/projects">All Projects</a></li>
                            <li><a href="faq.html">FAQ</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Services</h4>
                        <ul>
                            <li><a href="#services">UI/UX Design</a></li>
                            <li><a href="#services">Web Development</a></li>
                            <li><a href="#services">Graphic Design</a></li>
                            <li><a href="#services">Branding</a></li>
                            <li><a href="#services">Logo Design</a></li>
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
                <p>&copy; 2024 Knitin. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#" aria-label="GitHub">GitHub</a>
                    <a href="#" aria-label="LinkedIn">LinkedIn</a>
                    <a href="#" aria-label="Twitter">Twitter</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>

</html>