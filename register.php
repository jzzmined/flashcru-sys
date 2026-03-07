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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
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
            $hash     = password_hash($password, PASSWORD_DEFAULT);
            // FIX: Derive username from name, not email
            $username = strtolower(str_replace(' ', '_', $name)) . time();
            // FIX: role is 'user' (not 'responder') so auth_user.php lets them log in
            $ins = $conn->prepare("INSERT INTO users (username, full_name, email, contact_number, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'user', 'active', NOW())");
            $ins->bind_param("sssss", $username, $name, $email, $phone, $hash);
            if ($ins->execute()) {
                $new_id = $conn->insert_id;
                logActivity($new_id, "New user registered: $name");
                $success = 'Account created! You can now <a href="login.php">sign in</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register &ndash; FlashCru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card auth-card--register">

        <div class="auth-logo">
            <div class="auth-logo-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="auth-brand-name">FlashCru</div>
                <div class="auth-brand-sub">Flash Crew Response System</div>
            </div>
        </div>

        <div class="auth-title">Create Account</div>
        <div class="auth-desc">Join the community response network</div>

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
                    <div style="position:relative;">
                        <input type="password" name="password" id="reg_password" class="auth-form-control"
                            placeholder="Min. 6 characters" required style="padding-right:42px;">
                        <button type="button" onclick="togglePw('reg_password','eyeIcon1')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                            <i class="bi bi-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <!-- Password strength indicator -->
                    <div id="pwStrength" style="margin-top:6px;height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden;">
                        <div id="pwStrengthBar" style="height:100%;width:0;border-radius:4px;transition:all .3s;"></div>
                    </div>
                    <div id="pwStrengthLabel" style="font-size:11px;color:#94a3b8;margin-top:3px;"></div>
                </div>
                <div class="col-md-6">
                    <label class="auth-form-label">Confirm Password <span class="auth-required">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="confirm" id="reg_confirm" class="auth-form-control"
                            placeholder="Repeat password" required style="padding-right:42px;">
                        <button type="button" onclick="togglePw('reg_confirm','eyeIcon2')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                            <i class="bi bi-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                    <div id="pwMatchLabel" style="font-size:11px;margin-top:3px;"></div>
                </div>
                <div class="col-12 mt-1">
                    <button type="submit" class="auth-submit-btn auth-submit-btn--register">
                        <i class="bi bi-person-plus-fill me-2"></i> Create Account
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
    else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
}

// Password strength meter
document.getElementById('reg_password').addEventListener('input', function() {
    const v = this.value;
    const bar = document.getElementById('pwStrengthBar');
    const lbl = document.getElementById('pwStrengthLabel');
    let score = 0;
    if (v.length >= 6) score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const levels = [
        { pct: '0%', color: '#e2e8f0', text: '' },
        { pct: '25%', color: '#ef4444', text: 'Weak' },
        { pct: '50%', color: '#f59e0b', text: 'Fair' },
        { pct: '75%', color: '#3b82f6', text: 'Good' },
        { pct: '100%', color: '#10b981', text: 'Strong' },
    ];
    const l = levels[Math.min(score, 4)];
    bar.style.width = v.length ? l.pct : '0%';
    bar.style.background = l.color;
    lbl.textContent = v.length ? l.text : '';
    lbl.style.color = l.color;
});

// Confirm password match indicator
document.getElementById('reg_confirm').addEventListener('input', function() {
    const pw = document.getElementById('reg_password').value;
    const lbl = document.getElementById('pwMatchLabel');
    if (!this.value) { lbl.textContent = ''; return; }
    if (this.value === pw) { lbl.textContent = '✓ Passwords match'; lbl.style.color = '#10b981'; }
    else { lbl.textContent = '✗ Passwords do not match'; lbl.style.color = '#ef4444'; }
});
</script>
</body>
</html>