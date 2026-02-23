<?php
/**
 * FlashCru Emergency Response System
 * Login Page - index.php
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
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
    <title>Login — FlashCru Emergency Response</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            font-size: 14px;
            background: #F0F5FF;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Left accent strip behind card */
        .login-shell {
            display: flex;
            width: 100%;
            max-width: 420px;
            flex-direction: column;
            align-items: center;
        }

        /* Brand above card */
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 64px; height: 64px;
            border-radius: 16px;
            overflow: hidden;
            margin: 0 auto 12px;
            box-shadow: 0 4px 16px rgba(220,38,38,0.25);
        }

        .brand-logo img {
            width: 100%; height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-name {
            font-size: 26px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
        }

        .brand-sub {
            font-size: 13px;
            color: #64748B;
            margin-top: 3px;
            font-weight: 400;
        }

        /* Card */
        .login-card {
            width: 100%;
            background: #FFFFFF;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(15,23,42,0.10), 0 1px 3px rgba(15,23,42,0.06);
            border: 1px solid #E2E8F0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 24px;
        }

        /* Error */
        .error-alert {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #0F172A;
            margin-bottom: 6px;
        }

        .form-input {
            display: block;
            width: 100%;
            padding: 10px 13px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #0F172A;
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 8px;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .form-input:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }

        .form-input::placeholder { color: #94A3B8; }

        /* Remember / Forgot */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 22px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #475569;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: #1D4ED8;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #1D4ED8;
            font-weight: 600;
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }

        /* Login button */
        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: #0F172A;
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            letter-spacing: 0.01em;
        }

        .btn-login:hover {
            background: #1E40AF;
            box-shadow: 0 4px 14px rgba(29,78,216,0.3);
            transform: translateY(-1px);
        }

        .btn-login:active { transform: translateY(0); }

        /* Divider */
        .divider {
            text-align: center;
            font-size: 11px;
            color: #94A3B8;
            margin: 20px 0 16px;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #E2E8F0;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        /* Demo box */
        .demo-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .demo-title {
            font-size: 10px;
            font-weight: 700;
            color: #1D4ED8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 7px;
        }

        .demo-row:last-child { margin-bottom: 0; }

        .demo-label { font-size: 12px; color: #475569; }

        .demo-val {
            font-size: 12px;
            font-family: monospace;
            background: rgba(29,78,216,0.1);
            color: #1D4ED8;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Footer */
        .login-footer {
            margin-top: 20px;
            font-size: 12px;
            color: #94A3B8;
            text-align: center;
        }

        /* Red top accent on card */
        .login-card::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #DC2626, #1D4ED8);
            border-radius: 20px 20px 0 0;
            margin: -32px -32px 24px -32px;
        }

        @media (max-width: 480px) {
            .login-card { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="login-shell">

    <!-- Brand -->
    <div class="brand-header">
        <div class="brand-logo">
            <img src="fc-logo.jpg" alt="FlashCru Logo">
        </div>
        <div class="brand-name">FlashCru</div>
        <div class="brand-sub">Emergency Response System</div>
    </div>

    <!-- Card -->
    <div class="login-card">

        <div class="card-title">Welcome back</div>
        <div class="card-sub">Sign in to access your dashboard</div>

        <?php if (!empty($error)): ?>
        <div class="error-alert">
            <span>⚠️</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-input"
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-extras">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login">
                ⚡ Flash In
            </button>

        </form>

        <div class="divider">DEMO CREDENTIALS</div>

        <div class="demo-box">
            <div class="demo-title">Test Accounts</div>
            <div class="demo-row">
                <span class="demo-label">Admin</span>
                <span class="demo-val">admin / admin123</span>
            </div>
            <div class="demo-row">
                <span class="demo-label">Dispatcher</span>
                <span class="demo-val">dispatcher / pass123</span>
            </div>
        </div>

    </div>

    <div class="login-footer">⚡ FlashCru Emergency Response System v1.0</div>

</div>

</body>
</html>