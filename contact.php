<?php
/**
 * Knitin Portfolio — Contact Form Endpoint
 * Secure endpoint handling CSRF token dispensing and contact form submission.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/ContactService.php';

// Allow CORS for local development if needed
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: {$origin}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Action: Retrieve CSRF Token
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'csrf') {
    json_response([
        'csrf_token' => csrf_token(),
    ]);
}

// Process POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse data from JSON or multipart/form-data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $data = [];

    if (stripos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];
    } else {
        $data = $_POST;
    }

    $file = $_FILES['attachment'] ?? null;
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $service = new ContactService();
    $result = $service->handleSubmission($data, $file, $clientIp);

    json_response($result, $result['success'] ? 200 : 400);
}

// Method not allowed
json_response([
    'success' => false,
    'message' => 'Invalid request method.'
], 405);
