<?php
/**
 * FlashCru Emergency Response System
 * Authentication & Authorization System
 * 
 * @package FlashCru
 * @version 1.0.0
 */

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in (redirect to login if not)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: index.php');
        exit();
    }
}

/**
 * Check if user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is dispatcher
 * 
 * @return bool True if dispatcher, false otherwise
 */
function isDispatcher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dispatcher';
}

/**
 * Check if user is responder
 * 
 * @return bool True if responder, false otherwise
 */
function isResponder() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'responder';
}

/**
 * Require admin access (redirect if not admin)
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die('<h1>403 Forbidden</h1><p>You need administrator privileges to access this page.</p>');
    }
}

/**
 * Require dispatcher or admin access
 */
function requireDispatcher() {
    requireLogin();
    if (!isDispatcher() && !isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die('<h1>403 Forbidden</h1><p>You need dispatcher privileges to access this page.</p>');
    }
}

/**
 * Login user with username and password
 * 
 * @param string $username Username
 * @param string $password Password (plain text)
 * @return bool|string True on success, error message on failure
 */
function login($username, $password) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Fetch user from database
        $stmt = $conn->prepare("
            SELECT * FROM users 
            WHERE username = ? AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists
        if (!$user) {
            return 'Invalid username or password';
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return 'Invalid username or password';
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Update last login timestamp
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        
        // Log login activity
        logActivity($user['user_id'], null, 'login', 'User logged in successfully');
        
        return true;
        
    } catch(PDOException $e) {
        error_log("FlashCru Login Error: " . $e->getMessage());
        return 'Login failed. Please try again.';
    }
}

/**
 * Logout current user
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        // Log logout activity
        logActivity($_SESSION['user_id'], null, 'logout', 'User logged out');
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login page
    header('Location: index.php');
    exit();
}

/**
 * Get current logged-in user data
 * 
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get user's full name
 * 
 * @return string User's full name
 */
function getUserFullName() {
    return $_SESSION['full_name'] ?? 'Guest';
}

/**
 * Get user's role display name
 * 
 * @return string Role display name
 */
function getUserRoleName() {
    if (!isLoggedIn()) {
        return 'Guest';
    }
    
    $roles = [
        'admin' => 'Administrator',
        'dispatcher' => 'Dispatcher',
        'responder' => 'Responder'
    ];
    
    return $roles[$_SESSION['role']] ?? 'Unknown';
}

/**
 * Get user initials for avatar
 * 
 * @return string User initials (2 letters)
 */
function getUserInitials() {
    if (!isLoggedIn()) {
        return '?';
    }
    
    $name = $_SESSION['full_name'] ?? '';
    $parts = explode(' ', $name);
    
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    
    return strtoupper(substr($name, 0, 2));
}

/**
 * Check if user has specific permission
 * 
 * @param string $permission Permission name
 * @return bool True if user has permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin has all permissions
    if (isAdmin()) {
        return true;
    }
    
    // Define permissions by role
    $permissions = [
        'dispatcher' => [
            'view_incidents',
            'create_incidents',
            'edit_incidents',
            'assign_teams',
            'view_teams',
            'edit_team_status',
            'generate_reports',
            'view_activity_log'
        ],
        'responder' => [
            'view_incidents',
            'view_assigned_incidents',
            'update_incident_status',
            'view_teams'
        ]
    ];
    
    $role = $_SESSION['role'] ?? 'guest';
    
    if (isset($permissions[$role])) {
        return in_array($permission, $permissions[$role]);
    }
    
    return false;
}

/**
 * Log user activity to database
 * 
 * @param int $user_id User ID
 * @param int|null $incident_id Incident ID (optional)
 * @param string $action Action performed
 * @param string $details Action details
 * @return bool True on success
 */
function logActivity($user_id, $incident_id, $action, $details) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, incident_id, action, details) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$user_id, $incident_id, $action, $details]);
        
    } catch(PDOException $e) {
        error_log("FlashCru Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create new user (admin only)
 * 
 * @param array $data User data
 * @return int|false User ID on success, false on failure
 */
function createUser($data) {
    if (!isAdmin()) {
        return false;
    }
    
    // Hash password
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    $db = new Database();
    return $db->insert('users', $data);
}

/**
 * Update user data
 * 
 * @param int $user_id User ID to update
 * @param array $data Data to update
 * @return bool True on success
 */
function updateUser($user_id, $data) {
    // Only admin can update other users, users can update themselves
    if (!isAdmin() && $_SESSION['user_id'] != $user_id) {
        return false;
    }
    
    $db = new Database();
    
    // Hash password if being updated
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    } else {
        unset($data['password']); // Don't update password if empty
    }
    
    return $db->update('users', $data, 'user_id = :user_id', ['user_id' => $user_id]);
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if session is about to expire
 * 
 * @return bool True if session will expire in 5 minutes
 */
function isSessionExpiringSoon() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    $time_remaining = SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
    return $time_remaining < 300; // Less than 5 minutes
}

/**
 * Get session time remaining in seconds
 * 
 * @return int Seconds remaining
 */
function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    return SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
}

?>