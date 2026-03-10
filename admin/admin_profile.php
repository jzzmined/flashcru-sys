<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid  = (int)$_SESSION['user_id'];
$msg  = $err = '';

// Load current admin data
$admin = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
if (!$admin) { header("Location: ../logout.php"); exit; }

// ── Upload profile picture ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!empty($_FILES['profile_picture']['tmp_name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime    = mime_content_type($_FILES['profile_picture']['tmp_name']);
        $size    = $_FILES['profile_picture']['size'];

        if (!in_array($mime, $allowed)) {
            $err = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        } elseif ($size > 5 * 1024 * 1024) {
            $err = 'Image must be under 5MB.';
        } else {
            $upload_dir = '../assets/uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Remove old photo
            if (!empty($admin['profile_picture']) && file_exists('../' . $admin['profile_picture'])) {
                unlink('../' . $admin['profile_picture']);
            }

            $ext      = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'admin_' . $uid . '_' . uniqid() . '.' . $ext;
            $filepath = 'assets/uploads/profiles/' . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $filename)) {
                $stmt = $conn->prepare("UPDATE users SET profile_picture=? WHERE user_id=?");
                $stmt->bind_param("si", $filepath, $uid);
                $stmt->execute();
                logActivity($uid, "Updated profile picture");
                $msg = 'Profile picture updated successfully.';
                $admin = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
            } else {
                $err = 'Failed to upload image. Check folder permissions.';
            }
        }
    } else {
        $err = 'No image selected.';
    }
}

// ── Remove profile picture ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_photo'])) {
    if (!empty($admin['profile_picture']) && file_exists('../' . $admin['profile_picture'])) {
        unlink('../' . $admin['profile_picture']);
    }
    $conn->query("UPDATE users SET profile_picture=NULL WHERE user_id=$uid");
    logActivity($uid, "Removed profile picture");
    $msg   = 'Profile picture removed.';
    $admin = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
}

// ── Update profile info ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']      ?? '');
    $email     = sanitize($_POST['email']          ?? '');
    $phone     = sanitize($_POST['contact_number'] ?? '');

    if (!$full_name || !$email) {
        $err = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid email address.';
    } else {
        $chk = $conn->query("SELECT user_id FROM users WHERE email='$email' AND user_id != $uid");
        if ($chk && $chk->num_rows > 0) {
            $err = 'That email is already used by another account.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, contact_number=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $uid);
            if ($stmt->execute()) {
                $_SESSION['name'] = $full_name;
                logActivity($uid, "Updated admin profile");
                $msg = 'Profile updated successfully.';
                $admin = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
            } else {
                $err = 'Failed to update profile.';
            }
        }
    }
}

// ── Change password ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new_pw || !$confirm) {
        $err = 'All password fields are required.';
    } elseif (!password_verify($current, $admin['password'])) {
        $err = 'Current password is incorrect.';
    } elseif ($new_pw !== $confirm) {
        $err = 'New passwords do not match.';
    } elseif (strlen($new_pw) < 6) {
        $err = 'New password must be at least 6 characters.';
    } else {
        $hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $hash, $uid);
        if ($stmt->execute()) {
            logActivity($uid, "Changed admin password");
            $msg = 'Password changed successfully.';
        } else {
            $err = 'Failed to change password.';
        }
    }
}

