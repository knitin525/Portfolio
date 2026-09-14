<?php
/**
 * Knitin Portfolio — Public Projects API
 * Provides database-backed projects with Primary & Additional Categories,
 * cross-category filtering, and related project recommendations.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/ProjectService.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $service = new ProjectService();

    // 1. Single Project by ID
    if (!empty($_GET['id'])) {
        $proj = $service->getProjectById((int)$_GET['id']);
        if (!$proj) {
            json_response(['success' => false, 'message' => 'Project not found.'], 404);
        }
        $related = $service->getRelatedProjects((int)$proj['id'], 3);
        $proj['related_projects'] = $related;
        json_response(['success' => true, 'data' => $proj]);
    }

    // 2. Single Project by Slug
    if (!empty($_GET['slug'])) {
        $proj = $service->getProjectBySlug(trim($_GET['slug']));
        if (!$proj) {
            json_response(['success' => false, 'message' => 'Project not found.'], 404);
        }
        $related = $service->getRelatedProjects((int)$proj['id'], 3);
        $proj['related_projects'] = $related;
        json_response(['success' => true, 'data' => $proj]);
    }

    // 3. Related Projects for a given project ID
    if (!empty($_GET['related_to'])) {
        $related = $service->getRelatedProjects((int)$_GET['related_to'], 3);
        json_response(['success' => true, 'data' => $related]);
    }

    // 4. Categories list
    if (isset($_GET['categories'])) {
        $cats = $service->getCategories(true);
        json_response(['success' => true, 'data' => $cats]);
    }

    // 5. Filtered List of Projects
    $filters = ['status' => 'published'];
    if (isset($_GET['featured'])) {
        $filters['is_featured'] = (int)$_GET['featured'];
    }
    if (!empty($_GET['category'])) {
        $filters['category_slug'] = trim($_GET['category']);
    }
    if (!empty($_GET['search'])) {
        $filters['search'] = trim($_GET['search']);
    }
    if (!empty($_GET['limit'])) {
        $filters['limit'] = (int)$_GET['limit'];
    }

    $projects = $service->getProjects($filters);
    json_response([
        'success' => true,
        'count' => count($projects),
        'data' => $projects
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ], 500);
}
