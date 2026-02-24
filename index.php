<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = login($username, $password);
        if ($result === true) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — FlashCru Emergency Response</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary:      #FD5E53;
            --primary-dark: #E04840;
            --green:        #21BF73;
            --green-light:  #B0EACD;
            --bg:           #F9FCFB;
            --surface:      #FFFFFF;
            --navy:         #0D1B2A;
            --muted:        #64748B;
            --subtle:       #94A3B8;
            --faint:        #E2E8F0;
        }

        html { -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            font-size: 14px;
            min-height: 100vh;
            background: var(--bg);
            display: flex;
            align-items: stretch;
        }

        /* ── Left Panel ─────────────────────────────── */
        .login-left {
            width: 420px;
            flex-shrink: 0;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 44px;
            box-shadow: 4px 0 32px rgba(0,0,0,0.06);
            position: relative;
            z-index: 2;
        }

        /* ── Right Panel ─────────────────────────────── */
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, #FD5E53 0%, #E04840 40%, #0D1B2A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(176,234,205,0.15) 0%, transparent 70%);
            top: -200px; right: -100px;
            border-radius: 50%;
        }

        .login-right::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            bottom: -100px; left: 80px;
            border-radius: 50%;
        }

        /* ── Brand ───────────────────────────────────── */
        .brand-wrap { text-align: center; margin-bottom: 36px; }

        .brand-icon {
            width: 62px; height: 62px;
            border-radius: 18px;
            background: var(--primary);
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 8px 24px rgba(253,94,83,0.38);
        }

        .brand-name {
            font-family: 'Lexend', sans-serif;
            font-size: 26px;
            font-weight: 900;
            color: var(--navy);
            letter-spacing: -0.5px;
        }

        .brand-sub {
            font-size: 12.5px;
            color: var(--subtle);
            margin-top: 3px;
        }

        /* ── Form styles ─────────────────────────────── */
        .form-title {
            font-family: 'Lexend', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 4px;
        }

        .form-sub {
            font-size: 13px;
            color: var(--subtle);
            margin-bottom: 26px;
        }

        .form-group { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            display: block;
            width: 100%;
            padding: 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--navy);
            background: #F4F7F6;
            border: 1.5px solid transparent;
            border-radius: 10px;
            outline: none;
            transition: all 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(253,94,83,0.10);
        }

        input::placeholder { color: var(--subtle); }

        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 20px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
            cursor: pointer;
        }

        .checkbox-label input { accent-color: var(--primary); cursor: pointer; }

        .forgot-link {
            font-size: 13px;
            color: var(--primary);
            font-weight: 600;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 11px;
            font-family: 'Lexend', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s;
            box-shadow: 0 6px 20px rgba(253,94,83,0.35);
            letter-spacing: 0.02em;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(253,94,83,0.45);
        }

        /* Divider */
        .divider {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--subtle);
            letter-spacing: 0.10em;
            text-transform: uppercase;
            margin: 20px 0 16px;
            position: relative;
        }

        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 38%;
            height: 1px;
            background: var(--faint);
        }

        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        /* Demo Box */
        .demo-box {
            background: #F4F7F6;
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid var(--faint);
        }

        .demo-label {
            font-size: 9.5px;
            font-weight: 800;
            color: #3B82F6;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 10px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .demo-row:last-child { margin-bottom: 0; }
        .demo-row-label { font-size: 12.5px; color: var(--muted); }
        .demo-val {
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            background: rgba(253,94,83,0.10);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Error */
        .error-box {
            background: rgba(253,94,83,0.08);
            border: 1px solid rgba(253,94,83,0.25);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            color: #9B1C1C;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }

        /* Right panel content */
        .right-content { position: relative; z-index: 1; text-align: center; max-width: 480px; }

        .right-headline {
            font-family: 'Lexend', sans-serif;
            font-size: 40px;
            font-weight: 900;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .right-headline span { color: var(--green-light); }

        .right-sub {
            font-size: 16px;
            color: rgba(255,255,255,0.65);
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .stat-chip {
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 14px;
            padding: 16px 20px;
            text-align: center;
            min-width: 120px;
        }

        .stat-chip-val {
            font-family: 'Lexend', sans-serif;
            font-size: 28px;
            font-weight: 900;
            color: #fff;
        }

        .stat-chip-val.green { color: var(--green-light); }

        .stat-chip-label {
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            margin-top: 4px;
            font-weight: 500;
        }

        .footer-txt {
            margin-top: 24px;
            font-size: 11.5px;
            color: var(--subtle);
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-right { display: none; }
            .login-left  { width: 100%; max-width: 460px; margin: auto; box-shadow: none; }
        }
    </style>
</head>
<body>

<!-- Left: Login Form -->
<div class="login-left">
    <div class="brand-wrap">
        <div class="brand-icon">⚡</div>
        <div class="brand-name">FlashCru</div>
        <div class="brand-sub">Emergency Response System</div>
    </div>

    <div style="width:100%;">
        <div class="form-title">Welcome back</div>
        <div class="form-sub">Sign in to access the response dashboard</div>

        <?php if (!empty($error)): ?>
        <div class="error-box">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="Enter your username"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password"
                       required autocomplete="current-password">
            </div>
            <div class="form-extras">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn-login">⚡ Flash In</button>
        </form>

        <div class="divider">Test Credentials</div>

        <div class="demo-box">
            <div class="demo-label">Demo Accounts</div>
            <div class="demo-row">
                <span class="demo-row-label">Admin</span>
                <span class="demo-val" onclick="setCredentials('admin','admin123')">admin / admin123</span>
            </div>
            <div class="demo-row">
                <span class="demo-row-label">Dispatcher</span>
                <span class="demo-val" onclick="setCredentials('dispatcher','pass123')">dispatcher / pass123</span>
            </div>
        </div>
    </div>

    <div class="footer-txt">⚡ FlashCru Emergency Response System v4.0</div>
</div>

<!-- Right: Hero Panel -->
<div class="login-right">
    <div class="right-content">
        <div class="right-headline">
            Fast, <span>Coordinated</span><br>Emergency Response
        </div>
        <p class="right-sub">
            FlashCru connects dispatchers, response teams, and community reporters in one unified civic-tech platform.
        </p>
        <div class="stats-row">
            <div class="stat-chip">
                <div class="stat-chip-val green">98%</div>
                <div class="stat-chip-label">Response Rate</div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-val">4.2m</div>
                <div class="stat-chip-label">Avg. Response</div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-val green">24/7</div>
                <div class="stat-chip-label">Monitoring</div>
            </div>
        </div>
    </div>
</div>

<script>
function setCredentials(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>
</body>
</html>