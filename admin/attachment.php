<?php
/**
 * Knitin Portfolio — Secure Authenticated Attachment Download Proxy
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

Auth::requireAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Invalid attachment identifier.');
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Attachment not found in database.');
}

$fullPath = ROOT_PATH . '/' . $attachment['file_path'];

// Path traversal guard
$realUploads = realpath(UPLOADS_PATH);
$realFile = realpath($fullPath);

if (!$realFile || !str_starts_with($realFile, $realUploads) || !file_exists($realFile)) {
    http_response_code(404);
    exit('Physical attachment file not found on server.');
}

$filename = $attachment['original_name'];
$mime = $attachment['mime_type'] ?: 'application/octet-stream';
$size = filesize($realFile);

header('Content-Description: File Transfer');
header("Content-Type: {$mime}");
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header("Content-Length: {$size}");

readfile($realFile);
exit;
