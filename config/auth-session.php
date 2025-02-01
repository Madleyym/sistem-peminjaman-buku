<?php
// File: C:\xampp\htdocs\sistem\config\auth-session.php

class AuthSession
{
    private static $instance = null;
    private static $sessionType;
    private static $sessionTimeout = 1800; // 30 minutes
    private static $db = null;
    private const SESSION_PREFIX = 'libsys_';
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    // Constants untuk roles
    public const ROLES = [
        'ADMIN' => 'admin',
        'STAFF' => 'staff',
        'USERS' => 'users'
    ];

    private function __construct($sessionType)
    {
        // Set timezone
        date_default_timezone_set(self::DEFAULT_TIMEZONE);

        // Inisialisasi session jika belum ada
        if (session_status() === PHP_SESSION_NONE) {
            $this->initializeSessionSecurity();
            // Gunakan session name yang konsisten
            session_name(self::SESSION_PREFIX . $sessionType);
            session_start([
                'cookie_lifetime' => 0,
                'gc_maxlifetime' => self::$sessionTimeout,
                'use_strict_mode' => 1
            ]);
        }

        self::$sessionType = $sessionType;
        $this->initializeDatabase();

        // Set last activity time
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }

    private function initializeSessionSecurity(): void
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', self::$sessionTimeout);
        ini_set('session.use_strict_mode', 1);
        // Disable secure cookie jika tidak menggunakan HTTPS
        ini_set('session.cookie_secure', 0);
    }

    private function initializeDatabase(): void
    {
        if (self::$db === null) {
            require_once __DIR__ . '/database.php';
            $database = new Database();
            self::$db = $database->getConnection();
        }
    }

    public static function getInstance(string $role): self
    {
        if (self::$instance === null) {
            self::$instance = new self($role);
        }
        return self::$instance;
    }

    public static function login(array $data, string $role): bool
    {
        try {
            self::getInstance($role);
            $result = self::loginUser($data, $role);

            if ($result['status']) {
                $sessionKey = self::getSessionKey();
                $_SESSION[$sessionKey] = array_merge($result['user'], [
                    'last_activity' => time(),
                    'login_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);

                $_SESSION['LAST_ACTIVITY'] = time();
                $_SESSION['user_role'] = $role;

                // Log successful login
                error_log("Successful login for user: {$result['user']['email']} at " . date('Y-m-d H:i:s'));

                return true;
            }

            error_log("Failed login attempt for email: {$data['email']} at " . date('Y-m-d H:i:s'));
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    protected static function loginUser(array $data, string $table): array
    {
        try {
            $query = "SELECT * FROM {$table} WHERE email = :email";
            if ($table === 'admin' && isset($data['nik'])) {
                $query .= " AND nik = :nik";
            }
            if ($table === 'staff') {
                $query .= " AND is_active = 1";
            }

            $stmt = self::$db->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            if ($table === 'admin' && isset($data['nik'])) {
                $stmt->bindParam(':nik', $data['nik']);
            }
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'], $user['password'])) {
                self::updateLoginInfo($table, $user['id']);
                unset($user['password']);
                return [
                    'status' => true,
                    'user' => $user,
                    'role' => $table
                ];
            }

            return [
                'status' => false,
                'message' => 'Email atau password salah'
            ];
        } catch (PDOException $e) {
            error_log("Login Error ({$table}): " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan saat login'
            ];
        }
    }

    protected static function updateLoginInfo(string $table, string $userId): void
    {
        try {
            $updateFields = [
                'users' => 'last_login = NOW()',
                'admin' => 'last_login = NOW()',
                'staff' => 'last_login = NOW(), login_count = login_count + 1'
            ];

            $query = "UPDATE {$table} SET " . $updateFields[$table] . " WHERE id = :id";
            $stmt = self::$db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Login Info Error: " . $e->getMessage());
        }
    }

    public static function isLoggedIn(): bool
    {
        $sessionKey = self::getSessionKey();

        // Debug logging
        error_log("Checking login status for session key: " . $sessionKey);
        error_log("Current session data: " . print_r($_SESSION, true));

        if (!isset($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey])) {
            error_log("No session data found");
            return false;
        }

        if (self::checkSessionTimeout()) {
            error_log("Session has timed out");
            return false;
        }

        return true;
    }

    public static function getCurrentUser(): ?array
    {
        return self::isLoggedIn() ? $_SESSION[self::getSessionKey()] : null;
    }

    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . self::getLoginPath(self::$sessionType));
            exit();
        }
    }

    protected static function getSessionKey(): string
    {
        return self::$sessionType . '_data';
    }

    protected static function basicLogout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session data
            session_unset();

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }

            // Destroy session
            session_destroy();
        }
    }

    public static function logout()
    {
        $sessionKey = self::getSessionKey();
        $userData = $_SESSION[$sessionKey] ?? null;

        if ($userData && isset($userData['id'])) {
            try {
                $query = "UPDATE " . self::$sessionType . " SET last_logout = NOW() WHERE id = :id";
                $stmt = self::$db->prepare($query);
                $stmt->bindParam(':id', $userData['id']);
                $stmt->execute();

                // Log logout
                error_log("User logged out: " . $userData['email'] . " at " . date('Y-m-d H:i:s'));
            } catch (PDOException $e) {
                error_log("Logout Error: " . $e->getMessage());
            }
        }

        self::basicLogout();

        // Start new session for message
        session_start();
        $_SESSION['message'] = 'Anda telah berhasil logout';
        header('Location: ' . self::getLoginPath(self::$sessionType));
        exit();
    }

    protected static function checkSessionTimeout(): bool
    {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return true;
        }

        if ((time() - $_SESSION['LAST_ACTIVITY']) > self::$sessionTimeout) {
            self::basicLogout();
            return true;
        }

        $_SESSION['LAST_ACTIVITY'] = time();
        return false;
    }

    // Path methods remain the same...
    public static function getRedirectPath(string $role): string
    {
        switch ($role) {
            case self::ROLES['ADMIN']:
                return '/sistem/admin/index.php';
            case self::ROLES['STAFF']:
                return '/sistem/petugas/index.php';
            case self::ROLES['USERS']:
                return '/sistem/index.php';
            default:
                return '/sistem/public/auth/login.php';
        }
    }

    public static function getLoginPath(string $role): string
    {
        switch ($role) {
            case self::ROLES['ADMIN']:
                return '/sistem/admin/auth/login.php';
            case self::ROLES['STAFF']:
                return '/sistem/petugas/auth/login.php';
            case self::ROLES['USERS']:
                return '/sistem/public/auth/login.php';
            default:
                return '/sistem/public/auth/login.php';
        }
    }
}
