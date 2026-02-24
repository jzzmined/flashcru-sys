<?php
/**
 * FlashCru Emergency Response System
 * Settings — v4.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Settings';
$db = new Database();

$success = '';
$error   = '';

$user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);

// Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $contact   = sanitize($_POST['contact_number'] ?? '');
    $db->update('users', [
        'full_name'      => $full_name,
        'email'          => $email,
        'contact_number' => $contact,
    ], 'user_id = :id', ['id' => $_SESSION['user_id']]);
    $_SESSION['full_name'] = $full_name;
    $success = 'Profile updated successfully.';
    $user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
}

// Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $db->update('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], 'user_id = :id', ['id' => $_SESSION['user_id']]);
        $success = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="fcMainContent">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <div class="flex-between mb-20">
                <div>
                    <h2 class="page-title">⚙️ Settings</h2>
                    <p class="page-subtitle">Manage your account and system preferences</p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success" data-autodismiss>✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error" data-autodismiss>⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

                <!-- Profile -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">👤 Profile Information</h3>
                    </div>
                    <div class="panel-body">
                        <!-- Avatar Display -->
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px;padding:16px;background:var(--bg);border-radius:var(--radius);">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;font-family:'JetBrains Mono',monospace;box-shadow:var(--shadow-primary);">
                                <?php echo strtoupper(substr($user['username'] ?? 'SA', 0, 2)); ?>
                            </div>
                            <div>
                                <div style="font-family:'Lexend',sans-serif;font-weight:700;color:var(--navy);"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
                                <div style="font-size:12px;color:var(--muted);">@<?php echo htmlspecialchars($user['username'] ?? ''); ?> · <?php echo ucfirst($user['role'] ?? ''); ?></div>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                       value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                                       placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username (read-only)</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                       readonly style="background:var(--surface-2);cursor:not-allowed;">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary w-full">💾 Save Profile</button>
                        </form>
                    </div>
                </div>

                <!-- Password + System Info -->
                <div style="display:flex;flex-direction:column;gap:20px;">

                    <!-- Change Password -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">🔐 Change Password</h3>
                        </div>
                        <div class="panel-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required placeholder="At least 6 characters">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                                </div>
                                <button type="submit" name="change_password" class="btn btn-secondary w-full">🔑 Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">ℹ️ System Information</h3>
                        </div>
                        <div class="panel-body" style="padding:14px 20px;">
                            <?php
                            $info = [
                                ['System',    'FlashCru Emergency Response v4.0'],
                                ['PHP',       PHP_VERSION],
                                ['Server',    php_uname('s') . ' ' . php_uname('r')],
                                ['Timezone',  date_default_timezone_get()],
                                ['Last Login','Just now'],
                            ];
                            foreach ($info as [$k,$v]):
                            ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--bg);">
                                <span style="font-size:12.5px;color:var(--muted);font-weight:500;"><?php echo $k; ?></span>
                                <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--ink);"><?php echo htmlspecialchars($v); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Emergency Contacts -->
                    <div class="panel" style="border-top:3px solid var(--primary);">
                        <div class="panel-header">
                            <h3 class="panel-title" style="color:var(--primary);">📞 Emergency Hotlines</h3>
                        </div>
                        <div class="panel-body" style="padding:14px 20px;">
                            <?php
                            $hotlines = [
                                ['🚒 BFP Davao',   '(082) 221-3233'],
                                ['🏛️ CDRRMO',      '(082) 224-5487'],
                                ['🚔 PNP Davao',   '(082) 241-0017'],
                                ['🚑 Red Cross',   '(082) 221-0006'],
                                ['🆘 911',         '911'],
                            ];
                            foreach ($hotlines as [$label, $num]):
                            ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--bg);">
                                <span style="font-size:13px;color:var(--ink);"><?php echo $label; ?></span>
                                <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $num); ?>"
                                   style="font-family:'JetBrains Mono',monospace;font-size:12.5px;font-weight:700;color:var(--primary);">
                                    <?php echo $num; ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>