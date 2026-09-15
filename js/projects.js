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
    const modalAdditionalWrap = document.getElementById('modalAdditionalWrap');
    const modalAdditionalCategories = document.getElementById('modalAdditionalCategories');
    const modalRelatedGrid = document.getElementById('modalRelatedGrid');

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
     * Update Dynamic Category Counts (Considers Primary + Additional Categories)
     */
    function updateCounts() {
        // Dynamically gather categories from filter buttons
        const counts = {};
        filterButtons.forEach(btn => {
            const filter = btn.dataset.filter || 'all';
            counts[filter] = 0;
        });
        counts['all'] = allCards.length;

        allCards.forEach(card => {
            const primaryCat = card.dataset.category || '';
            const additionals = (card.dataset.additionalCategories || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);

            Object.keys(counts).forEach(key => {
                if (key === 'all') return;
                if (primaryCat === key || additionals.includes(key)) {
                    counts[key]++;
                }
            });
        });

        // Update count badges on filter buttons
        filterButtons.forEach(btn => {
            const filter = btn.dataset.filter || 'all';
            const badge = btn.querySelector('.projects-filter-count');
            if (badge && counts[filter] !== undefined) {
                badge.textContent = counts[filter];
            }
        });

        // Also update the countAll element if it exists
        const countAllEl = document.getElementById('countAll');
        if (countAllEl) countAllEl.textContent = counts['all'];
    }

    /**
     * Filter and Search Evaluation (Supports Primary + Additional Categories)
     */
    function applyFilterAndSearch() {
        const query = searchQuery.trim().toLowerCase();
        let totalVisible = 0;
        let featuredVisible = 0;
        let directoryVisible = 0;

        allCards.forEach(card => {
            const primaryCat = card.dataset.category || '';
            const additionals = (card.dataset.additionalCategories || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
            const title = (card.dataset.title || '').toLowerCase();
            const industry = (card.dataset.industry || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();
            const tags = (card.dataset.tags || '').toLowerCase();

            // Check Category Match: Appears in Primary Category OR any Additional Category
            const matchesCategory = (activeFilter === 'all') ||
                (primaryCat === activeFilter) ||
                additionals.includes(activeFilter);

            // Check Search Query Match
            const matchesSearch = !query ||
                title.includes(query) ||
                industry.includes(query) ||
                desc.includes(query) ||
                tags.includes(query) ||
                additionals.some(a => a.includes(query));

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
     * Calculate 3 Related Projects using the 4-level priority score
     * Priority:
     * 1. Same Primary Category (+100)
     * 2. Shared Additional Categories (+30 each)
     * 3. Shared Tags (+10 each)
     * 4. Similar Project Type / Industry (+15)
     */
    function getRelatedCards(currentCard, limit = 3) {
        const currentTitle = (currentCard.dataset.title || '').trim();
        const currentPrimary = currentCard.dataset.category || '';
        const currentAdditionals = (currentCard.dataset.additionalCategories || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
        const currentTags = (currentCard.dataset.tags || '').split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
        const currentIndustry = (currentCard.dataset.industry || '').trim().toLowerCase();

        const candidates = [];

        allCards.forEach(otherCard => {
            const otherTitle = (otherCard.dataset.title || '').trim();
            if (!otherTitle || otherTitle === currentTitle) {
                return; // Exclude current project itself
            }

            let score = 0;
            const otherPrimary = otherCard.dataset.category || '';
            const otherAdditionals = (otherCard.dataset.additionalCategories || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
            const otherTags = (otherCard.dataset.tags || '').split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
            const otherIndustry = (otherCard.dataset.industry || '').trim().toLowerCase();

            // 1. Same Primary Category
            if (otherPrimary === currentPrimary) {
                score += 100;
            }
            if (currentAdditionals.includes(otherPrimary)) {
                score += 50;
            }

            // 2. Shared Additional Categories
            const sharedAdditionals = currentAdditionals.filter(c => otherAdditionals.includes(c));
            score += sharedAdditionals.length * 30;
            if (otherAdditionals.includes(currentPrimary)) {
                score += 40;
            }

            // 3. Shared Tags
            const sharedTags = currentTags.filter(t => otherTags.includes(t));
            score += sharedTags.length * 10;

            // 4. Similar Industry
            if (currentIndustry && otherIndustry === currentIndustry) {
                score += 15;
            }

            candidates.push({
                card: otherCard,
                score: score,
                title: otherTitle,
                category: otherPrimary,
                categoryLabel: otherCard.dataset.primaryLabel || (otherCard.querySelector('.project-card__category')?.textContent || '').trim() || categoryMeta[otherPrimary]?.label || 'Project'
            });
        });

        candidates.sort((a, b) => b.score - a.score);
        return candidates.slice(0, limit);
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
        const primaryLabel = card.dataset.primaryLabel || (card.querySelector('.project-card__category')?.textContent || '').trim() || categoryMeta[cat]?.label || 'Project Showcase';
        const additionalLabels = (card.dataset.additionalLabels || '').split(',').map(s => s.trim()).filter(Boolean);

        const meta = categoryMeta[cat] || {
            label: primaryLabel,
            role: 'Design & Development',
            focus: 'Custom Solution'
        };

        // Populate Modal Fields
        if (modalTitle) modalTitle.textContent = title;
        if (modalCategory) modalCategory.textContent = primaryLabel;
        if (modalSubtitle) modalSubtitle.textContent = industry || meta.focus;
        if (modalDesc) modalDesc.textContent = desc;
        if (modalRole) modalRole.textContent = meta.role;
        if (modalFocus) modalFocus.textContent = industry || meta.focus;

        // Tags
        if (modalTags) {
            modalTags.innerHTML = tags.map(tag => `<span class="project-modal__tag">${tag}</span>`).join('');
        }

        // Additional Categories Display
        if (modalAdditionalWrap && modalAdditionalCategories) {
            if (additionalLabels.length > 0) {
                modalAdditionalCategories.innerHTML = additionalLabels.map(label => `
                    <span class="modal-additional-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path></svg>
                        ${label}
                    </span>
                `).join('');
                modalAdditionalWrap.style.display = 'block';
            } else {
                modalAdditionalWrap.style.display = 'none';
            }
        }

        // 3 Related Projects Display
        if (modalRelatedGrid) {
            const related = getRelatedCards(card, 3);
            modalRelatedGrid.innerHTML = related.map(rel => {
                const imgWrap = rel.card.querySelector('.project-card__image');
                const imgHtml = imgWrap ? imgWrap.innerHTML : `<div class="project-card__placeholder"><span class="project-card__placeholder-title">${rel.title}</span></div>`;
                return `
                    <div class="modal-related-card" data-title="${rel.title}">
                        <div class="modal-related-card__image">${imgHtml}</div>
                        <div class="modal-related-card__body">
                            <span class="modal-related-card__category">${rel.categoryLabel}</span>
                            <div class="modal-related-card__title" title="${rel.title}">${rel.title}</div>
                        </div>
                    </div>
                `;
            }).join('');

            // Attach click listeners to related cards
            modalRelatedGrid.querySelectorAll('.modal-related-card').forEach(rCard => {
                rCard.addEventListener('click', () => {
                    const targetTitle = rCard.dataset.title;
                    const targetCard = Array.from(allCards).find(c => (c.dataset.title || '').trim() === targetTitle);
                    if (targetCard) {
                        openModal(targetCard);
                    }
                });
            });
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