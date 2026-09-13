<?php
/**
 * Knitin Portfolio — Admin Authentication & Security
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function check(): bool {
        return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']);
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['admin_id'],
            'name' => $_SESSION['admin_name'] ?? 'Admin',
            'email' => $_SESSION['admin_email'] ?? '',
        ];
    }

    public static function id(): ?int {
        return $_SESSION['admin_id'] ?? null;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/');
            redirect('/admin/login.php?redirect=' . $returnUrl);
        }
    }

    public static function attempt(string $email, string $password): array {
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));

        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Please enter both email and password.'];
        }

        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {
                // Time-equivalent dummy verification to prevent timing attack
                password_verify($password, '$2y$10$abcdefghijklmnopqrstuvABCDEF');
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }

            // Check if locked out
            if (!empty($admin['locked_until']) && strtotime($admin['locked_until']) > time()) {
                $waitMinutes = ceil((strtotime($admin['locked_until']) - time()) / 60);
                return [
                    'success' => false,
                    'message' => "Too many failed attempts. Account locked for {$waitMinutes} more minutes."
                ];
            }

            // Verify password
            if (!password_verify($password, $admin['password_hash'])) {
                $attempts = (int)$admin['login_attempts'] + 1;
                $lockoutSql = '';
                $params = [$attempts];

                if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
                    $lockoutSql = ", locked_until = ?";
                    $params[] = $lockedUntil;
                }
                $params[] = $admin['id'];

                $updateStmt = $pdo->prepare("UPDATE admins SET login_attempts = ? {$lockoutSql} WHERE id = ?");
                $updateStmt->execute($params);

                $remaining = max(0, self::MAX_LOGIN_ATTEMPTS - $attempts);
                $warn = $remaining > 0 ? " ({$remaining} attempts remaining)" : " Account is now temporarily locked.";
                return ['success' => false, 'message' => 'Invalid email or password.' . $warn];
            }

            // Successful login: reset attempts, update last login, regenerate session
            session_regenerate_id(true);

            $updateStmt = $pdo->prepare("UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_last_activity'] = time();

            return ['success' => true, 'message' => 'Login successful.'];
        } catch (Throwable $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'A system error occurred during login. Check database configuration.'];
        }
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
