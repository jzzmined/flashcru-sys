<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name']     ?? '');
    $email    = sanitize($_POST['email']    ?? '');
    $phone    = sanitize($_POST['phone']    ?? '');
    $password = $_POST['password']          ?? '';
    $confirm  = $_POST['confirm']           ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (username,full_name,email,contact_number,password,role,status,created_at) VALUES(?,?,?,?,?,'responder','active',NOW())");
            $ins->bind_param("sssss", $email, $name, $email, $phone, $hash);
            if ($ins->execute()) {
                $success = 'Account created! You can now <a href="login.php">sign in</a>.';
            } else {
                $error = 'Registration failed: ' . $ins->error;
            }
        }
    }
}

$barangays = $conn->query("SELECT id, name FROM barangays ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register &ndash; FlashCru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- PAGE WRAPPER | class: auth-page -->
<div class="auth-page">

    <!-- CARD | class: auth-card auth-card--register (wider variant) -->
    <div class="auth-card auth-card--register">

        <!-- LOGO | classes: auth-logo, auth-logo-icon, auth-brand-name, auth-brand-sub -->
        <div class="auth-logo">
            <div class="auth-logo-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="auth-brand-name">FlashCru</div>
                <div class="auth-brand-sub">Flash Crew Response System</div>
            </div>
        </div>

        <!-- HEADING | classes: auth-title, auth-desc -->
        <div class="auth-title">Create Account</div>
        <div class="auth-desc">Join the community response network</div>

        <!-- ALERTS | classes: auth-alert, auth-alert--error, auth-alert--success -->
        <?php if ($error): ?>
            <div class="auth-alert auth-alert--error">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="auth-alert auth-alert--success">
                <i class="bi bi-check-circle-fill"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- FORM | classes: auth-register-form -->
        <!-- auth-form-label, auth-form-control -->
        <!-- auth-submit-btn auth-submit-btn--register -->
        <?php if (!$success): ?>
            <form method="POST" novalidate class="auth-register-form">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="auth-form-label">Full Name <span class="auth-required">*</span></label>
                        <input type="text" name="name" class="auth-form-control"
                            placeholder="Juan dela Cruz" required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="auth-form-label">Email Address <span class="auth-required">*</span></label>
                        <input type="email" name="email" class="auth-form-control"
                            placeholder="you@example.com" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="auth-form-label">Phone Number</label>
                        <input type="text" name="phone" class="auth-form-control"
                            placeholder="09XXXXXXXXX"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="auth-form-label">Password <span class="auth-required">*</span></label>
                        <input type="password" name="password" class="auth-form-control"
                            placeholder="Min. 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label class="auth-form-label">Confirm Password <span class="auth-required">*</span></label>
                        <input type="password" name="confirm" class="auth-form-control"
                            placeholder="Repeat password" required>
                    </div>
                    <div class="col-12 mt-1">
                        <button type="submit" class="auth-submit-btn auth-submit-btn--register">Create Account</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- FOOTER LINKS | class: auth-footer -->
        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>