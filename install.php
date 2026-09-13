<?php
/**
 * Knitin Portfolio — Database & Admin Installer
 * Safe setup script to initialize MySQL tables and create the initial administrator.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$lockFile = __DIR__ . '/install.lock';
$isLocked = file_exists($lockFile);

// Helper to load .env
function loadEnv(string $path): array {
    if (!file_exists($path)) return [];
    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            $vars[$key] = $val;
            $_ENV[$key] = $val;
        }
    }
    return $vars;
}

$envVars = loadEnv(__DIR__ . '/.env');

// System checks
$requirements = [
    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL Driver' => extension_loaded('pdo_mysql'),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'MBString Extension' => extension_loaded('mbstring'),
    'Uploads Folder Writeable' => is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads'),
];

$allReqsMet = !in_array(false, $requirements, true);

$message = '';
$error = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        $dbHost = trim($_POST['db_host'] ?? ($envVars['DB_HOST'] ?? 'localhost'));
        $dbPort = trim($_POST['db_port'] ?? ($envVars['DB_PORT'] ?? '3306'));
        $dbName = trim($_POST['db_name'] ?? ($envVars['DB_NAME'] ?? ''));
        $dbUser = trim($_POST['db_user'] ?? ($envVars['DB_USER'] ?? ''));
        $dbPass = $_POST['db_pass'] ?? ($envVars['DB_PASS'] ?? '');

        $adminName = trim($_POST['admin_name'] ?? 'Nitin Kumar');
        $adminEmail = trim($_POST['admin_email'] ?? 'contact@knitin525.in');
        $adminPass = $_POST['admin_pass'] ?? '';

        if (empty($dbName) || empty($dbUser)) {
            $error = 'Database name and database username are required.';
        } elseif (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'A valid administrator email address is required.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Administrator password must be at least 8 characters long.';
        } else {
            try {
                // Test DB connection
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Read and run database.sql
                $sqlFile = __DIR__ . '/database.sql';
                if (!file_exists($sqlFile)) {
                    throw new RuntimeException("database.sql file not found.");
                }

                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);

                // Create or update admin account
                $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
                $stmt->execute([$adminEmail]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $updateStmt = $pdo->prepare("UPDATE admins SET name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$adminName, $passwordHash, $existing['id']]);
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
                    $insertStmt->execute([$adminName, $adminEmail, $passwordHash]);
                }

                // Update .env file if needed
                $envContent = file_exists(__DIR__ . '/.env') ? file_get_contents(__DIR__ . '/.env') : file_get_contents(__DIR__ . '/.env.example');
                $replacements = [
                    'DB_HOST' => $dbHost,
                    'DB_PORT' => $dbPort,
                    'DB_NAME' => $dbName,
                    'DB_USER' => $dbUser,
                    'DB_PASS' => $dbPass,
                    'ADMIN_EMAIL' => $adminEmail,
                    'ADMIN_NAME' => '"' . addslashes($adminName) . '"',
                ];
                foreach ($replacements as $k => $v) {
                    if (preg_match("/^{$k}=.*/m", $envContent)) {
                        $envContent = preg_replace("/^{$k}=.*/m", "{$k}={$v}", $envContent);
                    } else {
                        $envContent .= "\n{$k}={$v}";
                    }
                }
                file_put_contents(__DIR__ . '/.env', $envContent);

                // Lock installer
                file_put_contents($lockFile, "Installed on " . date('c') . "\nAdmin: {$adminEmail}\n");
                $isLocked = true;
                $message = 'Installation completed successfully! You can now log into your Admin Dashboard.';
                $step = 3;
            } catch (Throwable $e) {
                $error = 'Installation Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitin Portfolio — System Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --error: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            width: 100%;
            max-width: 640px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .logo span { color: var(--primary); }
        .subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        .req-list {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .req-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.88rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .req-item:last-child { border-bottom: none; }
        .badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .badge-pass { background: rgba(16,185,129,0.2); color: #34d399; }
        .badge-fail { background: rgba(239,68,68,0.2); color: #f87171; }
        .form-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 24px 0 16px;
            color: #fff;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
            margin-top: 24px;
        }
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        .lock-notice {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">Knitin<span>.</span> Portfolio</div>
        <div class="subtitle">Contact & Email Management System Installer</div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
        <div class="lock-notice">
            <h3 style="margin-bottom: 10px; color: #34d399;">System Already Installed &amp; Locked</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px;">
                The installer lock file (<code>install.lock</code>) exists. For security, re-running this installer is prohibited.
            </p>
            <a href="admin/login.php" class="btn">Proceed to Admin Login &rarr;</a>
        </div>
    <?php else: ?>

        <div class="req-list">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase;">
                Environment Health Check
            </div>
            <?php foreach ($requirements as $name => $passed): ?>
                <div class="req-item">
                    <span><?= htmlspecialchars($name) ?></span>
                    <span class="badge <?= $passed ? 'badge-pass' : 'badge-fail' ?>">
                        <?= $passed ? 'OK' : 'MISSING' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="install">

            <div class="form-section-title">1. MySQL Database Connection</div>
            <div class="form-row">
                <div class="form-group">
                    <label>DB Host</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($envVars['DB_HOST'] ?? 'localhost') ?>" required>
                </div>
                <div class="form-group">
                    <label>DB Port</label>
                    <input type="text" name="db_port" value="<?= htmlspecialchars($envVars['DB_PORT'] ?? '3306') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>DB Name</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($envVars['DB_NAME'] ?? '') ?>" placeholder="e.g. u123456789_portfolio" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>DB Username</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($envVars['DB_USER'] ?? '') ?>" placeholder="e.g. u123456789_admin" required>
                </div>
                <div class="form-group">
                    <label>DB Password</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($envVars['DB_PASS'] ?? '') ?>" placeholder="Database password">
                </div>
            </div>

            <div class="form-section-title">2. Administrator Account Setup</div>
            <div class="form-group">
                <label>Admin Full Name</label>
                <input type="text" name="admin_name" value="<?= htmlspecialchars($envVars['ADMIN_NAME'] ?? 'Nitin Kumar') ?>" required>
            </div>

            <div class="form-group">
                <label>Admin Login Email</label>
                <input type="email" name="admin_email" value="<?= htmlspecialchars($envVars['ADMIN_EMAIL'] ?? 'contact@knitin525.in') ?>" required>
            </div>

            <div class="form-group">
                <label>Admin Login Password (min 8 chars)</label>
                <input type="password" name="admin_pass" placeholder="Create a secure admin password" required minlength="8">
            </div>

            <button type="submit" class="btn">Run Installation &amp; Initialize Database</button>
        </form>

    <?php endif; ?>
</div>
</body>
</html>