// ── Recent activity ─────────────────────────────────────────────────────────
$recent_logs = $conn->query("
    SELECT * FROM activity_log WHERE user_id=$uid ORDER BY created_at DESC LIMIT 8
");
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">My Profile</div>
                    <div class="fc-breadcrumb">Account / Admin Profile</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <a href="dashboard.php" class="fc-bell-btn" title="Notifications" style="text-decoration:none;">
                    <i class="bi bi-bell-fill"></i>
                </a>
            </div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
            <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
            <div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= $err ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- LEFT: Profile card + photo upload + recent activity -->
                <div class="col-lg-4">

                    <!-- Profile summary card -->
                    <div class="fc-card" style="margin-bottom:20px;overflow:visible;">
                        <div style="background:linear-gradient(135deg,#0f172a,#1e293b);padding:32px 24px 24px;text-align:center;border-radius:var(--fc-radius) var(--fc-radius) 0 0;position:relative;">

                            <!-- Avatar with click-to-change overlay -->
                            <div style="position:relative;display:inline-block;margin-bottom:14px;" id="avatarWrap">
                                <?php if (!empty($admin['profile_picture'])): ?>
                                <img src="../<?= htmlspecialchars($admin['profile_picture']) ?>"
                                     alt="Profile"
                                     id="avatarPreview"
                                     style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--fc-primary);box-shadow:0 8px 28px var(--fc-primary-glow);">
                                <?php else: ?>
                                <div id="avatarInitial"
                                     style="width:90px;height:90px;border-radius:50%;background:var(--fc-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:800;font-family:'Lexend',sans-serif;box-shadow:0 8px 28px var(--fc-primary-glow);">
                                    <?= strtoupper(substr($admin['full_name'],0,1)) ?>
                                </div>
                                <?php endif; ?>

                                <!-- Camera overlay button -->
                                <button type="button" onclick="document.getElementById('photoInput').click()"
                                        title="Change photo"
                                        style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--fc-primary);border:2px solid #1e293b;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;padding:0;">
                                    <i class="bi bi-camera-fill"></i>
                                </button>
                            </div>

                            <div style="font-family:'Lexend',sans-serif;font-weight:700;font-size:17px;color:#fff;margin-bottom:4px;">
                                <?= htmlspecialchars($admin['full_name']) ?>
                            </div>
                            <span style="background:rgba(230,30,30,.2);color:#ff6b6b;padding:3px 14px;border-radius:100px;font-size:11px;font-weight:700;font-family:'Lexend',sans-serif;text-transform:uppercase;">
                                Administrator
                            </span>
                        </div>

                        <!-- Photo upload form (hidden, triggered by camera button) -->
                        <form method="POST" enctype="multipart/form-data" id="photoForm" style="display:none;">
                            <input type="file" name="profile_picture" id="photoInput"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <button type="submit" name="upload_photo" id="photoSubmit"></button>
                        </form>

                        <div style="padding:18px 22px;">
                            <div class="ir-detail-row"><span><i class="bi bi-envelope-fill me-1"></i> Email</span><strong style="word-break:break-all;"><?= htmlspecialchars($admin['email']) ?></strong></div>
                            <div class="ir-detail-row"><span><i class="bi bi-phone-fill me-1"></i> Phone</span><strong><?= htmlspecialchars($admin['contact_number'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row"><span><i class="bi bi-calendar-fill me-1"></i> Joined</span><strong><?= date('M d, Y', strtotime($admin['created_at'])) ?></strong></div>
                            <div class="ir-detail-row" style="border:none;padding-bottom:0;"><span><i class="bi bi-shield-fill-check me-1"></i> Status</span>
                                <span style="background:var(--fc-success-lt);color:var(--fc-success);padding:3px 12px;border-radius:100px;font-size:11px;font-weight:700;">Active</span>
                            </div>
                        </div>

                        <!-- Photo action buttons -->
                        <div style="padding:0 22px 18px;display:flex;gap:8px;">
                            <button type="button" onclick="document.getElementById('photoInput').click()"
                                    class="fc-btn fc-btn-primary" style="flex:1;justify-content:center;font-size:12px;padding:8px;">
                                <i class="bi bi-camera-fill"></i> Change Photo
                            </button>
                            <?php if (!empty($admin['profile_picture'])): ?>
                            <form method="POST" style="flex:1;" onsubmit="return confirm('Remove your profile picture?')">
                                <button type="submit" name="remove_photo"
                                        class="fc-btn" style="width:100%;justify-content:center;font-size:12px;padding:8px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-danger);">
                                    <i class="bi bi-trash-fill"></i> Remove
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent activity -->
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title"><i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> My Recent Actions</div>
                        </div>
                        <div style="max-height:300px;overflow-y:auto;">
                            <?php if ($recent_logs->num_rows === 0): ?>
                            <div class="fc-empty" style="padding:32px;"><i class="bi bi-journal"></i><h6>No activity yet</h6></div>
                            <?php else: ?>
                            <?php while ($l = $recent_logs->fetch_assoc()): ?>
                            <div class="fc-log-item">
                                <div class="fc-log-dot"></div>
                                <div>
                                    <div class="fc-log-action"><?= htmlspecialchars($l['action']) ?></div>
                                    <div class="fc-log-meta"><?= date('M d, Y · g:i A', strtotime($l['created_at'])) ?></div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Edit forms -->
                <div class="col-lg-8">

                    <!-- Edit profile form -->
                    <div class="fc-form-section" style="margin-bottom:20px;">
                        <div class="fc-form-section-title">
                            <i class="bi bi-person-fill"></i> Edit Profile Information
                        </div>
                        <form method="POST" novalidate id="profileForm">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="fc-form-label">Full Name <span style="color:var(--fc-primary)">*</span></label>
                                    <input type="text" name="full_name" class="fc-form-control"
                                           value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">Email Address <span style="color:var(--fc-primary)">*</span></label>
                                    <input type="email" name="email" class="fc-form-control"
                                           value="<?= htmlspecialchars($admin['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">Phone Number</label>
                                    <input type="text" name="contact_number" class="fc-form-control"
                                           value="<?= htmlspecialchars($admin['contact_number'] ?? '') ?>"
                                           placeholder="09XXXXXXXXX">
                                </div>
                                <div class="col-12" style="padding-top:6px;">
                                    <button type="submit" name="update_profile" class="fc-btn fc-btn-primary">
                                        <i class="bi bi-save-fill"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Change password form -->
                    <div class="fc-form-section">
                        <div class="fc-form-section-title">
                            <i class="bi bi-key-fill"></i> Change Password
                        </div>
                        <form method="POST" novalidate id="pwForm">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="fc-form-label">Current Password <span style="color:var(--fc-primary)">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" name="current_password" id="pw_current" class="fc-form-control"
                                               placeholder="Enter current password" style="padding-right:42px;" required>
                                        <button type="button" onclick="togglePw('pw_current','eye1')"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                                            <i class="bi bi-eye" id="eye1"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">New Password <span style="color:var(--fc-primary)">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" name="new_password" id="pw_new" class="fc-form-control"
                                               placeholder="Min. 6 characters" style="padding-right:42px;" required>
                                        <button type="button" onclick="togglePw('pw_new','eye2')"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                                            <i class="bi bi-eye" id="eye2"></i>
                                        </button>
                                    </div>
                                    <div style="margin-top:6px;height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden;">
                                        <div id="strengthBar" style="height:100%;width:0;border-radius:4px;transition:all .3s;"></div>
                                    </div>
                                    <div id="strengthLabel" style="font-size:11px;color:#94a3b8;margin-top:3px;"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">Confirm New Password <span style="color:var(--fc-primary)">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" name="confirm_password" id="pw_confirm" class="fc-form-control"
                                               placeholder="Repeat new password" style="padding-right:42px;" required>
                                        <button type="button" onclick="togglePw('pw_confirm','eye3')"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                                            <i class="bi bi-eye" id="eye3"></i>
                                        </button>
                                    </div>
                                    <div id="matchLabel" style="font-size:11px;margin-top:3px;"></div>
                                </div>
                                <div class="col-12" style="padding-top:6px;">
                                    <button type="submit" name="change_password" class="fc-btn fc-btn-dark">
                                        <i class="bi bi-key-fill"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Photo preview before upload ─────────────────────────────────────────────
document.getElementById('photoInput').addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('Image must be under 5MB.');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
        // Replace avatar with preview image
        const wrap = document.getElementById('avatarWrap');
        const existing = document.getElementById('avatarPreview') || document.getElementById('avatarInitial');
        const img = document.createElement('img');
        img.id = 'avatarPreview';
        img.src = e.target.result;
        img.style.cssText = 'width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--fc-primary);box-shadow:0 8px 28px var(--fc-primary-glow);';
        if (existing) existing.replaceWith(img);
    };
    reader.readAsDataURL(file);
    // Auto-submit the hidden photo form
    document.getElementById('photoSubmit').click();
});

