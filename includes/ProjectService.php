<?php
/**
 * Knitin Portfolio — Project & Category Service
 * Handles Primary + Additional Category management, validation, persistence & discovery.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class ProjectService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? db();
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /**
     * Get list of categories.
     *
     * @param bool $onlyActive
     * @return array
     */
    public function getCategories(bool $onlyActive = false): array {
        $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM projects p WHERE p.primary_category_id = c.id) AS primary_project_count,
                    (SELECT COUNT(*) FROM project_category_map pcm WHERE pcm.category_id = c.id) AS additional_project_count
                FROM project_categories c";
        if ($onlyActive) {
            $sql .= " WHERE c.is_active = 1";
        }
        $sql .= " ORDER BY c.display_order ASC, c.name ASC";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Get single category by ID.
     */
    public function getCategoryById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM project_categories WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get single category by slug.
     */
    public function getCategoryBySlug(string $slug): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM project_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Save category (Insert or Update).
     */
    public function saveCategory(array $data, ?int $id = null): int {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException("Category name is required.");
        }

        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        $description = !empty($data['description']) ? trim($data['description']) : null;
        $displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        if ($id !== null && $id > 0) {
            // Check unique slug excluding self
            $stmt = $this->pdo->prepare("SELECT id FROM project_categories WHERE slug = ? AND id != ? LIMIT 1");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug .= '-' . $id;
            }

            $updateStmt = $this->pdo->prepare("
                UPDATE project_categories 
                SET name = ?, slug = ?, description = ?, display_order = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$name, $slug, $description, $displayOrder, $isActive, $id]);
            return $id;
        } else {
            // Check unique slug
            $stmt = $this->pdo->prepare("SELECT id FROM project_categories WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }

            $insertStmt = $this->pdo->prepare("
                INSERT INTO project_categories (name, slug, description, display_order, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->execute([$name, $slug, $description, $displayOrder, $isActive]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * Delete a category.
     * Prevents deletion if category is assigned as Primary Category to any project.
     */
    public function deleteCategory(int $id): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM projects WHERE primary_category_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            throw new RuntimeException("Cannot delete category because it is assigned as Primary Category to {$count} project(s).");
        }

        // Delete any mapping rows first
        $delMap = $this->pdo->prepare("DELETE FROM project_category_map WHERE category_id = ?");
        $delMap->execute([$id]);

        $del = $this->pdo->prepare("DELETE FROM project_categories WHERE id = ?");
        return $del->execute([$id]);
    }

    // =========================================================================
    // PROJECTS & PRIMARY + ADDITIONAL CATEGORY ARCHITECTURE
    // =========================================================================

    /**
     * Get list of projects with primary and mapped additional categories.
     *
     * @param array $filters [status, is_featured, category_id, search, limit, offset]
     * @return array
     */
    public function getProjects(array $filters = []): array {
        $sql = "
            SELECT 
                p.*,
                pc.name AS primary_category_name,
                pc.slug AS primary_category_slug
            FROM projects p
            INNER JOIN project_categories pc ON pc.id = p.primary_category_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $sql .= " AND p.is_featured = ?";
            $params[] = (int)$filters['is_featured'];
        }

        if (!empty($filters['primary_category_id'])) {
            $sql .= " AND p.primary_category_id = ?";
            $params[] = (int)$filters['primary_category_id'];
        }

        // Filter matching either Primary Category OR any mapped Additional Category
        if (!empty($filters['category_id'])) {
            $catId = (int)$filters['category_id'];
            $sql .= " AND (p.primary_category_id = ? OR EXISTS (
                SELECT 1 FROM project_category_map pcm WHERE pcm.project_id = p.id AND pcm.category_id = ?
            ))";
            $params[] = $catId;
            $params[] = $catId;
        }

        // Filter matching category slug (matches primary slug OR additional slug)
        if (!empty($filters['category_slug']) && $filters['category_slug'] !== 'all') {
            $catSlug = trim($filters['category_slug']);
            $sql .= " AND (pc.slug = ? OR EXISTS (
                SELECT 1 FROM project_category_map pcm 
                JOIN project_categories sub_c ON sub_c.id = pcm.category_id 
                WHERE pcm.project_id = p.id AND sub_c.slug = ?
            ))";
            $params[] = $catSlug;
            $params[] = $catSlug;
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (p.title LIKE ? OR p.industry LIKE ? OR p.summary LIKE ? OR p.tags LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY p.sort_order ASC, p.created_at DESC";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $projects = $stmt->fetchAll();

        if (empty($projects)) {
            return [];
        }

        // Hydrate additional categories for all returned projects in bulk
        $projectIds = array_column($projects, 'id');
        $mapSql = "
            SELECT pcm.project_id, c.id, c.name, c.slug 
            FROM project_category_map pcm
            JOIN project_categories c ON c.id = pcm.category_id
            WHERE pcm.project_id IN (" . implode(',', array_fill(0, count($projectIds), '?')) . ")
            ORDER BY c.display_order ASC, c.name ASC
        ";
        $mapStmt = $this->pdo->prepare($mapSql);
        $mapStmt->execute($projectIds);
        $mappedRows = $mapStmt->fetchAll();

        $additionalMap = [];
        foreach ($mappedRows as $mRow) {
            $additionalMap[$mRow['project_id']][] = [
                'id' => (int)$mRow['id'],
                'name' => $mRow['name'],
                'slug' => $mRow['slug'],
            ];
        }

        foreach ($projects as &$proj) {
            $proj['additional_categories'] = $additionalMap[$proj['id']] ?? [];
        }
        unset($proj);

        return $projects;
    }

    /**
     * Get single project by ID with full primary & additional categories.
     */
    public function getProjectById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                pc.name AS primary_category_name,
                pc.slug AS primary_category_slug
            FROM projects p
            INNER JOIN project_categories pc ON pc.id = p.primary_category_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) return null;

        // Fetch additional categories
        $mapStmt = $this->pdo->prepare("
            SELECT c.id, c.name, c.slug 
            FROM project_category_map pcm
            JOIN project_categories c ON c.id = pcm.category_id
            WHERE pcm.project_id = ?
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $mapStmt->execute([$id]);
        $project['additional_categories'] = $mapStmt->fetchAll();
        $project['additional_category_ids'] = array_map('intval', array_column($project['additional_categories'], 'id'));

        return $project;
    }

    /**
     * Get single project by slug.
     */
    public function getProjectBySlug(string $slug): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                pc.name AS primary_category_name,
                pc.slug AS primary_category_slug
            FROM projects p
            INNER JOIN project_categories pc ON pc.id = p.primary_category_id
            WHERE p.slug = ?
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $project = $stmt->fetch();
        if (!$project) return null;

        $mapStmt = $this->pdo->prepare("
            SELECT c.id, c.name, c.slug 
            FROM project_category_map pcm
            JOIN project_categories c ON c.id = pcm.category_id
            WHERE pcm.project_id = ?
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $mapStmt->execute([(int)$project['id']]);
        $project['additional_categories'] = $mapStmt->fetchAll();
        $project['additional_category_ids'] = array_map('intval', array_column($project['additional_categories'], 'id'));

        return $project;
    }

    private ?array $projectTableColumns = null;

    /**
     * Cache and return existing columns in projects table for safe dynamic persistence.
     */
    public function getProjectTableColumns(): array {
        if ($this->projectTableColumns === null) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM projects");
                $this->projectTableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
            } catch (Throwable $e) {
                $this->projectTableColumns = [];
            }
        }
        return $this->projectTableColumns;
    }

    /**
     * Save Project with Primary and Additional Categories.
     * Enforces:
     * 1. Exactly ONE Primary Category (Required, valid ID).
     * 2. Zero or more Additional Categories (Optional).
     * 3. Automatic deduplication: If Primary Category is selected in Additional Categories,
     *    it is automatically removed.
     * 4. Safe transaction wrapping.
     * 5. Dynamic support for extended schema columns (subtitle, country, year, services, etc.)
     *
     * @param array $data
     * @param int|null $id
     * @return int Saved project ID
     */
    public function saveProject(array $data, ?int $id = null): int {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException("Project title is required.");
        }

        // Validate Primary Category
        $primaryCategoryId = isset($data['primary_category_id']) ? (int)$data['primary_category_id'] : 0;
        if ($primaryCategoryId <= 0) {
            throw new InvalidArgumentException("Primary Category is required. Every project must have exactly one Primary Category.");
        }

        $checkPrimary = $this->pdo->prepare("SELECT id FROM project_categories WHERE id = ? LIMIT 1");
        $checkPrimary->execute([$primaryCategoryId]);
        if (!$checkPrimary->fetch()) {
            throw new InvalidArgumentException("Invalid Primary Category selected.");
        }

        // Generate or clean slug
        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        $clientName = !empty($data['client_name']) ? trim($data['client_name']) : null;
        $industry = !empty($data['industry']) ? trim($data['industry']) : null;
        $summary = !empty($data['summary']) ? trim($data['summary']) : null;
        $description = !empty($data['description']) ? trim($data['description']) : null;
        $heroImage = !empty($data['hero_image']) ? trim($data['hero_image']) : null;
        $tags = !empty($data['tags']) ? trim($data['tags']) : null;
        $projectType = !empty($data['project_type']) ? trim($data['project_type']) : null;
        $projectUrl = !empty($data['project_url']) ? trim($data['project_url']) : null;
        $isFeatured = isset($data['is_featured']) ? (int)(bool)$data['is_featured'] : 0;
        $status = in_array($data['status'] ?? '', ['draft', 'published', 'archived'], true) ? $data['status'] : 'published';
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;

        // Extended optional fields
        $extraData = [
            'subtitle' => !empty($data['subtitle']) ? trim($data['subtitle']) : null,
            'country' => !empty($data['country']) ? trim($data['country']) : null,
            'project_year' => !empty($data['project_year']) ? (int)$data['project_year'] : null,
            'services' => !empty($data['services']) ? trim($data['services']) : null,
            'technologies' => !empty($data['technologies']) ? trim($data['technologies']) : null,
            'tools' => !empty($data['tools']) ? trim($data['tools']) : null,
            'github_url' => !empty($data['github_url']) ? trim($data['github_url']) : null,
            'behance_url' => !empty($data['behance_url']) ? trim($data['behance_url']) : null,
            'demo_url' => !empty($data['demo_url']) ? trim($data['demo_url']) : null,
            'case_study_url' => !empty($data['case_study_url']) ? trim($data['case_study_url']) : null,
            'seo_title' => !empty($data['seo_title']) ? trim($data['seo_title']) : null,
            'seo_description' => !empty($data['seo_description']) ? trim($data['seo_description']) : null,
            'image_alt' => !empty($data['image_alt']) ? trim($data['image_alt']) : null,
        ];

        // ---------------------------------------------------------------------
        // SANITIZE ADDITIONAL CATEGORIES
        // Rule: Prevent duplicates, remove primary category from additionals, filter invalid IDs
        // ---------------------------------------------------------------------
        $rawAdditional = $data['additional_category_ids'] ?? [];
        if (!is_array($rawAdditional)) {
            $rawAdditional = is_string($rawAdditional) ? explode(',', $rawAdditional) : [];
        }

        $sanitizedAdditional = [];
        foreach ($rawAdditional as $rawId) {
            $catInt = (int)$rawId;
            // Discard invalid IDs, primary category duplicate, or already added
            if ($catInt > 0 && $catInt !== $primaryCategoryId && !in_array($catInt, $sanitizedAdditional, true)) {
                $sanitizedAdditional[] = $catInt;
            }
        }

        // Verify that additional category IDs actually exist
        if (!empty($sanitizedAdditional)) {
            $inClause = implode(',', array_fill(0, count($sanitizedAdditional), '?'));
            $valStmt = $this->pdo->prepare("SELECT id FROM project_categories WHERE id IN ({$inClause})");
            $valStmt->execute($sanitizedAdditional);
            $validDbIds = array_map('intval', $valStmt->fetchAll(PDO::FETCH_COLUMN, 0));
            $sanitizedAdditional = array_values(array_intersect($sanitizedAdditional, $validDbIds));
        }

        $existingCols = $this->getProjectTableColumns();

        // ---------------------------------------------------------------------
        // DATABASE PERSISTENCE TRANSACTION
        // ---------------------------------------------------------------------
        $this->pdo->beginTransaction();
        try {
            if ($id !== null && $id > 0) {
                // Check slug uniqueness excluding this project
                $slugCheck = $this->pdo->prepare("SELECT id FROM projects WHERE slug = ? AND id != ? LIMIT 1");
                $slugCheck->execute([$slug, $id]);
                if ($slugCheck->fetch()) {
                    $slug .= '-' . $id;
                }

                $fields = [
                    'primary_category_id' => $primaryCategoryId,
                    'title' => $title,
                    'slug' => $slug,
                    'client_name' => $clientName,
                    'industry' => $industry,
                    'summary' => $summary,
                    'description' => $description,
                    'hero_image' => $heroImage,
                    'tags' => $tags,
                    'project_type' => $projectType,
                    'project_url' => $projectUrl,
                    'is_featured' => $isFeatured,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                ];

                foreach ($extraData as $col => $val) {
                    if (in_array($col, $existingCols, true)) {
                        $fields[$col] = $val;
                    }
                }

                $setSqlParts = [];
                $updateValues = [];
                foreach ($fields as $col => $val) {
                    $setSqlParts[] = "{$col} = ?";
                    $updateValues[] = $val;
                }
                $setSqlParts[] = "updated_at = NOW()";
                $updateValues[] = $id;

                $updateSql = "UPDATE projects SET " . implode(', ', $setSqlParts) . " WHERE id = ?";
                $updateStmt = $this->pdo->prepare($updateSql);
                $updateStmt->execute($updateValues);

                $projectId = $id;
            } else {
                // Check slug uniqueness
                $slugCheck = $this->pdo->prepare("SELECT id FROM projects WHERE slug = ? LIMIT 1");
                $slugCheck->execute([$slug]);
                if ($slugCheck->fetch()) {
                    $slug .= '-' . time();
                }

                $insertFields = [
                    'primary_category_id' => $primaryCategoryId,
                    'title' => $title,
                    'slug' => $slug,
                    'client_name' => $clientName,
                    'industry' => $industry,
                    'summary' => $summary,
                    'description' => $description,
                    'hero_image' => $heroImage,
                    'tags' => $tags,
                    'project_type' => $projectType,
                    'project_url' => $projectUrl,
                    'is_featured' => $isFeatured,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                ];

                foreach ($extraData as $col => $val) {
                    if (in_array($col, $existingCols, true)) {
                        $insertFields[$col] = $val;
                    }
                }

                $colNames = array_keys($insertFields);
                $colNames[] = 'created_at';
                $placeholders = array_fill(0, count($insertFields), '?');
                $placeholders[] = 'NOW()';

                $insertValues = array_values($insertFields);

                $insertSql = "INSERT INTO projects (" . implode(', ', $colNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute($insertValues);

                $projectId = (int)$this->pdo->lastInsertId();
            }

            // Sync Additional Categories mapping table
            $delMap = $this->pdo->prepare("DELETE FROM project_category_map WHERE project_id = ?");
            $delMap->execute([$projectId]);

            if (!empty($sanitizedAdditional)) {
                $insertMap = $this->pdo->prepare("INSERT INTO project_category_map (project_id, category_id) VALUES (?, ?)");
                foreach ($sanitizedAdditional as $addCatId) {
                    $insertMap->execute([$projectId, $addCatId]);
                }
            }

            $this->pdo->commit();
            return $projectId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete project and associated category mappings.
     */
    public function deleteProject(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Toggle project featured status.
     */
    public function toggleFeatured(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE projects SET is_featured = NOT is_featured, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // =========================================================================
    // RELATED PROJECTS RECOMMENDATION ALGORITHM
    // Priority:
    // 1. Same Primary Category (weight: 100)
    // 2. Shared Additional Categories (weight: 30 each)
    // 3. Shared Tags (weight: 10 each)
    // 4. Similar Project Type / Industry (weight: 5)
    // Exclude current project; show top 3.
    // =========================================================================

    /**
     * Get up to $limit related projects for a given project.
     *
     * @param int $projectId
     * @param int $limit
     * @return array
     */
    public function getRelatedProjects(int $projectId, int $limit = 3): array {
        $current = $this->getProjectById($projectId);
        if (!$current) {
            return [];
        }

        $currentPrimaryId = (int)$current['primary_category_id'];
        $currentAdditionalIds = $current['additional_category_ids'] ?? [];
        $currentTags = array_filter(array_map('trim', explode(',', strtolower($current['tags'] ?? ''))));
        $currentIndustry = strtolower(trim($current['industry'] ?? ''));
        $currentType = strtolower(trim($current['project_type'] ?? ''));

        // Fetch candidate published projects excluding self
        $candidates = $this->getProjects([
            'status' => 'published',
        ]);

        $scored = [];
        foreach ($candidates as $cand) {
            $candId = (int)$cand['id'];
            if ($candId === $projectId) {
                continue; // Never recommend current project itself
            }

            $score = 0;

            // 1. Same Primary Category
            if ((int)$cand['primary_category_id'] === $currentPrimaryId) {
                $score += 100;
            }

            // Also check if candidate primary category is one of current's additionals, or vice-versa
            if (in_array((int)$cand['primary_category_id'], $currentAdditionalIds, true)) {
                $score += 50;
            }

            // 2. Shared Additional Categories
            $candAdditionalIds = array_map('intval', array_column($cand['additional_categories'] ?? [], 'id'));
            $sharedAdditionals = array_intersect($currentAdditionalIds, $candAdditionalIds);
            $score += count($sharedAdditionals) * 30;

            if (in_array($currentPrimaryId, $candAdditionalIds, true)) {
                $score += 40;
            }

            // 3. Shared Tags
            $candTags = array_filter(array_map('trim', explode(',', strtolower($cand['tags'] ?? ''))));
            $sharedTags = array_intersect($currentTags, $candTags);
            $score += count($sharedTags) * 10;

            // 4. Similar Industry / Project Type
            if (!empty($currentIndustry) && strtolower(trim($cand['industry'] ?? '')) === $currentIndustry) {
                $score += 15;
            }
            if (!empty($currentType) && strtolower(trim($cand['project_type'] ?? '')) === $currentType) {
                $score += 10;
            }

            $cand['match_score'] = $score;
            $scored[] = $cand;
        }

        // Sort by match_score descending, then sort_order ascending, then created_at descending
        usort($scored, function($a, $b) {
            if ($b['match_score'] !== $a['match_score']) {
                return $b['match_score'] <=> $a['match_score'];
            }
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] <=> $b['sort_order'];
            }
            return strcmp($b['created_at'], $a['created_at']);
        });

        return array_slice($scored, 0, $limit);
    }

    // =========================================================================
    // UTILITY HELPERS
    // =========================================================================

    /**
     * Get adjacent (previous/next) published projects for navigation.
     * Uses sort_order ASC, created_at DESC ordering consistent with getProjects().
     *
     * @param int $projectId
     * @return array ['prev' => ?array, 'next' => ?array]
     */
    public function getAdjacentProjects(int $projectId): array {
        $result = ['prev' => null, 'next' => null];

        $current = $this->getProjectById($projectId);
        if (!$current) {
            return $result;
        }

        $sortOrder = (int)$current['sort_order'];
        $createdAt = $current['created_at'];

        // Previous: lower sort_order, or same sort_order but newer created_at
        $prevStmt = $this->pdo->prepare("
            SELECT p.id, p.title, p.slug, p.hero_image, pc.name AS primary_category_name, pc.slug AS primary_category_slug
            FROM projects p
            INNER JOIN project_categories pc ON pc.id = p.primary_category_id
            WHERE p.status = 'published' AND p.id != ?
              AND (p.sort_order < ? OR (p.sort_order = ? AND p.created_at > ?))
            ORDER BY p.sort_order DESC, p.created_at ASC
            LIMIT 1
        ");
        $prevStmt->execute([$projectId, $sortOrder, $sortOrder, $createdAt]);
        $result['prev'] = $prevStmt->fetch() ?: null;

        // Next: higher sort_order, or same sort_order but older created_at
        $nextStmt = $this->pdo->prepare("
            SELECT p.id, p.title, p.slug, p.hero_image, pc.name AS primary_category_name, pc.slug AS primary_category_slug
            FROM projects p
            INNER JOIN project_categories pc ON pc.id = p.primary_category_id
            WHERE p.status = 'published' AND p.id != ?
              AND (p.sort_order > ? OR (p.sort_order = ? AND p.created_at < ?))
            ORDER BY p.sort_order ASC, p.created_at DESC
            LIMIT 1
        ");
        $nextStmt->execute([$projectId, $sortOrder, $sortOrder, $createdAt]);
        $result['next'] = $nextStmt->fetch() ?: null;

        return $result;
    }

    /**
     * Get only categories that have at least one published project.
     * Used for the public-facing filter bar.
     *
     * @return array
     */
    public function getPublishedCategories(): array {
        $sql = "
            SELECT DISTINCT c.id, c.name, c.slug, c.display_order
            FROM project_categories c
            WHERE c.is_active = 1
              AND (
                EXISTS (SELECT 1 FROM projects p WHERE p.primary_category_id = c.id AND p.status = 'published')
                OR EXISTS (SELECT 1 FROM project_category_map pcm JOIN projects p2 ON p2.id = pcm.project_id WHERE pcm.category_id = c.id AND p2.status = 'published')
              )
            ORDER BY c.display_order ASC, c.name ASC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Get project count with optional filters.
     * Useful for admin dashboard stats.
     *
     * @param array $filters [status, is_featured]
     * @return int
     */
    public function getProjectCount(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM projects WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $sql .= " AND is_featured = ?";
            $params[] = (int)$filters['is_featured'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create URL-safe slug from string.
     */
    private function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return !empty($text) ? $text : 'item-' . bin2hex(random_bytes(3));
    }
}
