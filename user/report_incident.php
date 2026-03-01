<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_id  = (int)($_POST['incident_type_id'] ?? 0);
    $bar_id   = (int)($_POST['barangay_id']      ?? 0);
    $desc     = sanitize($_POST['description']   ?? '');
    $loc      = sanitize($_POST['location_detail']?? '');
    $uid      = (int)$_SESSION['user_id'];

    if (!$type_id || !$bar_id || !$desc) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO incidents (user_id, incident_type_id, barangay_id, street_landmark, description, status_id, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        if (!$stmt) die("Prepare failed: " . $conn->error);
        $stmt->bind_param("iiiss", $uid, $type_id, $bar_id, $loc, $desc);
        if ($stmt->execute()) {
            $inc_id = $conn->insert_id;
            logActivity($uid, "Submitted incident report #$inc_id");
            $success = "Incident reported successfully! Report ID: <strong>#$inc_id</strong>";
        } else {
            $error = 'Failed to submit report. Please try again.';
        }
    }
}

$inc_types = $conn->query("SELECT id, name FROM incident_types ORDER BY name");
$barangays = $conn->query("SELECT id, name FROM barangays ORDER BY name");
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Report Incident</div>
                    <div class="fc-breadcrumb">Dashboard / Report Incident</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <div class="fc-notif-btn"><i class="bi bi-bell"></i></div>
                <div class="fc-tb-user">
                    <div class="fc-user-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
                    <div>
                        <div class="fc-tb-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                        <div class="fc-tb-role">Community Member</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fc-content">
            <div style="max-width:740px;">

                <?php if ($error): ?>
                <div class="fc-alert fc-alert-error"><i class="bi bi-exclamation-circle-fill"></i> <?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="fc-alert fc-alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                    &mdash; <a href="my_reports.php">View my reports</a>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="fc-form-section">
                        <div class="fc-form-section-title">
                            <i class="bi bi-exclamation-triangle-fill"></i> Incident Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fc-form-label">Incident Type <span style="color:var(--fc-primary)">*</span></label>
                                <select name="incident_type_id" class="fc-form-control" required>
                                    <option value="">Select incident type</option>
                                    <?php while ($it = $inc_types->fetch_assoc()): ?>
                                    <option value="<?= $it['id'] ?>"
                                        <?= (isset($_POST['incident_type_id']) && $_POST['incident_type_id'] == $it['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($it['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="fc-form-label">Barangay <span style="color:var(--fc-primary)">*</span></label>
                                <select name="barangay_id" class="fc-form-control" required>
                                    <option value="">Select barangay</option>
                                    <?php while ($b = $barangays->fetch_assoc()): ?>
                                    <option value="<?= $b['id'] ?>"
                                        <?= (isset($_POST['barangay_id']) && $_POST['barangay_id'] == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="fc-form-label">Exact Location / Landmark</label>
                                <input type="text" name="location_detail" class="fc-form-control"
                                       placeholder="e.g. Near public market, corner Rizal St."
                                       value="<?= htmlspecialchars($_POST['location_detail'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label class="fc-form-label">
                                    Incident Description <span style="color:var(--fc-primary)">*</span>
                                </label>
                                <textarea name="description" class="fc-form-control" rows="5" required
                                          placeholder="Describe the incident in detail: what happened, how many affected, severity, and anything else relevant..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <button type="submit" class="fc-btn fc-btn-primary">
                            <i class="bi bi-send-fill"></i> Submit Report
                        </button>
                        <a href="dashboard.php" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>