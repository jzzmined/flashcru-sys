<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php"); exit();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password']          ?? '';

    // Fix: Using 'user_id' and 'full_name' to match your table
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password FROM users WHERE email = ? AND role = 'admin'");
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id']; // Updated key
                $_SESSION['name']    = $user['full_name']; // Updated key
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = 'admin';
                logActivity($user['user_id'], 'Admin logged in');
                redirect('admin/dashboard.php');
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'No admin account found with that email.';
        }
    } else {
        $error = "System Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login &ndash; FlashCru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="fc-auth-page" style="background:linear-gradient(135deg,#0d1a2e 0%,#1a0a09 100%);">
    <div class="fc-auth-card">
        <div class="fc-auth-logo">
            <div class="fc-auth-logo-icon" style="background:var(--fc-dark);border:2px solid var(--fc-primary);">
                <i class="bi bi-shield-lock-fill" style="color:var(--fc-primary);"></i>
            </div>
            <div>
                <div class="fc-auth-brand-name">FlashCru Admin</div>
                <div class="fc-auth-brand-sub">Restricted Access</div>
            </div>
        </div>
        <div class="fc-auth-title">Administrator Login</div>
        <div class="fc-auth-desc">Enter admin credentials to access the control panel</div>

        <?php if ($error): ?>
        <div class="fc-alert fc-alert-error">
            <i class="bi bi-shield-x-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="fc-form-label">Admin Email</label>
                <input type="email" name="email" class="fc-form-control"
                       placeholder="admin@flashcru.ph" required>
            </div>
            <div class="mb-4">
                <label class="fc-form-label">Password</label>
                <input type="password" name="password" class="fc-form-control"
                       placeholder="Admin password" required>
            </div>
            <button type="submit" class="fc-btn-auth" style="background:var(--fc-dark);">
                Access Dashboard
            </button>
        </form>
        <div class="fc-auth-footer">
            <a href="login.php">&larr; Back to User Login</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>