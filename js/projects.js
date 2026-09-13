/**
 * Projects Showcase - Interactive Filtering, Live Search & Modal Preview
 * Nitin Kumar Portfolio (knitin525.in)
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // DOM Elements
    const filterButtons = document.querySelectorAll('.projects-filter-btn');
    const searchInput = document.getElementById('projectSearchInput');
    const searchClear = document.getElementById('projectSearchClear');
    const allCards = document.querySelectorAll('.project-card');
    const featuredHeading = document.getElementById('featuredHeading');
    const featuredGrid = document.getElementById('featuredGrid');
    const directoryGrid = document.getElementById('directoryGrid');
    const noResults = document.getElementById('projectsNoResults');
    const directoryCount = document.getElementById('directoryCount');

    // Modal Elements
    const modalBackdrop = document.getElementById('projectModalBackdrop');
    const modalCloseBtn = document.getElementById('projectModalClose');
    const modalImageWrap = document.getElementById('modalImageWrap');
    const modalCategory = document.getElementById('modalCategory');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalTags = document.getElementById('modalTags');
    const modalRole = document.getElementById('modalRole');
    const modalFocus = document.getElementById('modalFocus');

    // State
    let activeFilter = 'all';
    let searchQuery = '';

    // Category Map for display titles & default roles
    const categoryMeta = {
        'web-uiux': {
            label: 'Web & UI/UX',
            role: 'UI/UX Design, Front-End & Responsive Strategy',
            focus: 'Enterprise, SaaS & Commercial Web'
        },
        'pharma': {
            label: 'Pharma Design',
            role: 'Packaging Design, Visual Aids & Compliance',
            focus: 'Pharmaceutical & Healthcare Brands'
        },
        'branding': {
            label: 'Branding & Logo',
            role: 'Visual Identity, Logo Design & Brand Systems',
            focus: 'Corporate, Retail & Product Identity'
        },
        'graphics': {
            label: 'Graphic Design',
            role: 'Infographics, Print Collateral & Social Media',
            focus: 'Marketing Campaigns & Visual Assets'
        },
        'motion': {
            label: 'Motion & Video',
            role: 'Motion Graphics, Video Editing & Promotional Promos',
            focus: 'Digital Media & Product Marketing'
        }
    };

    /**
     * Update Dynamic Category Counts
     */
    function updateCounts() {
        const counts = {
            'all': allCards.length,
            'web-uiux': 0,
            'pharma': 0,
            'branding': 0,
            'graphics': 0,
            'motion': 0
        };

        allCards.forEach(card => {
            const cat = card.dataset.category;
            if (counts[cat] !== undefined) {
                counts[cat]++;
            }
        });

        // Update badge DOM
        const countMap = {
            'countAll': counts['all'],
            'countWeb': counts['web-uiux'],
            'countPharma': counts['pharma'],
            'countBranding': counts['branding'],
            'countGraphics': counts['graphics'],
            'countMotion': counts['motion']
        };

        Object.entries(countMap).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });
    }

    /**
     * Filter and Search Evaluation
     */
    function applyFilterAndSearch() {
        const query = searchQuery.trim().toLowerCase();
        let totalVisible = 0;
        let featuredVisible = 0;
        let directoryVisible = 0;

        allCards.forEach(card => {
            const cat = card.dataset.category || '';
            const title = (card.dataset.title || '').toLowerCase();
            const industry = (card.dataset.industry || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();
            const tags = (card.dataset.tags || '').toLowerCase();

            // Check Category Match
            const matchesCategory = (activeFilter === 'all') || (cat === activeFilter);

            // Check Search Query Match
            const matchesSearch = !query ||
                title.includes(query) ||
                industry.includes(query) ||
                desc.includes(query) ||
                tags.includes(query);

            const isVisible = matchesCategory && matchesSearch;

            if (isVisible) {
                card.style.display = '';
                card.classList.remove('is-hidden');
                totalVisible++;

                if (card.closest('#featuredGrid')) {
                    featuredVisible++;
                } else {
                    directoryVisible++;
                }
            } else {
                card.style.display = 'none';
                card.classList.add('is-hidden');
            }
        });

        // Handle Featured Grid Visibility
        // Hide featured section when category is not 'all' or 'web-uiux' (since featured are web-uiux), or if no featured cards match query
        if (featuredHeading && featuredGrid) {
            if (featuredVisible > 0) {
                featuredHeading.style.display = '';
                featuredGrid.style.display = '';
            } else {
                featuredHeading.style.display = 'none';
                featuredGrid.style.display = 'none';
            }
        }

        // Update Directory Counter Badge
        if (directoryCount) {
            directoryCount.textContent = `${directoryVisible} Projects`;
        }

        // Show/Hide No Results State
        if (noResults) {
            if (totalVisible === 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
    }

    /**
     * Category Filter Buttons
     */
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            activeFilter = btn.dataset.filter || 'all';
            applyFilterAndSearch();
        });
    });

    /**
     * Live Search Input Handler
     */
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            if (searchClear) {
                if (searchQuery) {
                    searchClear.classList.add('show');
                    searchClear.style.display = 'block';
                } else {
                    searchClear.classList.remove('show');
                    searchClear.style.display = 'none';
                }
            }
            applyFilterAndSearch();
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchQuery = '';
                searchClear.classList.remove('show');
                searchClear.style.display = 'none';
                applyFilterAndSearch();
                searchInput.focus();
            });
        }
    }

    /**
     * Project Modal Dialog
     */
    function openModal(card) {
        if (!modalBackdrop || !card) return;

        const title = card.dataset.title || 'Project Detail';
        const cat = card.dataset.category || 'web-uiux';
        const industry = card.dataset.industry || '';
        const desc = card.dataset.desc || '';
        const tags = (card.dataset.tags || '').split(',').map(t => t.trim()).filter(Boolean);

        const meta = categoryMeta[cat] || {
            label: 'Project Showcase',
            role: 'Design & Development',
            focus: 'Custom Solution'
        };

        // Populate Modal Fields
        if (modalTitle) modalTitle.textContent = title;
        if (modalCategory) modalCategory.textContent = meta.label;
        if (modalSubtitle) modalSubtitle.textContent = industry || meta.focus;
        if (modalDesc) modalDesc.textContent = desc;
        if (modalRole) modalRole.textContent = meta.role;
        if (modalFocus) modalFocus.textContent = industry || meta.focus;

        // Tags
        if (modalTags) {
            modalTags.innerHTML = tags.map(tag => `<span class="project-modal__tag">${tag}</span>`).join('');
        }

        // Image / Media clone
        if (modalImageWrap) {
            modalImageWrap.innerHTML = '';
            const cardImgWrap = card.querySelector('.project-card__image');
            if (cardImgWrap) {
                const clone = cardImgWrap.cloneNode(true);
                modalImageWrap.appendChild(clone);
            }
        }

        // Display Modal
        modalBackdrop.classList.add('show');
        modalBackdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('show');
        modalBackdrop.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Attach click listeners to cards' "View Project" buttons
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.open-modal-btn');
        if (trigger) {
            const card = trigger.closest('.project-card');
            if (card) {
                openModal(card);
            }
        }
    });

    // Close button click
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    // Click outside modal dialog to dismiss
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) {
                closeModal();
            }
        });
    }

    // ESC key listener to dismiss modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('is-open')) {
            closeModal();
        }
    });

    // Handle Image Error Graceful Fallback
    const projectImages = document.querySelectorAll('.project-card__image img');
    projectImages.forEach(img => {
        img.addEventListener('error', function() {
            const card = this.closest('.project-card');
            const title = card ? card.dataset.title : 'Project Showcase';
            const parent = this.parentElement;
            if (parent) {
                parent.innerHTML = `
                    <div class="project-card__placeholder" style="background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #0f172a 100%);">
                        <span class="project-card__placeholder-title">${title}</span>
                    </div>
                `;
            }
        });
    });

    // Initial calculation
    updateCounts();
    applyFilterAndSearch();
});