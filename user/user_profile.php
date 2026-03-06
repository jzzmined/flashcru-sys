<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid  = (int)$_SESSION['user_id'];
$msg  = $err = '';

// Load user data
$user = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
if (!$user) { header("Location: ../logout.php"); exit; }

// ── Update profile info ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']      ?? '');
    $email     = sanitize($_POST['email']          ?? '');
    $phone     = sanitize($_POST['contact_number'] ?? '');

    if (!$full_name || !$email) {
        $err = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        // Check email not used by another user
        $chk = $conn->query("SELECT user_id FROM users WHERE email='$email' AND user_id != $uid");
        if ($chk && $chk->num_rows > 0) {
            $err = 'That email is already used by another account.';
        } else {
            // Handle profile picture upload
            $pic_sql = '';
            if (!empty($_FILES['profile_picture']['name'])) {
                $file    = $_FILES['profile_picture'];
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                $mime    = mime_content_type($file['tmp_name']);

                if (!in_array($mime, $allowed)) {
                    $err = 'Profile picture must be JPG, PNG, WEBP, or GIF.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $err = 'Profile picture must be under 5MB.';
                } else {
                    $upload_dir = '../assets/uploads/profiles/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    // Delete old picture if exists
                    if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
                        unlink('../' . $user['profile_picture']);
                    }

                    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'user_' . $uid . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                        $filepath = 'assets/uploads/profiles/' . $filename;
                        $pic_sql  = ", profile_picture='$filepath'";
                    } else {
                        $err = 'Failed to upload profile picture.';
                    }
                }
            }

            if (!$err) {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, contact_number=? $pic_sql WHERE user_id=?");
                $stmt->bind_param("sssi", $full_name, $email, $phone, $uid);
                if ($stmt->execute()) {
                    $_SESSION['name'] = $full_name;
                    logActivity($uid, "Updated profile");
                    $msg  = 'Profile updated successfully.';
                    $user = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
                } else {
                    $err = 'Failed to update profile.';
                }
            }
        }
    }
}

// ── Remove profile picture ───────────────────────────────
if (isset($_GET['remove_pic'])) {
    if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
        unlink('../' . $user['profile_picture']);
    }
    $conn->query("UPDATE users SET profile_picture=NULL WHERE user_id=$uid");
    logActivity($uid, "Removed profile picture");
    header("Location: user_profile.php?msg=pic_removed"); exit;
}
if (isset($_GET['msg']) && $_GET['msg'] === 'pic_removed') {
    $msg  = 'Profile picture removed.';
    $user = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
}

// ── Change password ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new_pw || !$confirm) {
        $err = 'All password fields are required.';
    } elseif (!password_verify($current, $user['password'])) {
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
            logActivity($uid, "Changed password");
            $msg = 'Password changed successfully.';
        } else {
            $err = 'Failed to change password.';
        }
    }
}

// Recent activity
$logs = $conn->query("SELECT * FROM activity_log WHERE user_id=$uid ORDER BY created_at DESC LIMIT 6");

// User stats
$total    = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid")->fetch_assoc()['c'];
$resolved = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=4")->fetch_assoc()['c'];
$pending  = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=1")->fetch_assoc()['c'];

