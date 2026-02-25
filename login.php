<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'user'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = 'user';
            logActivity($user['id'], 'User logged in');
            redirect('user/dashboard.php');
        } else {
            $error = 'Incorrect password. Please try again.';
        }
    } else {
        $error = 'No account found with that email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &ndash; FlashCru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="fc-auth-page">
    <div class="fc-auth-card">
        <!-- Logo -->
        <div class="fc-auth-logo">
            <div class="fc-auth-logo-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="fc-auth-brand-name">FlashCru</div>
                <div class="fc-auth-brand-sub">Flash Crew Response System</div>
            </div>
        </div>

        <div class="fc-auth-title">Welcome Back</div>
        <div class="fc-auth-desc">Sign in to your community account</div>

        <?php if ($error): ?>
        <div class="fc-alert fc-alert-error">
            <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="fc-form-label">Email Address</label>
                <input type="email" name="email" class="fc-form-control"
                       placeholder="you@example.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label class="fc-form-label">Password</label>
                <input type="password" name="password" class="fc-form-control"
                       placeholder="Your password" required>
            </div>
            <button type="submit" class="fc-btn-auth">Sign In</button>
        </form>

        <div class="fc-auth-footer">
            Don't have an account? <a href="register.php">Register here</a><br>
            <a href="admin_login.php" style="color:var(--fc-muted);font-size:12px;font-weight:400;">
                Admin? Click here
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>