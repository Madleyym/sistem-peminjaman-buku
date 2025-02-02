<?php
// File: C:\xampp\htdocs\sistem\config\auth-session.php

class AuthSession
{ // Di dalam class AuthSession
    private const TOKEN_LENGTH = 32;
    private const TOKEN_NAME = 'csrf_token';
    private static $csrf_token = null;
    private static $instance = null;
    private static $sessionType;
    private static $sessionTimeout = 1800; // 30 minutes
    private static $db = null; // Pastikan ini ada
    private const SESSION_PREFIX = 'libsys_';
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    // Constants untuk roles
    public const ROLES = [
        'ADMIN' => 'admin',
        'STAFF' => 'staff',
        'USERS' => 'users'
    ];
    // Di file config/auth-session.php
    public static function isAdmin(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === self::ROLES['ADMIN'];
    }
    public static function regenerateCSRFToken(): string
    {
        self::clearCSRFToken();
        return self::generateCSRFToken();
    }
    public static function generateCSRFToken(): string
    {
        if (self::$csrf_token === null) {
            // Generate token if not exists in session
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            self::$csrf_token = $_SESSION['csrf_token'];
        }
        return self::$csrf_token;
    }

    public static function validateCSRFToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Method untuk menghapus token saat logout
    public static function clearCSRFToken(): void
    {
        self::$csrf_token = null;
        unset($_SESSION['csrf_token']);
    }


    // Constructor juga perlu diupdate
    private function __construct($sessionType)
    {
        try {
            // Set timezone
            date_default_timezone_set(self::DEFAULT_TIMEZONE);

            // Set session type
            self::$sessionType = $sessionType;

            // Initialize session if not started
            if (session_status() === PHP_SESSION_NONE) {
                // Set session security parameters
                $this->initializeSessionSecurity();

                // Set session name
                session_name(self::SESSION_PREFIX . $sessionType);

                // Start session with secure parameters
                session_start([
                    'cookie_lifetime' => 0,
                    'gc_maxlifetime' => self::$sessionTimeout,
                    'use_strict_mode' => 1,
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Strict'
                ]);
            }

            // Initialize database connection
            if (self::$db === null) {
                require_once __DIR__ . '/database.php';
                $database = new database();
                self::$db = $database->getConnection();
            }

            // Set or update session activity
            $_SESSION['LAST_ACTIVITY'] = time();

            // Set user role in session
            $_SESSION['user_role'] = $sessionType;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Session initialization error: " . $e->getMessage());
            throw new RuntimeException("Failed to initialize session: " . $e->getMessage());
        }
    }
    // private function initializeSessionSecurity(): void
    // {
    //     ini_set('session.cookie_httponly', 1);
    //     ini_set('session.use_only_cookies', 1);
    //     ini_set('session.cookie_samesite', 'Strict');
    //     ini_set('session.gc_maxlifetime', self::$sessionTimeout);
    //     ini_set('session.use_strict_mode', 1);
    //     // Disable secure cookie jika tidak menggunakan HTTPS
    //     ini_set('session.cookie_secure', 0);
    // }

    // private function initializeDatabase(): void
    // {
    //     if (self::$db === null) {
    //         require_once __DIR__ . '/database.php';
    //         $database = new Database();
    //         self::$db = $database->getConnection();
    //     }
    // }

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
                $_SESSION[$sessionKey] = [
                    'id' => $result['user']['id'],
                    'name' => $result['user']['name'],
                    'role' => $role,
                    'last_activity' => time(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ];

                $_SESSION['LAST_ACTIVITY'] = time();
                $_SESSION['user_role'] = $role;

                return true;
            }

            error_log("Failed login attempt for email: {$data['email']}");
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
    public static function requireRole(string $requiredRole)
    {
        // First check if user is logged in
        self::requireLogin();

        // Get current user's role
        $currentRole = $_SESSION['user_role'] ?? null;

        // Debug logging
        error_log("Checking role requirement: Required={$requiredRole}, Current={$currentRole}");

        // Check if user has the required role
        if ($currentRole !== $requiredRole) {
            error_log("Unauthorized access attempt: User with role {$currentRole} tried to access {$requiredRole} area");

            // Redirect to appropriate page based on their actual role
            if ($currentRole) {
                header('Location: ' . self::getRedirectPath($currentRole));
            } else {
                header('Location: ' . self::getLoginPath($requiredRole));
            }
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

    public static function logActivity(string $action, string $role, string $userId): bool
    {
        try {
            // Initialize database if not yet initialized
            if (self::$db === null) {
                self::initializeDatabase();
            }

            $query = "INSERT INTO activity_logs (
                user_id, 
                role, 
                action_type, 
                description, 
                ip_address, 
                user_agent, 
                created_at
            ) VALUES (
                :user_id, 
                :role, 
                'access', 
                :description, 
                :ip_address, 
                :user_agent, 
                NOW()
            )";

            $stmt = self::$db->prepare($query);

            $result = $stmt->execute([
                ':user_id' => $userId,
                ':role' => $role,
                ':description' => $action,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            if (!$result) {
                error_log("Failed to execute activity log query");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Log Activity Error: " . $e->getMessage());
            return false;
        }
    }

    private static function initializeDatabase(): void
    {
        try {
            if (self::$db === null
            ) {
                require_once __DIR__ . '/database.php';
                $database = new Database();
                self::$db = $database->getConnection();
            }
        } catch (Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            throw new RuntimeException("Failed to initialize database: " . $e->getMessage());
        }
    }

    private function initializeSessionSecurity(): void
    {
        // Session security settings
        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Additional security headers
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
    }

    // Add getCurrentUser method if not exists

    // Add method to get login time
    public static function getLoginTime(): string
    {
        return self::getCurrentUser()['login_time'];
    }

    // Add method to get username
    public static function getUsername(): string
    {
        return self::getCurrentUser()['username'];
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