// ── Password toggle ─────────────────────────────────────────────────────────
function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// ── Password strength ───────────────────────────────────────────────────────
document.getElementById('pw_new').addEventListener('input', function () {
    const v = this.value;
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v))      score++;
    if (/[0-9]/.test(v))      score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const levels = [
        {pct:'0%',  color:'#e2e8f0', text:''},
        {pct:'25%', color:'#ef4444', text:'Weak'},
        {pct:'50%', color:'#f59e0b', text:'Fair'},
        {pct:'75%', color:'#3b82f6', text:'Good'},
        {pct:'100%',color:'#10b981', text:'Strong'},
    ];
    const l = levels[Math.min(score, 4)];
    bar.style.width      = v.length ? l.pct : '0%';
    bar.style.background = l.color;
    lbl.textContent      = v.length ? l.text : '';
    lbl.style.color      = l.color;
});

// ── Password match ──────────────────────────────────────────────────────────
document.getElementById('pw_confirm').addEventListener('input', function () {
    const pw  = document.getElementById('pw_new').value;
    const lbl = document.getElementById('matchLabel');
    if (!this.value) { lbl.textContent = ''; return; }
    if (this.value === pw) { lbl.textContent = '✓ Passwords match'; lbl.style.color = '#10b981'; }
    else { lbl.textContent = '✗ Does not match'; lbl.style.color = '#ef4444'; }
});
</script>

<?php include '../includes/footer.php'; ?>