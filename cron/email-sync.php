<?php
/**
 * Knitin Portfolio — Automated Email Synchronization Cron Script
 * Run every 5–10 minutes via Hostinger Cron Jobs or via secure webhook with ?key=SECRET.
 *
 * Example Hostinger Cron Command:
 * /usr/bin/php /home/u123456789/public_html/cron/email-sync.php > /dev/null 2>&1
 */

declare(strict_types=1);

// Prevent timeout for long runs
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/EmailSyncService.php';

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

// If called via HTTP, verify secret key
if (!$isCli) {
    $providedKey = $_GET['key'] ?? '';
    $configuredKey = (string)env('CRON_SECRET', '');

    if (empty($configuredKey) || empty($providedKey) || !hash_equals($configuredKey, $providedKey)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Unauthorized cron request. Invalid or missing secret key.']));
    }
}

// Concurrency lock using a lock file
$lockFilePath = __DIR__ . '/email-sync.lock';
$lockFp = fopen($lockFilePath, 'c+');

if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    $msg = "[" . date('Y-m-d H:i:s') . "] Sync already running in another process. Exiting.\n";
    if ($isCli) {
        echo $msg;
    } else {
        json_response(['success' => false, 'message' => 'Sync already running in another process.']);
    }
    fclose($lockFp);
    exit;
}

// Touch lock file with start timestamp
fwrite($lockFp, date('Y-m-d H:i:s') . " (PID: " . getmypid() . ")\n");

$startTime = microtime(true);
$service = new EmailSyncService();
$result = $service->sync($isCli ? 'cron' : 'webhook', 30);
$elapsed = round(microtime(true) - $startTime, 2);

// Release concurrency lock
flock($lockFp, LOCK_UN);
fclose($lockFp);

$logOutput = sprintf(
    "[%s] IMAP Sync completed in %ss. Success: %s. Processed: %d messages. Note: %s\n",
    date('Y-m-d H:i:s'),
    $elapsed,
    $result['success'] ? 'YES' : 'NO',
    $result['messages_processed'] ?? 0,
    $result['message'] ?? ''
);

if ($isCli) {
    echo $logOutput;
} else {
    json_response([
        'success' => $result['success'],
        'messages_processed' => $result['messages_processed'] ?? 0,
        'elapsed_seconds' => $elapsed,
        'message' => $result['message'],
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}
