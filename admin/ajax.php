<?php
/**
 * Knitin Portfolio — Admin AJAX Dispatcher
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/MailSender.php';
require_once dirname(__DIR__) . '/includes/ImapClient.php';
require_once dirname(__DIR__) . '/includes/EmailSyncService.php';

// Must be logged in as admin
if (!Auth::check()) {
    json_response(['success' => false, 'message' => 'Unauthorized access.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method.'], 405);
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$action = $_POST['action'] ?? '';
$pdo = db();

switch ($action) {
    case 'sync_emails':
        $service = new EmailSyncService();
        $result = $service->sync('manual', 30);
        json_response($result);
        break;

    case 'test_smtp':
        $mailer = new MailSender();
        $result = $mailer->testConnection();
        json_response($result);
        break;

    case 'test_imap':
        $client = new ImapClient();
        $result = $client->testConnection();
        json_response($result);
        break;

    case 'toggle_star':
        $emailId = (int)($_POST['email_id'] ?? 0);
        if (!$emailId) json_response(['success' => false, 'message' => 'Missing ID'], 400);

        $stmt = $pdo->prepare("UPDATE emails SET is_starred = NOT is_starred WHERE id = ?");
        $stmt->execute([$emailId]);
        json_response(['success' => true]);
        break;

    case 'toggle_read':
        $emailId = (int)($_POST['email_id'] ?? 0);
        $isRead = !empty($_POST['is_read']) ? 1 : 0;
        if (!$emailId) json_response(['success' => false, 'message' => 'Missing ID'], 400);

        $stmt = $pdo->prepare("UPDATE emails SET is_read = ? WHERE id = ?");
        $stmt->execute([$isRead, $emailId]);
        json_response(['success' => true]);
        break;

    case 'update_lead_status':
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['new', 'reviewed', 'replied', 'follow_up', 'converted', 'closed'];

        if (!$leadId || !in_array($status, $allowed, true)) {
            json_response(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        $stmt = $pdo->prepare("UPDATE contact_submissions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $leadId]);
        json_response(['success' => true]);
        break;

    case 'update_contact_status':
        $contactId = (int)($_POST['contact_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['new_lead', 'contacted', 'in_discussion', 'client', 'completed', 'archived'];

        if (!$contactId || !in_array($status, $allowed, true)) {
            json_response(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        $stmt = $pdo->prepare("UPDATE contacts SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $contactId]);
        json_response(['success' => true]);
        break;

    case 'save_notes':
        $contactId = (int)($_POST['contact_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if (!$contactId) json_response(['success' => false, 'message' => 'Missing contact ID'], 400);

        $stmt = $pdo->prepare("UPDATE contacts SET notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$notes, $contactId]);
        json_response(['success' => true]);
        break;

    case 'toggle_project_featured':
        require_once dirname(__DIR__) . '/includes/ProjectService.php';
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) json_response(['success' => false, 'message' => 'Missing project ID'], 400);

        $ps = new ProjectService($pdo);
        $result = $ps->toggleFeatured($projectId);
        json_response(['success' => $result]);
        break;

    case 'delete_project':
        require_once dirname(__DIR__) . '/includes/ProjectService.php';
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) json_response(['success' => false, 'message' => 'Missing project ID'], 400);

        $ps = new ProjectService($pdo);
        $result = $ps->deleteProject($projectId);
        json_response(['success' => $result]);
        break;

    case 'upload_project_icon':
        require_once dirname(__DIR__) . '/includes/ProjectService.php';
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) {
            json_response(['success' => false, 'message' => 'Missing project ID.'], 400);
        }

        if (empty($_FILES['icon_file']['name']) || $_FILES['icon_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            ];
            $errMsg = $uploadErrors[$_FILES['icon_file']['error'] ?? 0] ?? 'File upload error occurred.';
            json_response(['success' => false, 'message' => $errMsg], 400);
        }

        $file = $_FILES['icon_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'];
        if (!in_array($ext, $allowed, true)) {
            json_response(['success' => false, 'message' => 'Invalid file format. Allowed: PNG, JPG, WEBP, SVG, GIF, AVIF.'], 400);
        }

        // 10MB limit
        if ($file['size'] > 10 * 1024 * 1024) {
            json_response(['success' => false, 'message' => 'File size exceeds 10MB limit.'], 400);
        }

        $uploadDir = dirname(__DIR__) . '/uploads/projects';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $fileName = 'proj_icon_' . $projectId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            json_response(['success' => false, 'message' => 'Could not save file to server uploads folder.'], 500);
        }

        $relPath = 'uploads/projects/' . $fileName;
        $ps = new ProjectService($pdo);
        $updated = $ps->updateProjectImage($projectId, $relPath);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Project icon uploaded successfully!',
                'image_url' => $relPath,
                'display_url' => project_image_url($relPath, true),
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to save project icon to database.'], 500);
        }
        break;

    case 'remove_project_icon':
        require_once dirname(__DIR__) . '/includes/ProjectService.php';
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) {
            json_response(['success' => false, 'message' => 'Missing project ID.'], 400);
        }

        $ps = new ProjectService($pdo);
        $updated = $ps->updateProjectImage($projectId, null);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Project icon removed successfully.',
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Could not remove icon in database.'], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        break;
}
