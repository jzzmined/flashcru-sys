<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = sanitize($_POST['name']        ?? '');
    $email      = sanitize($_POST['email']       ?? '');
    $phone      = sanitize($_POST['phone']       ?? '');
    $bar_id     = (int)($_POST['barangay_id']    ?? 0);
    $password   = $_POST['password']             ?? '';
    $confirm    = $_POST['confirm']              ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (name,email,phone,barangay_id,password,role,created_at) VALUES(?,?,?,?,?,'user',NOW())");
            $ins->bind_param("sssss", $name, $email, $phone, $bar_id, $hash);
            if ($ins->execute()) {
                $success = 'Account created! You can now <a href="login.php">sign in</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
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
<div class="fc-auth-page">
    <div class="fc-auth-card" style="max-width:520px;">
        <div class="fc-auth-logo">
            <div class="fc-auth-logo-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="fc-auth-brand-name">FlashCru</div>
                <div class="fc-auth-brand-sub">Flash Crew Response System</div>
            </div>
        </div>
        <div class="fc-auth-title">Create Account</div>
        <div class="fc-auth-desc">Join the community response network</div>

        <?php if ($error): ?>
        <div class="fc-alert fc-alert-error"><i class="bi bi-exclamation-circle-fill"></i> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-12">
                    <label class="fc-form-label">Full Name <span style="color:var(--fc-primary)">*</span></label>
                    <input type="text" name="name" class="fc-form-control"
                           placeholder="Juan dela Cruz" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="fc-form-label">Email Address <span style="color:var(--fc-primary)">*</span></label>
                    <input type="email" name="email" class="fc-form-control"
                           placeholder="you@example.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="fc-form-label">Phone Number</label>
                    <input type="text" name="phone" class="fc-form-control"
                           placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="fc-form-label">Barangay</label>
                    <select name="barangay_id" class="fc-form-control">
                        <option value="">Select your barangay</option>
                        <?php while ($b = $barangays->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>"
                            <?= (isset($_POST['barangay_id']) && $_POST['barangay_id'] == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fc-form-label">Password <span style="color:var(--fc-primary)">*</span></label>
                    <input type="password" name="password" class="fc-form-control"
                           placeholder="Min. 6 characters" required>
                </div>
                <div class="col-md-6">
                    <label class="fc-form-label">Confirm Password <span style="color:var(--fc-primary)">*</span></label>
                    <input type="password" name="confirm" class="fc-form-control"
                           placeholder="Repeat password" required>
                </div>
                <div class="col-12 mt-1">
                    <button type="submit" class="fc-btn-auth">Create Account</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="fc-auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>