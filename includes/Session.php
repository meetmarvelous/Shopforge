<?php
/**
 * Session Management Class
 * 
 * Secure session handling with security best practices.
 * 
 * @package ShopForge
 * @version 1.0
 */

require_once __DIR__ . '/../config.php';

class Session
{
    private static bool $started = false;
    
    /**
     * Start session with secure settings
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        
        // Configure session settings before starting
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', SESSION_SAMESITE);
        
        if (SESSION_SECURE) {
            ini_set('session.cookie_secure', '1');
        }
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE
        ]);
        
        session_name(SESSION_NAME);
        session_start();
        
        // Check session expiration
        if (isset($_SESSION['_last_activity'])) {
            if (time() - $_SESSION['_last_activity'] > SESSION_LIFETIME) {
                self::destroy();
                self::start();
            }
        }
        
        $_SESSION['_last_activity'] = time();
        self::$started = true;
    }
    
    /**
     * Regenerate session ID (call after login/privilege change)
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
            self::$started = false;
        }
    }
    
    /**
     * Set a session value
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a session value
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Get all session data
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }
    
    /**
     * Set a flash message (one-time message)
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get and remove a flash message
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Check if flash message exists
     */
    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Get all flash messages and clear them
     */
    public static function getFlashes(): array
    {
        self::start();
        
        $flashes = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        
        return $flashes;
    }
    
    // =========================================================================
    // USER AUTHENTICATION HELPERS
    // =========================================================================
    
    /**
     * Set logged in user
     */
    public static function setUser(array $user): void
    {
        self::regenerate();
        self::set('user', $user);
        self::set('user_id', $user['id']);
        self::set('logged_in', true);
        self::set('login_time', time());
    }
    
    /**
     * Get current user
     */
    public static function getUser(): ?array
    {
        return self::get('user');
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::get('logged_in', false) === true && self::get('user_id') !== null;
    }
    
    /**
     * Logout user
     */
    public static function logout(): void
    {
        self::destroy();
    }
    
    // =========================================================================
    // ADMIN AUTHENTICATION HELPERS
    // =========================================================================
    
    /**
     * Set logged in admin
     */
    public static function setAdmin(array $admin): void
    {
        self::regenerate();
        self::set('admin', $admin);
        self::set('admin_id', $admin['id']);
        self::set('admin_role', $admin['role']);
        self::set('admin_logged_in', true);
        self::set('admin_login_time', time());
    }
    
    /**
     * Get current admin
     */
    public static function getAdmin(): ?array
    {
        return self::get('admin');
    }
    
    /**
     * Get current admin ID
     */
    public static function getAdminId(): ?int
    {
        return self::get('admin_id');
    }
    
    /**
     * Check if admin is logged in
     */
    public static function isAdminLoggedIn(): bool
    {
        return self::get('admin_logged_in', false) === true && self::get('admin_id') !== null;
    }
    
    /**
     * Get admin role
     */
    public static function getAdminRole(): ?string
    {
        return self::get('admin_role');
    }
    
    /**
     * Check if admin has specific role
     */
    public static function hasRole(string $role): bool
    {
        $currentRole = self::getAdminRole();
        
        if (!$currentRole) {
            return false;
        }
        
        // Role hierarchy: super_admin > admin > manager > staff
        $hierarchy = [
            'super_admin' => 4,
            'admin' => 3,
            'manager' => 2,
            'staff' => 1
        ];
        
        $currentLevel = $hierarchy[$currentRole] ?? 0;
        $requiredLevel = $hierarchy[$role] ?? 0;
        
        return $currentLevel >= $requiredLevel;
    }
    
    /**
     * Logout admin
     */
    public static function adminLogout(): void
    {
        self::remove('admin');
        self::remove('admin_id');
        self::remove('admin_role');
        self::remove('admin_logged_in');
        self::remove('admin_login_time');
        self::regenerate();
    }
    
    // =========================================================================
    // REDIRECT HELPERS
    // =========================================================================
    
    /**
     * Store intended URL for redirect after login
     */
    public static function setIntendedUrl(string $url): void
    {
        self::set('intended_url', $url);
    }
    
    /**
     * Get and clear intended URL
     */
    public static function getIntendedUrl(string $default = ''): string
    {
        $url = self::get('intended_url', $default);
        self::remove('intended_url');
        return $url;
    }
}

// Auto-start session
Session::start();
