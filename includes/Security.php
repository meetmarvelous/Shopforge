<?php
/**
 * Security Utilities Class
 * 
 * Provides security functions for CSRF protection, XSS prevention,
 * input validation, password hashing, and more.
 * 
 * @package ShopForge
 * @version 1.0
 */

require_once __DIR__ . '/../config.php';

class Security
{
    /**
     * Generate a CSRF token and store in session
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = [
            'token' => $token,
            'expires' => time() + CSRF_TOKEN_LIFETIME
        ];
        
        return $token;
    }
    
    /**
     * Get current CSRF token or generate new one
     */
    public static function getCSRFToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        
        // Check if token exists and is not expired
        if (isset($_SESSION[CSRF_TOKEN_NAME]) && 
            $_SESSION[CSRF_TOKEN_NAME]['expires'] > time()) {
            return $_SESSION[CSRF_TOKEN_NAME]['token'];
        }
        
        return self::generateCSRFToken();
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool
     */
    public static function validateCSRFToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        $storedData = $_SESSION[CSRF_TOKEN_NAME];
        
        // Check if token is expired
        if ($storedData['expires'] < time()) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($storedData['token'], $token);
    }
    
    /**
     * Generate CSRF hidden input field
     */
    public static function csrfField(): string
    {
        $token = self::getCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::escape($token) . '">';
    }
    
    /**
     * Escape output to prevent XSS
     * 
     * @param mixed $data Data to escape
     * @return string
     */
    public static function escape(mixed $data): string
    {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Alias for escape
     */
    public static function e(mixed $data): string
    {
        return self::escape($data);
    }
    
    /**
     * Escape output for JavaScript context
     */
    public static function escapeJs(mixed $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * Escape for HTML attribute
     */
    public static function escapeAttr(mixed $data): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return trim(strip_tags($input));
    }
    
    /**
     * Validate and sanitize email
     */
    public static function sanitizeEmail(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return $email ?: '';
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize integer
     */
    public static function sanitizeInt(mixed $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public static function sanitizeFloat(mixed $input): float
    {
        return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Validate integer within range
     */
    public static function isValidInt(mixed $input, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): bool
    {
        $value = filter_var($input, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max]
        ]);
        return $value !== false;
    }
    
    /**
     * Validate URL
     */
    public static function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(?string $url): string
    {
        if ($url === null) {
            return '';
        }
        return filter_var(trim($url), FILTER_SANITIZE_URL) ?: '';
    }
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password hash needs rehashing
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Validate password strength
     * 
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
        }
        
        if (PASSWORD_REQUIRE_MIXED_CASE) {
            if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain both uppercase and lowercase letters.";
            }
        }
        
        if (PASSWORD_REQUIRE_NUMBERS) {
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Password must contain at least one number.";
            }
        }
        
        if (PASSWORD_REQUIRE_SYMBOLS) {
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $errors[] = "Password must contain at least one special character.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generate a secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate a URL-safe token
     */
    public static function generateUrlSafeToken(int $length = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
    
    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(): string
    {
        return 'SF' . date('Ymd') . strtoupper(substr(self::generateToken(4), 0, 6));
    }
    
    /**
     * Sanitize filename for uploads
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any directory traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        
        // Remove multiple consecutive underscores/dashes
        $filename = preg_replace('/[_\-]+/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 195) . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Generate unique filename for uploads
     */
    public static function generateUniqueFilename(string $originalFilename): string
    {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        return self::generateToken(16) . '_' . time() . '.' . $ext;
    }
    
    /**
     * Validate file extension
     */
    public static function isAllowedExtension(string $filename, array $allowed): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowed);
    }
    
    /**
     * Validate file MIME type for images
     */
    public static function isValidImageMime(string $filepath): bool
    {
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        return in_array($mime, $allowedMimes);
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Rate limiting check
     * 
     * @param string $key Unique identifier (e.g., 'login_' . $ip)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $lockoutTime Lockout duration in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    public static function checkRateLimit(string $key, int $maxAttempts, int $lockoutTime): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        
        $rateLimitKey = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
        }
        
        $data = &$_SESSION[$rateLimitKey];
        
        // Check if locked
        if ($data['locked_until'] > time()) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $data['locked_until'] - time()
            ];
        }
        
        // Reset if window expired
        if (time() - $data['first_attempt'] > $lockoutTime) {
            $data = [
                'attempts' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
        }
        
        return [
            'allowed' => $data['attempts'] < $maxAttempts,
            'remaining' => max(0, $maxAttempts - $data['attempts']),
            'retry_after' => 0
        ];
    }
    
    /**
     * Increment rate limit counter
     */
    public static function incrementRateLimit(string $key, int $maxAttempts, int $lockoutTime): void
    {
        $rateLimitKey = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'locked_until' => 0
            ];
        }
        
        $_SESSION[$rateLimitKey]['attempts']++;
        
        // Lock if exceeded max attempts
        if ($_SESSION[$rateLimitKey]['attempts'] >= $maxAttempts) {
            $_SESSION[$rateLimitKey]['locked_until'] = time() + $lockoutTime;
        }
    }
    
    /**
     * Reset rate limit
     */
    public static function resetRateLimit(string $key): void
    {
        $rateLimitKey = 'rate_limit_' . $key;
        unset($_SESSION[$rateLimitKey]);
    }
    
    /**
     * Validate required POST fields
     * 
     * @param array $required Array of required field names
     * @return array ['valid' => bool, 'missing' => array]
     */
    public static function validateRequired(array $required): array
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }
    
    /**
     * Create slug from string
     */
    public static function createSlug(string $string): string
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = preg_replace('/^-+|-+$/', '', $string);
        return $string;
    }
}

/**
 * Shorthand functions
 */
function e(mixed $data): string
{
    return Security::escape($data);
}

function csrf_field(): string
{
    return Security::csrfField();
}

function csrf_token(): string
{
    return Security::getCSRFToken();
}
