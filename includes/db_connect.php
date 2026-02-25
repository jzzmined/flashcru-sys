<?php
/**
 * FlashCru Emergency Response System
 * Configuration File
 * 
 * @package FlashCru
 * @version 1.0.0
 */

// ===========================================
// DATABASE CONFIGURATION
// ===========================================
// IMPORTANT: Change these to match your setup!

define('DB_HOST', 'localhost');        // Usually 'localhost'
define('DB_USER', 'root');             // Your MySQL username (default: root)
define('DB_PASS', '');                 // Your MySQL password (default: empty for XAMPP)
define('DB_NAME', 'emergency_db'); // Database name

// ===========================================
// SITE CONFIGURATION
// ===========================================

define('SITE_NAME', 'FlashCru');
define('SITE_FULL_NAME', 'FlashCru Emergency Response System');
define('SITE_TAGLINE', 'Your Emergency Cru');
define('SITE_VERSION', '1.0.0');

// Base URL (change if not in root directory)
define('SITE_URL', 'http://localhost/emergency-response-system');

// ===========================================
// PATH CONFIGURATION
// ===========================================

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('API_PATH', ROOT_PATH . '/api');

// ===========================================
// SESSION CONFIGURATION
// ===========================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 minutes = 1800 seconds)
define('SESSION_TIMEOUT', 1800);

// Check if session has timed out
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session expired
    session_unset();
    session_destroy();
    if (basename($_SERVER['PHP_SELF']) != 'index.php') {
        header('Location: index.php?timeout=1');
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// ===========================================
// TIMEZONE CONFIGURATION
// ===========================================

date_default_timezone_set('Asia/Manila'); // Change to your timezone

// ===========================================
// ERROR REPORTING
// ===========================================
// For Development: Show all errors
// For Production: Set to 0

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/error.log');

// ===========================================
// SECURITY HEADERS
// ===========================================

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// ===========================================
// MAP CONFIGURATION
// ===========================================

// Default map center (Davao City, Philippines)
define('MAP_CENTER_LAT', 7.0731);
define('MAP_CENTER_LNG', 125.6128);
define('MAP_ZOOM_LEVEL', 13);

// Auto-refresh interval (seconds)
define('AUTO_REFRESH_INTERVAL', 30);

// ===========================================
// PAGINATION SETTINGS
// ===========================================

define('ITEMS_PER_PAGE', 10);

// ===========================================
// FILE UPLOAD SETTINGS
// ===========================================

define('UPLOAD_PATH', ROOT_PATH . '/assets/images/uploads');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ===========================================
// HELPER FUNCTIONS
// ===========================================

/**
 * Sanitize input data to prevent XSS attacks
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * 
 * @param string $date Date string to format
 * @param string $format Desired format (default: 'F j, Y g:i A')
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y g:i A') {
    if (empty($date)) {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Generate CSRF token for form security
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to another page
 * 
 * @param string $page Page to redirect to
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Display success message
 * 
 * @param string $message Success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 * 
 * @param string $message Error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 * 
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

// ===========================================
// APPLICATION CONSTANTS
// ===========================================

// Incident types
define('INCIDENT_TYPES', [
    'fire' => 'Fire Emergency',
    'medical' => 'Medical Emergency',
    'accident' => 'Traffic Accident',
    'rescue' => 'Rescue Operation',
    'other' => 'Other'
]);

// Incident status
define('INCIDENT_STATUS', [
    'pending' => 'Pending',
    'active' => 'Active',
    'critical' => 'Critical',
    'resolved' => 'Resolved'
]);

// Priority levels
define('PRIORITY_LEVELS', [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'critical' => 'Critical'
]);

// Team types
define('TEAM_TYPES', [
    'fire' => 'Fire Department',
    'medical' => 'Medical Services',
    'police' => 'Police Department',
    'rescue' => 'Rescue Team'
]);

// Team status
define('TEAM_STATUS', [
    'available' => 'Available',
    'busy' => 'Busy',
    'offline' => 'Offline'
]);

// User roles
define('USER_ROLES', [
    'admin' => 'Administrator',
    'dispatcher' => 'Dispatcher',
    'responder' => 'Responder'
]);

// ===========================================
// CONFIGURATION CHECK
// ===========================================

// Verify database connection is configured
if (DB_HOST === '' || DB_NAME === '') {
    die('Error: Database configuration is incomplete. Please check includes/config.php');
}

// All configuration loaded successfully
?>