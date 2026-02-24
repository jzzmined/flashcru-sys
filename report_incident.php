<?php
/**
 * FlashCru Emergency Response System
 * Report Incident — Red/White/Blue Theme v3.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Report Incident';
$db = new Database();

$error   = '';
$success = '';

// Fetch dropdowns from new tables
$barangays = $db->fetchAll("SELECT id, name, district FROM barangays ORDER BY name ASC");
$types     = $db->fetchAll("SELECT id, name, icon, color FROM incident_types ORDER BY name ASC");

// Get logged-in user info
$current_user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_id = (int)($_POST['incident_type_id'] ?? 0);
    $brgy_id = (int)($_POST['barangay_id'] ?? 0);
    $contact = sanitize($_POST['contact_number'] ?? '');
    $street  = sanitize($_POST['street_landmark'] ?? '');
    $desc    = sanitize($_POST['description'] ?? '');
    $uid     = $_SESSION['user_id'];

    if (!$type_id || !$brgy_id || !$desc) {
        $error = 'Please fill in all required fields marked with *.';
    } else {
        $new_id = $db->insert('incidents', [
            'user_id'          => $uid,
            'incident_type_id' => $type_id,
            'barangay_id'      => $brgy_id,
            'contact_number'   => $contact,
            'street_landmark'  => $street,
            'description'      => $desc,
            'status_id'        => 1,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($new_id) {
            logActivity($uid, $new_id, 'incident_reported', 'New incident report submitted');
            $success = 'Your incident report has been submitted successfully. Our team will respond shortly.';
            $_POST = [];
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
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

            <div class="flex-between mb-20">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--navy);">🚨 Report an Incident</h2>
                    <p class="text-muted" style="font-size:13px;margin-top:3px;">Submit a new emergency incident report</p>
                </div>
                <a href="incidents.php" class="btn btn-secondary">← Back to Reports</a>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

                <!-- Main Form -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">📋 Incident Details</h3>
                    </div>
                    <div class="panel-body">
                        <form method="POST" id="reportForm">

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group">
                                    <label class="form-label">Full Name (Auto-filled)</label>
                                    <input type="text" class="form-control"
                                           value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>"
                                           readonly style="background:var(--gray-100);cursor:not-allowed;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control"
                                           placeholder="09XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['contact_number'] ?? $current_user['contact_number'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group">
                                    <label class="form-label">Incident Type *</label>
                                    <select name="incident_type_id" class="form-control" required>
                                        <option value="">— Select Incident Type —</option>
                                        <?php foreach ($types as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"
                                            <?php echo (($_POST['incident_type_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Barangay *</label>
                                    <select name="barangay_id" class="form-control" required>
                                        <option value="">— Select Barangay —</option>
                                        <?php
                                        $cur_district = '';
                                        foreach ($barangays as $b):
                                            if ($b['district'] !== $cur_district):
                                                if ($cur_district !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($b['district']) . '">';
                                                $cur_district = $b['district'];
                                            endif;
                                        ?>
                                        <option value="<?php echo $b['id']; ?>"
                                            <?php echo (($_POST['barangay_id'] ?? '') == $b['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['name']); ?>
                                        </option>
                                        <?php endforeach;
                                        if ($cur_district !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Street / Landmark</label>
                                <input type="text" name="street_landmark" class="form-control"
                                       placeholder="e.g. Near Gaisano Mall, Cor. Quirino Ave."
                                       value="<?php echo htmlspecialchars($_POST['street_landmark'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="5"
                                          placeholder="Describe what happened — what you saw, how many people affected, any immediate dangers..."
                                          required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div style="display:flex;gap:12px;margin-top:4px;">
                                <button type="submit" class="btn btn-primary" style="flex:1;">🚨 Submit Report</button>
                                <button type="reset" class="btn btn-secondary">Clear</button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div style="display:flex;flex-direction:column;gap:16px;">

                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">ℹ️ What Happens Next?</h3>
                        </div>
                        <div class="panel-body">
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <?php
                                $steps = [
                                    ['1','#A63244','Report Received','Your report is logged and reviewed by dispatch immediately.'],
                                    ['2','#e67e22','Team Dispatched','A response team is assigned and sent to your location.'],
                                    ['3','#3498db','Ongoing Response','Our team is on scene managing the incident.'],
                                    ['4','#21BF73','Resolved','Incident resolved. Track status in your reports.'],
                                ];
                                foreach ($steps as [$num,$color,$title,$desc]):
                                ?>
                                <div style="display:flex;gap:12px;align-items:flex-start;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:<?php echo $color; ?>;color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;font-family:'JetBrains Mono',monospace;"><?php echo $num; ?></div>
                                    <div>
                                        <div style="font-size:13px;font-weight:700;color:var(--navy);"><?php echo $title; ?></div>
                                        <div style="font-size:11.5px;color:var(--gray-400);margin-top:2px;"><?php echo $desc; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel" style="border-left:4px solid var(--red-600);">
                        <div class="panel-header">
                            <h3 class="panel-title" style="color:var(--red-600);">📞 Life-Threatening?</h3>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:12.5px;color:var(--gray-500);margin-bottom:14px;">If this is a life-threatening emergency, call immediately:</p>
                            <a href="tel:911" class="btn btn-primary w-full" style="margin-bottom:8px;display:block;text-align:center;text-decoration:none;">📞 Call 911</a>
                            <div style="font-size:12px;color:var(--gray-400);margin-top:10px;">
                                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--gray-100);">
                                    <span>BFP Davao</span><span style="font-family:'JetBrains Mono',monospace;font-weight:600;">(082) 221-3233</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--gray-100);">
                                    <span>CDRRMO</span><span style="font-family:'JetBrains Mono',monospace;font-weight:600;">(082) 224-5487</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:5px 0;">
                                    <span>PNP Davao</span><span style="font-family:'JetBrains Mono',monospace;font-weight:600;">(082) 241-0017</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>