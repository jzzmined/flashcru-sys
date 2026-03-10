<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_id = (int)($_POST['incident_type_id'] ?? 0);
    $bar_id  = (int)($_POST['barangay_id']      ?? 0);
    $desc    = sanitize($_POST['description']   ?? '');
    $loc     = sanitize($_POST['location_detail']?? '');
    $uid     = (int)$_SESSION['user_id'];

    if (!$type_id || !$bar_id || !$desc) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($desc) < 10) {
        $error = 'Description must be at least 10 characters.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO incidents (user_id, incident_type_id, barangay_id, street_landmark, description, status_id, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        if (!$stmt) die("Prepare failed: " . $conn->error);
        $stmt->bind_param("iiiss", $uid, $type_id, $bar_id, $loc, $desc);
        if ($stmt->execute()) {
            $inc_id = $conn->insert_id;

            // Handle file uploads
            if (!empty($_FILES['evidence']['name'][0])) {
                $upload_dir = '../assets/uploads/evidence/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                foreach ($_FILES['evidence']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $mime = mime_content_type($tmp);
                    if (!in_array($mime, $allowed)) continue;
                    if ($_FILES['evidence']['size'][$i] > 10 * 1024 * 1024) continue;
                    $ext      = pathinfo($_FILES['evidence']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'evidence_' . $inc_id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmp, $upload_dir . $filename)) {
                        $filepath = 'assets/uploads/evidence/' . $filename;
                        $orig     = $conn->real_escape_string($_FILES['evidence']['name'][$i]);
                        $conn->query("INSERT INTO incident_evidence (incident_id, file_path, file_name) VALUES ($inc_id, '$filepath', '$orig')");
                    }
                }
            }

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
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Report Incident</div>
                    <div class="fc-breadcrumb">User / Report New Incident</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <a href="dashboard.php" class="fc-bell-btn" title="Notifications" style="text-decoration:none;">
                    <i class="bi bi-bell-fill"></i>
                </a>
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
                    &mdash; <a href="my_reports.php" style="color:var(--fc-success);font-weight:600;">View my reports</a>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="incidentForm" novalidate>

                    <!-- Incident Info -->
                    <div class="fc-form-section" style="margin-bottom:20px;">
                        <div class="fc-form-section-title">
                            <i class="bi bi-exclamation-triangle-fill"></i> Incident Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fc-form-label">Incident Type <span style="color:var(--fc-primary)">*</span></label>
                                <select name="incident_type_id" id="f_type" class="fc-form-control" required>
                                    <option value="">Select incident type</option>
                                    <?php while ($it = $inc_types->fetch_assoc()): ?>
                                    <option value="<?= $it['id'] ?>" <?= (isset($_POST['incident_type_id']) && $_POST['incident_type_id'] == $it['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($it['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="fc-field-err" id="err_type"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="fc-form-label">Barangay <span style="color:var(--fc-primary)">*</span></label>
                                <select name="barangay_id" id="f_barangay" class="fc-form-control" required>
                                    <option value="">Select barangay</option>
                                    <?php while ($b = $barangays->fetch_assoc()): ?>
                                    <option value="<?= $b['id'] ?>" <?= (isset($_POST['barangay_id']) && $_POST['barangay_id'] == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="fc-field-err" id="err_barangay"></div>
                            </div>

                            <div class="col-12">
                                <label class="fc-form-label">Exact Location / Landmark</label>
                                <input type="text" name="location_detail" id="f_location" class="fc-form-control"
                                       placeholder="e.g. Near public market, corner Rizal St."
                                       value="<?= htmlspecialchars($_POST['location_detail'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label class="fc-form-label">
                                    Description <span style="color:var(--fc-primary)">*</span>
                                    <span id="descCount" style="font-weight:400;color:var(--fc-muted);font-size:11px;margin-left:6px;">0 / 500</span>
                                </label>
                                <textarea name="description" id="f_desc" class="fc-form-control" rows="5" required maxlength="500"
                                          placeholder="Describe the incident in detail: what happened, how many affected, severity..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                <div class="fc-field-err" id="err_desc"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Evidence Upload -->
                    <div class="fc-form-section" style="margin-bottom:20px;">
                        <div class="fc-form-section-title">
                            <i class="bi bi-image-fill"></i> Attach Evidence
                            <span style="font-size:11.5px;font-weight:400;color:var(--fc-muted);margin-left:4px;">(optional)</span>
                        </div>

                        <div class="fc-dropzone" id="dropzone">
                            <input type="file" name="evidence[]" id="evidenceInput"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   multiple style="display:none;">
                            <div class="fc-dropzone-body" id="dropzoneBody" onclick="document.getElementById('evidenceInput').click()">
                                <i class="bi bi-cloud-arrow-up-fill fc-dropzone-icon"></i>
                                <p class="fc-dropzone-text">Drop files here or <span class="fc-dropzone-browse">browse</span></p>
                                <p class="fc-dropzone-hint">JPG, PNG, WEBP · Max 10MB each</p>
                            </div>
                            <div class="fc-dropzone-previews" id="previewContainer"></div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn" class="fc-btn fc-btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:15px;letter-spacing:.04em;text-transform:uppercase;">
                        <i class="bi bi-send-fill"></i> Submit Incident Report
                    </button>
                    <a href="dashboard.php" class="fc-btn" style="width:100%;justify-content:center;margin-top:10px;background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);">
                        Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.fc-field-err { font-size: 11.5px; color: var(--fc-danger); margin-top: 4px; min-height: 16px; }
.fc-form-control.is-invalid { border-color: var(--fc-danger); box-shadow: 0 0 0 3px rgba(239,68,68,.12); }
.fc-form-control.is-valid   { border-color: var(--fc-success); }
</style>

<script>
// Description char counter
const descTa = document.getElementById('f_desc');
const counter = document.getElementById('descCount');
function updateCount() {
    const len = descTa.value.length;
    counter.textContent = len + ' / 500';
    counter.style.color = len > 450 ? '#ef4444' : '#94a3b8';
}
descTa.addEventListener('input', updateCount);
updateCount();

// Frontend validation
document.getElementById('incidentForm').addEventListener('submit', function(e) {
    let valid = true;

    function setErr(fieldId, errId, msg) {
        const el = document.getElementById(fieldId);
        const errEl = document.getElementById(errId);
        if (msg) {
            el.classList.add('is-invalid'); el.classList.remove('is-valid');
            errEl.textContent = msg; valid = false;
        } else {
            el.classList.remove('is-invalid'); el.classList.add('is-valid');
            errEl.textContent = '';
        }
    }

    const type     = document.getElementById('f_type').value;
    const barangay = document.getElementById('f_barangay').value;
    const desc     = descTa.value.trim();

    setErr('f_type',     'err_type',     !type     ? 'Please select an incident type.'  : '');
    setErr('f_barangay', 'err_barangay', !barangay ? 'Please select a barangay.'        : '');
    setErr('f_desc',     'err_desc',     !desc     ? 'Description is required.'         :
                                          desc.length < 10 ? 'Please provide more detail (min 10 characters).' : '');

    if (!valid) { e.preventDefault(); window.scrollTo({top: 0, behavior: 'smooth'}); }
    else {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
        btn.disabled = true;
    }
});

// Clear validation on input
['f_type','f_barangay','f_desc'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        this.classList.remove('is-invalid','is-valid');
        const errId = 'err_' + id.replace('f_','');
        if (document.getElementById(errId)) document.getElementById(errId).textContent = '';
    });
});

// Dropzone
const input   = document.getElementById('evidenceInput');
const dropzone = document.getElementById('dropzone');
const preview = document.getElementById('previewContainer');
const body    = document.getElementById('dropzoneBody');

input.addEventListener('change', () => handleFiles(input.files));
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('fc-dropzone--active'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('fc-dropzone--active'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('fc-dropzone--active');
    handleFiles(e.dataTransfer.files);
});

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        if (file.size > 10 * 1024 * 1024) { alert(file.name + ' exceeds 10MB limit.'); return; }
        const reader = new FileReader();
        reader.onload = e => {
            const item = document.createElement('div');
            item.className = 'fc-preview-item';
            item.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}">
                <div class="fc-preview-name">${file.name}</div>
                <button type="button" class="fc-preview-remove" onclick="this.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>`;
            preview.appendChild(item);
            body.style.padding = '12px 16px 0';
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php include '../includes/footer.php'; ?>