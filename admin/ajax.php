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

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        break;
}
