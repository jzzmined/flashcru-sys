<?php
/**
 * FlashCru Emergency Response System
 * Settings / User Management — Red/White/Blue Theme v3.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Settings';
$db = new Database();

// Handle Delete User
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user_id']) {
        $db->delete('users', 'user_id = :id', ['id' => $id]);
        header('Location: settings.php?msg=deleted');
        exit();
    }
}

// Handle Save User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $data = [
        'username'  => sanitize($_POST['username']),
        'full_name' => sanitize($_POST['full_name']),
        'email'     => sanitize($_POST['email']),
        'role'      => sanitize($_POST['role']),
        'status'    => sanitize($_POST['status']),
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    if (!empty($_POST['user_id'])) {
        $db->update('users', $data, 'user_id = :id', ['id' => (int)$_POST['user_id']]);
        header('Location: settings.php?msg=updated');
    } else {
        if (empty($_POST['password'])) {
            header('Location: settings.php?error=Password+is+required+for+new+users');
            exit();
        }
        $db->insert('users', $data);
        header('Location: settings.php?msg=created');
    }
    exit();
}

// Fetch users
$users = $db->fetchAll("SELECT * FROM users ORDER BY FIELD(role,'admin','dispatcher','responder'), full_name");

// Edit user
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [(int)$_GET['edit']]);
}

$current_user = getCurrentUser();

// Role display helpers
function roleBadgeColor(string $role): string {
    return match ($role) {
        'admin'      => 'var(--red-600)',
        'dispatcher' => 'var(--blue-500)',
        'responder'  => 'var(--green)',
        default      => 'var(--gray-400)',
    };
}

function roleLabel(string $role): string {
    return match ($role) {
        'admin'      => 'Administrator',
        'dispatcher' => 'Dispatcher',
        'responder'  => 'Responder',
        default      => ucfirst($role),
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <!-- Page Header -->
            <div class="flex-between mb-20">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--navy);">⚙️ Settings</h2>
                    <p class="text-muted" style="font-size:13px;margin-top:3px;">Manage users and system configuration</p>
                </div>
                <?php if (isAdmin()): ?>
                <button class="btn btn-primary" onclick="openModal('userModal')">+ New User</button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">✅ User <?php echo htmlspecialchars($_GET['msg']); ?> successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- Main Layout -->
            <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

                <!-- Users Table -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">👤 User Accounts</h3>
                        <span style="font-size:12px;color:var(--gray-400);"><?php echo count($users); ?> total users</span>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <?php
                                            // Avatar gradient by role
                                            $grad = match($user['role']) {
                                                'admin'      => 'linear-gradient(135deg,var(--red-600),var(--red-400))',
                                                'dispatcher' => 'linear-gradient(135deg,var(--blue-600),var(--blue-400))',
                                                default      => 'linear-gradient(135deg,var(--green),#34D399)',
                                            };
                                            $parts = explode(' ', $user['full_name']);
                                            $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                                            ?>
                                            <div style="width:34px;height:34px;border-radius:50%;background:<?php echo $grad; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;flex-shrink:0;font-family:'JetBrains Mono',monospace;">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div style="font-size:11px;color:var(--gray-400);"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-500);">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td>
                                        <span style="color:<?php echo roleBadgeColor($user['role']); ?>;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">
                                            <?php echo roleLabel($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'available' : 'offline'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:12px;white-space:nowrap;">
                                        <?php echo $user['last_login'] ? date('M j, g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                    <td>
                                        <div class="flex gap-8">
                                            <button class="btn btn-secondary btn-sm"
                                                onclick='editUser(<?php echo json_encode($user); ?>)'>✏️</button>
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="settings.php?delete=<?php echo $user['user_id']; ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this user?')">🗑️</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div><!-- /users table -->

                <!-- Right Sidebar Panels -->
                <div style="display:flex;flex-direction:column;gap:16px;">

                    <!-- My Profile -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">👤 My Profile</h3>
                        </div>
                        <div class="panel-body">
                            <!-- Avatar -->
                            <div style="text-align:center;margin-bottom:18px;">
                                <?php
                                $parts = explode(' ', $_SESSION['full_name'] ?? 'System Admin');
                                $myInitials = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));
                                ?>
                                <div style="
                                    width:64px;height:64px;border-radius:50%;
                                    background:linear-gradient(135deg,var(--red-600),var(--red-400));
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:20px;font-weight:800;color:#fff;
                                    margin:0 auto 12px;
                                    box-shadow:var(--shadow-red);
                                    font-family:'JetBrains Mono',monospace;">
                                    <?php echo $myInitials; ?>
                                </div>
                                <div style="font-weight:700;font-size:16px;color:var(--navy);">
                                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?>
                                </div>
                                <div style="font-size:12px;color:var(--gray-400);margin-top:3px;">
                                    <?php echo roleLabel($_SESSION['role'] ?? 'admin'); ?>
                                </div>
                            </div>

                            <!-- Info rows -->
                            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                                <div style="display:flex;justify-content:space-between;">
                                    <span class="text-muted">Username</span>
                                    <span style="font-weight:600;font-family:'JetBrains Mono',monospace;">
                                        <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                                    </span>
                                </div>
                                <div style="display:flex;justify-content:space-between;">
                                    <span class="text-muted">Email</span>
                                    <span><?php echo htmlspecialchars($current_user['email'] ?? 'N/A'); ?></span>
                                </div>
                                <div style="display:flex;justify-content:space-between;">
                                    <span class="text-muted">Role</span>
                                    <span style="font-weight:700;color:var(--red-600);">
                                        <?php echo roleLabel($_SESSION['role'] ?? 'admin'); ?>
                                    </span>
                                </div>
                            </div>

                            <button class="btn btn-secondary w-full mt-16"
                                    onclick='editUser(<?php echo json_encode($current_user); ?>)'>
                                ✏️ Edit My Profile
                            </button>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">⚡ System Info</h3>
                        </div>
                        <div class="panel-body">
                            <?php
                            $sys_info = [
                                'System'   => SITE_NAME     ?? 'FlashCru',
                                'Version'  => SITE_VERSION  ?? '3.0',
                                'PHP'      => phpversion(),
                                'Database' => DB_NAME       ?? '—',
                                'Timezone' => date_default_timezone_get(),
                                'Date'     => date('M j, Y'),
                            ];
                            ?>
                            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                                <?php foreach ($sys_info as $key => $val): ?>
                                <div style="display:flex;justify-content:space-between;padding-bottom:8px;border-bottom:1px solid var(--gray-100);">
                                    <span class="text-muted"><?php echo $key; ?></span>
                                    <span style="font-weight:600;color:var(--navy);font-family:'JetBrains Mono',monospace;">
                                        <?php echo htmlspecialchars((string)$val); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /right col -->

            </div><!-- /grid -->

        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<!-- User Modal -->
<div class="modal-overlay <?php echo $edit_user ? 'active' : ''; ?>" id="userModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="userModalTitle"><?php echo $edit_user ? '✏️ Edit User' : '+ New User'; ?></h3>
            <button class="modal-close" onclick="closeModal('userModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modal_user_id" value="<?php echo $edit_user['user_id'] ?? ''; ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" id="modal_full_name" class="form-control" required
                               value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>"
                               placeholder="First Last">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="modal_username" class="form-control" required
                               value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>"
                               placeholder="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="modal_email" class="form-control"
                           value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>"
                           placeholder="email@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" class="form-control"
                           placeholder="<?php echo $edit_user ? 'Leave blank to keep current' : 'Enter password'; ?>">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" id="modal_role" class="form-control" required>
                            <option value="admin"      <?php echo ($edit_user['role']??'')==='admin'?'selected':''; ?>>Administrator</option>
                            <option value="dispatcher" <?php echo ($edit_user['role']??'dispatcher')==='dispatcher'?'selected':''; ?>>Dispatcher</option>
                            <option value="responder"  <?php echo ($edit_user['role']??'')==='responder'?'selected':''; ?>>Responder</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="modal_status" class="form-control">
                            <option value="active"   <?php echo ($edit_user['status']??'active')==='active'?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_user['status']??'')==='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button>
                <button type="submit" name="save_user" class="btn btn-primary">
                    <?php echo $edit_user ? 'Update User' : 'Create User'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    <?php if ($edit_user): ?>window.location.href = 'settings.php';<?php endif; ?>
}

function editUser(user) {
    document.getElementById('modal_user_id').value    = user.user_id   || '';
    document.getElementById('modal_full_name').value  = user.full_name || '';
    document.getElementById('modal_username').value   = user.username  || '';
    document.getElementById('modal_email').value      = user.email     || '';
    document.getElementById('modal_role').value       = user.role      || 'dispatcher';
    document.getElementById('modal_status').value     = user.status    || 'active';
    document.getElementById('userModalTitle').textContent = '✏️ Edit User';
    openModal('userModal');
}
</script>
</body>
</html>