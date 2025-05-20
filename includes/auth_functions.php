<?php
/**
 * Authentication functions for admin users
 * Enhanced with modern security practices
 */

// Start session securely
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirect if not logged in
 * @param string $redirectUrl URL to redirect to
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Attempt to log in a user
 * @param string $username Username
 * @param string $password Password
 * @return bool True if login successful
 */
function login($username, $password) {
    try {
        $pdo = getDbConnection();
        
        // Use prepared statement for security
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            // Update last login time
            $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return true;
        }
        
        // Add small delay to prevent timing attacks
        sleep(1);
        return false;
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log out the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Change admin password
 * @param int $adminId Admin ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return bool True if password changed
 */
function changePassword($adminId, $currentPassword, $newPassword) {
    try {
        $pdo = getDbConnection();
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            // Add small delay to prevent timing attacks
            sleep(1);
            return false; // Current password is incorrect
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $adminId]);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        return $result;
    } catch (PDOException $e) {
        error_log('Change password error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get admin user information
 * @param int $adminId Admin ID
 * @return array|null User data or null if not found
 */
function getAdminInfo($adminId) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT id, username, created_at, last_login
            FROM admins 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Get admin info error: ' . $e->getMessage());
        return null;
    }
} 