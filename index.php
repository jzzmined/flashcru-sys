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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
          --red-50:   #FFF1F2;
          --red-100:  #FFE4E6;
          --red-200:  #FECDD3;
          --red-400:  #E07B82;
          --red-600:  #A63244;
          --red-700:  #7D1D2F;
          --blue-500: #3B82F6;
          --blue-600: #2563EB;
          --cream:    #FEF8F8;
          --white:    #FFFFFF;
          --gray-100: #F3F4F6;
          --gray-200: #E5E7EB;
          --gray-400: #9CA3AF;
          --gray-500: #6B7280;
          --gray-600: #4B5563;
          --navy:     #0F172A;
        }

        html { -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'Sora', -apple-system, sans-serif;
            font-size: 14px;
            background: var(--cream);
            background-image:
              radial-gradient(ellipse at 10% 50%, rgba(124,29,52,0.08) 0%, transparent 55%),
              radial-gradient(ellipse at 90% 10%, rgba(37,99,235,0.07) 0%, transparent 55%),
              radial-gradient(ellipse at 70% 90%, rgba(124,29,52,0.05) 0%, transparent 45%);
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
            background: linear-gradient(135deg, #A63244, #7D1D2F);
            margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(124,29,52,0.35);
        }
        .brand-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .brand-logo .logo-fallback { font-size: 28px; }

        .brand-name {
            font-size: 28px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.5px;
        }
        .brand-sub {
            font-size: 13px;
            color: var(--gray-400);
            margin-top: 3px;
        }

        /* Card */
        .login-card {
            width: 100%;
            background: var(--white);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--gray-100);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #A63244, #3B82F6);
            border-radius: 24px 24px 0 0;
            margin: -36px -36px 30px -36px;
        }

        .card-title { font-size: 19px; font-weight: 800; color: var(--navy); margin-bottom: 4px; }
        .card-sub   { font-size: 13px; color: var(--gray-400); margin-bottom: 26px; }

        /* Error */
        .error-alert {
            background: var(--red-50);
            border: 1px solid var(--red-200);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--red-700);
        }

        /* Form */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 6px;
        }
        .form-input {
            display: block;
            width: 100%;
            padding: 11px 14px;
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            color: var(--navy);
            background: var(--gray-100);
            border: 1.5px solid transparent;
            border-radius: 10px;
            transition: all 0.20s;
            outline: none;
        }
        .form-input:focus {
            border-color: rgba(166,50,68,0.40);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(166,50,68,0.10);
        }
        .form-input::placeholder { color: var(--gray-400); }

        /* Extras */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 22px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-600);
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--red-600);
            cursor: pointer;
        }
        .forgot-link {
            font-size: 13px;
            color: var(--red-600);
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
            background: linear-gradient(135deg, #A63244, #C25460);
            color: white;
            border: none;
            border-radius: 11px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s;
            box-shadow: 0 4px 16px rgba(124,29,52,0.32);
        }
        .btn-login:hover {
            box-shadow: 0 6px 24px rgba(124,29,52,0.45);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); }

        /* Divider */
        .divider {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-400);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 22px 0 16px;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 38%;
            height: 1px;
            background: var(--gray-200);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        /* Demo box */
        .demo-box {
            background: var(--gray-100);
            border-radius: 12px;
            padding: 16px 18px;
            border: 1px solid var(--gray-200);
        }
        .demo-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--blue-600);
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
        .demo-label { font-size: 12.5px; color: var(--gray-500); font-weight: 500; }
        .demo-val {
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            background: var(--red-100);
            color: var(--red-700);
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Footer */
        .login-footer {
            margin-top: 22px;
            font-size: 12px;
            color: var(--gray-400);
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
            <img src="fc-logo.jpg" alt="FlashCru Logo"
                 onerror="this.style.display='none';this.parentElement.innerHTML='<span class=&quot;logo-fallback&quot;>⚡</span>'">
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

    <div class="login-footer">⚡ FlashCru Emergency Response System v3.0</div>

</div>

</body>
</html>