// Profile picture path helper
$pic_url = !empty($user['profile_picture'])
    ? '../' . $user['profile_picture']
    : null;
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">My Profile</div>
                    <div class="fc-breadcrumb">Account / Profile Settings</div>
                </div>
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

                <!-- ── LEFT COLUMN ── -->
                <div class="col-lg-4">

                    <!-- Profile card -->
                    <div class="fc-card" style="margin-bottom:20px;">

                        <!-- Cover strip -->
                        <div style="height:90px;background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:var(--fc-radius) var(--fc-radius) 0 0;position:relative;">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(230,30,30,.18),transparent);border-radius:inherit;"></div>
                        </div>

                        <!-- Avatar -->
                        <div style="display:flex;flex-direction:column;align-items:center;padding:0 24px 24px;margin-top:-44px;position:relative;">

                            <!-- Picture circle -->
                            <div id="avatarWrapper" style="position:relative;width:88px;height:88px;margin-bottom:14px;">
                                <?php if ($pic_url): ?>
                                <img id="avatarPreview" src="<?= htmlspecialchars($pic_url) ?>" alt="Profile"
                                     style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:var(--fc-shadow-md);">
                                <?php else: ?>
                                <div id="avatarInitial" style="width:88px;height:88px;border-radius:50%;background:var(--fc-primary);border:4px solid #fff;box-shadow:var(--fc-shadow-md);display:flex;align-items:center;justify-content:center;font-family:'Lexend',sans-serif;font-weight:800;font-size:32px;color:#fff;">
                                    <?= strtoupper(substr($user['full_name'],0,1)) ?>
                                </div>
                                <img id="avatarPreview" src="" alt=""
                                     style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:var(--fc-shadow-md);display:none;position:absolute;inset:0;">
                                <?php endif; ?>

                                <!-- Camera overlay button -->
                                <label for="picInput" style="position:absolute;bottom:2px;right:2px;width:28px;height:28px;border-radius:50%;background:var(--fc-primary);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);border:2px solid #fff;font-size:13px;" title="Change photo">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                            </div>

                            <div style="font-family:'Lexend',sans-serif;font-weight:700;font-size:17px;color:var(--fc-dark);text-align:center;margin-bottom:4px;">
                                <?= htmlspecialchars($user['full_name']) ?>
                            </div>
                            <span style="background:var(--fc-info-lt);color:var(--fc-info);padding:3px 14px;border-radius:100px;font-size:11px;font-weight:700;font-family:'Lexend',sans-serif;">
                                Community User
                            </span>

                            <!-- Remove picture link -->
                            <?php if (!empty($user['profile_picture'])): ?>
                            <a href="?remove_pic=1"
                               onclick="return confirm('Remove your profile picture?')"
                               style="font-size:11.5px;color:var(--fc-danger);margin-top:10px;font-weight:500;">
                                <i class="bi bi-trash"></i> Remove Photo
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Info rows -->
                        <div style="padding:0 22px 20px;">
                            <div class="ir-detail-row"><span><i class="bi bi-envelope-fill me-1"></i>Email</span><strong style="word-break:break-all;font-size:12.5px;"><?= htmlspecialchars($user['email'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row"><span><i class="bi bi-phone-fill me-1"></i>Phone</span><strong><?= htmlspecialchars($user['contact_number'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row" style="border:none;"><span><i class="bi bi-calendar-fill me-1"></i>Joined</span><strong><?= date('M d, Y', strtotime($user['created_at'])) ?></strong></div>
                        </div>
                    </div>

                    <!-- Stats card -->
                    <div class="fc-card" style="margin-bottom:20px;">
                        <div class="fc-card-header">
                            <div class="fc-card-title"><i class="bi bi-bar-chart-fill" style="color:var(--fc-primary)"></i> My Stats</div>
                        </div>
                        <div style="padding:18px 22px;display:flex;justify-content:space-around;text-align:center;">
                            <div>
                                <div style="font-family:'Lexend',sans-serif;font-weight:800;font-size:26px;color:var(--fc-dark);"><?= $total ?></div>
                                <div style="font-size:11.5px;color:var(--fc-muted);">Total</div>
                            </div>
                            <div style="width:1px;background:var(--fc-border);"></div>
                            <div>
                                <div style="font-family:'Lexend',sans-serif;font-weight:800;font-size:26px;color:var(--fc-warning);"><?= $pending ?></div>
                                <div style="font-size:11.5px;color:var(--fc-muted);">Pending</div>
                            </div>
                            <div style="width:1px;background:var(--fc-border);"></div>
                            <div>
                                <div style="font-family:'Lexend',sans-serif;font-weight:800;font-size:26px;color:var(--fc-success);"><?= $resolved ?></div>
                                <div style="font-size:11.5px;color:var(--fc-muted);">Resolved</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent activity -->
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title"><i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> Recent Activity</div>
                        </div>
                        <div style="max-height:240px;overflow-y:auto;">
                            <?php if ($logs->num_rows === 0): ?>
                            <div class="fc-empty" style="padding:28px;"><i class="bi bi-journal"></i><h6>No activity yet</h6></div>
                            <?php else: ?>
                            <?php while ($l = $logs->fetch_assoc()): ?>
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

                <!-- ── RIGHT COLUMN ── -->
                <div class="col-lg-8">

                    <!-- Edit profile form -->
                    <div class="fc-form-section" style="margin-bottom:20px;">
                        <div class="fc-form-section-title">
                            <i class="bi bi-person-fill"></i> Edit Profile
                        </div>

                        <form method="POST" enctype="multipart/form-data" novalidate id="profileForm">

                            <!-- Hidden file input triggered by camera button -->
                            <input type="file" id="picInput" name="profile_picture"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   style="display:none;" onchange="previewPic(this)">

                            <!-- Picture upload area (click-to-change shown below form) -->
                            <div id="picChangeArea" style="display:none;margin-bottom:18px;padding:14px 16px;background:#fafbff;border:1.5px dashed var(--fc-border);border-radius:var(--fc-radius-sm);align-items:center;gap:14px;flex-wrap:wrap;">
                                <img id="picPreviewLarge" src="" alt="Preview"
                                     style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--fc-border);display:none;">
                                <div id="picPreviewInitial" style="width:64px;height:64px;border-radius:50%;background:var(--fc-primary-lt);color:var(--fc-primary);display:flex;align-items:center;justify-content:center;font-size:22px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:var(--fc-dark);" id="picFileName">No file selected</div>
                                    <div style="font-size:11.5px;color:var(--fc-muted);">JPG, PNG, WEBP · Max 5MB</div>
                                </div>
                                <button type="button" onclick="clearPic()" style="margin-left:auto;background:none;border:none;color:var(--fc-danger);font-size:13px;cursor:pointer;font-weight:600;">
                                    <i class="bi bi-x-circle"></i> Remove
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="fc-form-label">Full Name <span style="color:var(--fc-primary)">*</span></label>
                                    <input type="text" name="full_name" id="f_name" class="fc-form-control"
                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                    <div class="fc-field-err" id="err_name"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">Email Address <span style="color:var(--fc-primary)">*</span></label>
                                    <input type="email" name="email" id="f_email" class="fc-form-control"
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                    <div class="fc-field-err" id="err_email"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">Phone Number</label>
                                    <input type="text" name="contact_number" class="fc-form-control"
                                           value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
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
                                        <input type="password" name="current_password" id="pw_current"
                                               class="fc-form-control" placeholder="Enter current password"
                                               style="padding-right:42px;" required>
                                        <button type="button" onclick="togglePw('pw_current','eye1')"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                                            <i class="bi bi-eye" id="eye1"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fc-form-label">New Password <span style="color:var(--fc-primary)">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" name="new_password" id="pw_new"
                                               class="fc-form-control" placeholder="Min. 6 characters"
                                               style="padding-right:42px;" required>
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
                                        <input type="password" name="confirm_password" id="pw_confirm"
                                               class="fc-form-control" placeholder="Repeat new password"
                                               style="padding-right:42px;" required>
                                        <button type="button" onclick="togglePw('pw_confirm','eye3')"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;">
                                            <i class="bi bi-eye" id="eye3"></i>
                                        </button>
                                    </div>
                                    <div id="matchLabel" style="font-size:11px;margin-top:3px;"></div>
                                </div>
                                <div class="col-12" style="padding-top:4px;">
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

<style>
.fc-field-err { font-size:11.5px; color:var(--fc-danger); margin-top:4px; min-height:16px; }
.fc-form-control.is-invalid { border-color:var(--fc-danger); box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.fc-form-control.is-valid   { border-color:var(--fc-success); }
</style>

<script>
// ── Profile picture preview ──────────────────────────────
function previewPic(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Validate size
    if (file.size > 5 * 1024 * 1024) {
        alert('File is too large. Maximum size is 5MB.');
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        // Update small avatar in card
        const preview = document.getElementById('avatarPreview');
        const initial = document.getElementById('avatarInitial');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (initial) initial.style.display = 'none';

        // Show large preview area in form
        const area = document.getElementById('picChangeArea');
        area.style.display = 'flex';
        const large = document.getElementById('picPreviewLarge');
        large.src = e.target.result;
        large.style.display = 'block';
        document.getElementById('picPreviewInitial').style.display = 'none';
        document.getElementById('picFileName').textContent = file.name;
    };
    reader.readAsDataURL(file);
}

function clearPic() {
    document.getElementById('picInput').value = '';
    document.getElementById('picChangeArea').style.display = 'none';
    const preview = document.getElementById('avatarPreview');
    const initial = document.getElementById('avatarInitial');
    <?php if ($pic_url): ?>
    preview.src = '<?= htmlspecialchars($pic_url) ?>';
    preview.style.display = 'block';
    <?php else: ?>
    preview.style.display = 'none';
    if (initial) initial.style.display = 'flex';
    <?php endif; ?>
}

// ── Show pic change area when file selected ──────────────
document.getElementById('picInput').addEventListener('change', function() {
    if (this.files && this.files[0]) previewPic(this);
});

// ── Show change area trigger on avatar click ─────────────
document.getElementById('avatarWrapper').style.cursor = 'pointer';

// ── Password toggle ──────────────────────────────────────
function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// ── Password strength ────────────────────────────────────
document.getElementById('pw_new').addEventListener('input', function() {
    const v = this.value;
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
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

// ── Password match indicator ─────────────────────────────
document.getElementById('pw_confirm').addEventListener('input', function() {
    const pw  = document.getElementById('pw_new').value;
    const lbl = document.getElementById('matchLabel');
    if (!this.value) { lbl.textContent = ''; return; }
    if (this.value === pw) { lbl.textContent = '✓ Passwords match'; lbl.style.color = '#10b981'; }
    else { lbl.textContent = '✗ Does not match'; lbl.style.color = '#ef4444'; }
});

// ── Profile form validation ──────────────────────────────
document.getElementById('profileForm').addEventListener('submit', function(e) {
    let valid = true;
    function setErr(fieldId, errId, msg) {
        const el = document.getElementById(fieldId);
        const errEl = document.getElementById(errId);
        if (msg) { el.classList.add('is-invalid'); errEl.textContent = msg; valid = false; }
        else { el.classList.remove('is-invalid'); errEl.textContent = ''; }
    }
    setErr('f_name',  'err_name',  !document.getElementById('f_name').value.trim() ? 'Full name is required.' : '');
    const email = document.getElementById('f_email').value.trim();
    setErr('f_email', 'err_email', !email ? 'Email is required.' : (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? 'Enter a valid email.' : ''));
    if (!valid) e.preventDefault();
});
</script>

<?php include '../includes/footer.php'; ?>