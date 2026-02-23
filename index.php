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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
          --bg:           #EEF1F7;
          --white:        #FAFCFF;
          --navy:         #1A2340;
          --indigo:       #3D5AF1;
          --red:          #E53935;
          --red-light:    #FDEAEA;
          --text-primary: #1A2340;
          --text-muted:   #8E9BBE;
          --border:       rgba(160,180,220,0.25);
          --neu-out:      6px 6px 14px rgba(160,180,220,0.45), -4px -4px 10px rgba(255,255,255,0.80);
          --neu-inset:    inset 3px 3px 8px rgba(160,180,220,0.35), inset -3px -3px 8px rgba(255,255,255,0.75);
          --neu-card:     4px 4px 12px rgba(150,170,210,0.30), -3px -3px 8px rgba(255,255,255,0.85);
        }

        html { -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            font-size: 14px;
            background: var(--bg);
            background-image:
              radial-gradient(ellipse at 15% 50%, rgba(61,90,241,0.09) 0%, transparent 55%),
              radial-gradient(ellipse at 85% 15%, rgba(229,57,53,0.08) 0%, transparent 50%),
              radial-gradient(ellipse at 70% 85%, rgba(61,90,241,0.06) 0%, transparent 45%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Brand */
        .brand-header { text-align: center; margin-bottom: 28px; }

        .brand-logo {
            width: 68px; height: 68px;
            border-radius: 20px;
            background: linear-gradient(135deg, #E53935, #C62828);
            margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            box-shadow: 6px 6px 14px rgba(229,57,53,0.30), -3px -3px 8px rgba(255,255,255,0.80);
        }

        .brand-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }

        .brand-name {
            font-size: 28px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.5px;
        }

        .brand-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Card */
        .login-card {
            width: 100%;
            background: var(--white);
            border-radius: 24px;
            padding: 36px;
            box-shadow: var(--neu-out);
            border: 1px solid rgba(255,255,255,0.70);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, var(--red), var(--indigo));
            border-radius: 24px 24px 0 0;
            margin: -36px -36px 30px -36px;
        }

        .card-title { font-size: 19px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .card-sub   { font-size: 13px; color: var(--text-muted); margin-bottom: 26px; }

        /* Error */
        .error-alert {
            background: var(--red-light);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--red);
            box-shadow: inset 0 0 0 1px rgba(229,57,53,0.20);
        }

        /* Form */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #5A6787;
            margin-bottom: 7px;
        }

        .form-input {
            display: block;
            width: 100%;
            padding: 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            color: var(--navy);
            background: var(--bg);
            border: 1.5px solid transparent;
            border-radius: 10px;
            box-shadow: var(--neu-inset);
            transition: all 0.22s;
            outline: none;
        }

        .form-input:focus {
            border-color: rgba(61,90,241,0.40);
            box-shadow: var(--neu-inset), 0 0 0 3px rgba(61,90,241,0.10);
            background: var(--white);
        }

        .form-input::placeholder { color: var(--text-muted); }

        /* Remember / Forgot */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 16px 0 24px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #5A6787;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--indigo);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: var(--indigo);
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .forgot-link:hover { opacity: 0.75; text-decoration: underline; }

        /* Login button */
        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--indigo), #5B73F5);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 16px rgba(61,90,241,0.30);
        }

        .btn-login:hover {
            box-shadow: 0 6px 22px rgba(61,90,241,0.45);
            transform: translateY(-1px);
        }

        .btn-login:active { transform: translateY(0); }

        /* Divider */
        .divider {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 22px 0 18px;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 38%;
            height: 1px;
            background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        /* Demo box */
        .demo-box {
            background: var(--bg);
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: var(--neu-inset);
        }

        .demo-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--indigo);
            text-transform: uppercase;
            letter-spacing: 0.10em;
            margin-bottom: 12px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .demo-row:last-child { margin-bottom: 0; }

        .demo-label { font-size: 12.5px; color: #5A6787; font-weight: 500; }

        .demo-val {
            font-size: 12px;
            font-family: 'DM Mono', monospace;
            background: rgba(61,90,241,0.10);
            color: var(--indigo);
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        /* Footer */
        .login-footer {
            margin-top: 22px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-card { padding: 28px 22px; }
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

        <div class="divider">Demo Credentials</div>

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

    <div class="login-footer">⚡ FlashCru Emergency Response System v2.0</div>

</div>

</body>
</html>