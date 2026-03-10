<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = '';
$statusMap  = ['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id        = (int)$_POST['update_id'];
    $status    = sanitize($_POST['status']);
    $team_id   = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;
    $status_id = $statusMap[$status] ?? 1;
    $stmt = $conn->prepare("UPDATE incidents SET status_id=?, assigned_team_id=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("iii", $status_id, $team_id, $id);
    $stmt->execute();
    logActivity($_SESSION['user_id'], "Updated incident #$id → status: $status");
    $msg = "Incident #$id updated successfully.";
}

// Handle cancel request approval / rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_action'])) {
    $id         = (int)$_POST['cancel_incident_id'];
    $action     = $_POST['cancel_action'] === 'approve' ? 'approve' : 'reject';
    $admin_note = sanitize($_POST['cancel_admin_note'] ?? '');

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE incidents SET status_id=5, cancel_request='approved', cancel_admin_note=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $admin_note, $id);
        $stmt->execute();
        logActivity($_SESSION['user_id'], "Approved cancellation request for incident #$id");
        $msg = "Cancellation approved — Report #$id has been cancelled.";
    } else {
        $stmt = $conn->prepare("UPDATE incidents SET cancel_request='rejected', cancel_admin_note=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $admin_note, $id);
        $stmt->execute();
        logActivity($_SESSION['user_id'], "Rejected cancellation request for incident #$id");
        $msg = "Cancellation request for Report #$id has been rejected.";
    }
}

$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filter_cancel_req_query = isset($_GET['cancel_requests']);
if ($filter_cancel_req_query) {
    $where = "WHERE i.cancel_request = 'pending'";
} else {
    $where = ($filter && isset($statusMap[$filter])) ? "WHERE i.status_id = ".$statusMap[$filter] : '';
}

// Pagination
$per_page = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));

$count_q = $conn->query("SELECT COUNT(*) c FROM incidents i $where");
$total_count = (int)$count_q->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));
$offset = ($page - 1) * $per_page;

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay,
           u.full_name AS reporter, u.contact_number AS reporter_phone,
           t.team_name, i.assigned_team_id,
           i.cancel_request, i.cancel_reason, i.cancel_admin_note
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN users           u  ON i.user_id         = u.user_id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    $where
    ORDER BY i.created_at DESC
    LIMIT $per_page OFFSET $offset
");
if (!$reports) die("Reports query failed: " . $conn->error);

$teams_res = $conn->query("SELECT team_id AS id, team_name AS name FROM teams WHERE status='active' ORDER BY team_name");
$teams_arr = [];
if ($teams_res) while ($t = $teams_res->fetch_assoc()) $teams_arr[] = $t;

// Count per status for tabs
$cnt_res = $conn->query("SELECT status_id, COUNT(*) c FROM incidents GROUP BY status_id");
$counts = [];
while ($c = $cnt_res->fetch_assoc()) $counts[$c['status_id']] = $c['c'];
$total = array_sum($counts);

$statusCfg = [
    1 => ['label'=>'Pending',    'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.15)',  'icon'=>'bi-clock-fill'],
    2 => ['label'=>'Assigned',   'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.15)',  'icon'=>'bi-person-check-fill'],
    3 => ['label'=>'Responding', 'color'=>'#8b5cf6', 'bg'=>'rgba(139,92,246,.15)',  'icon'=>'bi-activity'],
    4 => ['label'=>'Resolved',   'color'=>'#10b981', 'bg'=>'rgba(16,185,129,.15)',  'icon'=>'bi-patch-check-fill'],
    5 => ['label'=>'Cancelled',  'color'=>'#94a3b8', 'bg'=>'rgba(148,163,184,.15)', 'icon'=>'bi-x-circle-fill'],
];
$typeIcons = [
    'fire'=>'bi-fire','flood'=>'bi-water','flooding'=>'bi-water',
    'earthquake'=>'bi-house-fill','accident'=>'bi-car-front-fill',
    'vehicular'=>'bi-car-front-fill','medical'=>'bi-heart-pulse-fill',
    'missing'=>'bi-person-x-fill','crime'=>'bi-shield-exclamation',
    'typhoon'=>'bi-cloud-lightning-rain-fill',
];
function getTypeIcon($name, $icons) {
    $lower = strtolower($name);
    foreach ($icons as $key => $icon) { if (str_contains($lower, $key)) return $icon; }
    return 'bi-exclamation-triangle-fill';
}

$all_rows = [];
while ($r = $reports->fetch_assoc()) $all_rows[] = $r;

$evidence_map = [];
if (!empty($all_rows)) {
    $inc_ids = array_column($all_rows, 'id');
    $ids_str = implode(',', $inc_ids);
    $ev_res = $conn->query("SELECT * FROM incident_evidence WHERE incident_id IN ($ids_str) ORDER BY uploaded_at ASC");
    if ($ev_res) while ($ev = $ev_res->fetch_assoc()) $evidence_map[$ev['incident_id']][] = $ev;
}
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>
    <div class="fc-main">

        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Manage Reports</div>
                    <div class="fc-breadcrumb">Admin / Incident Reports</div>
                </div>
            </div>
            <div class="fc-topbar-right no-print" style="display:flex;align-items:center;gap:12px;">
                <a href="dashboard.php" class="fc-bell-btn" title="Notifications" style="text-decoration:none;">
                    <i class="bi bi-bell-fill"></i>
                </a>
                <button onclick="window.print()" class="fc-btn" style="background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text-2);font-size:13px;padding:8px 16px;">
                    <i class="bi bi-printer-fill"></i> Print / Export
                </button>
            </div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
            <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div>
            <?php endif; ?>

            <!-- Filter tabs -->
            <?php
            $cancel_req_count = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE cancel_request='pending'")->fetch_assoc()['c'];
            $filter_cancel_req = isset($_GET['cancel_requests']) ? true : false;
            ?>
            <div class="fc-filter-tabs no-print">
                <a href="?status=" class="fc-filter-tab <?= ($filter==='' && !$filter_cancel_req) ? 'active':'' ?>">
                    All <span class="fc-tab-count"><?= $total ?></span>
                </a>
                <?php foreach (['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5] as $val=>$sid): ?>
                <a href="?status=<?= $val ?>" class="fc-filter-tab <?= ($filter===$val && !$filter_cancel_req) ? 'active':'' ?>">
                    <?= ucfirst($val) ?>
                    <?php if (!empty($counts[$sid])): ?><span class="fc-tab-count"><?= $counts[$sid] ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
                <!-- Cancel Requests tab — shown prominently if there are pending requests -->
                <a href="?cancel_requests=1" class="fc-filter-tab <?= $filter_cancel_req ? 'active' : '' ?>"
                   style="<?= $cancel_req_count > 0 ? 'border-color:#f59e0b;color:#92400e;' : '' ?>">
                    <i class="bi bi-hourglass-split" style="margin-right:4px;"></i>
                    Cancel Requests
                    <?php if ($cancel_req_count > 0): ?>
                    <span class="fc-tab-count" style="background:#f59e0b;color:#fff;"><?= $cancel_req_count ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (empty($all_rows)): ?>
            <div class="fc-card">
                <div class="fc-empty" style="padding:80px 28px;">
                    <i class="bi bi-inbox"></i><h6>No incidents found</h6>
                </div>
            </div>
            <?php else: ?>

            <div class="ir-grid">
                <?php foreach ($all_rows as $r):
                    $sid   = (int)$r['status_id'];
                    $scfg  = $statusCfg[$sid] ?? $statusCfg[1];
                    $ticon = getTypeIcon($r['type_name'] ?? '', $typeIcons);
                    $evs   = $evidence_map[$r['id']] ?? [];
                    $thumb = !empty($evs) ? $evs[0]['file_path'] : null;
                ?>
                <div class="ir-card" onclick="openAdminPanel(<?= $r['id'] ?>)">
                    <div class="ir-card-img">
                        <?php if ($thumb): ?>
                            <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
                        <?php else: ?>
                            <div class="ir-card-placeholder"><i class="bi <?= $ticon ?>"></i></div>
                        <?php endif; ?>
                        <div class="ir-card-img-overlay"></div>
                        <div class="ir-status-badge" style="background:<?= $scfg['bg'] ?>;color:<?= $scfg['color'] ?>;border:1px solid <?= $scfg['color'] ?>40;">
                            <i class="bi <?= $scfg['icon'] ?>"></i> <?= $scfg['label'] ?>
                        </div>
                        <div class="ir-id-badge">#<?= $r['id'] ?></div>
                        <?php if (!empty($r['cancel_request']) && $r['cancel_request'] === 'pending'): ?>
                        <div style="position:absolute;top:8px;left:8px;background:#f59e0b;color:#fff;padding:3px 9px;border-radius:100px;font-size:10px;font-weight:700;font-family:'Lexend',sans-serif;display:flex;align-items:center;gap:4px;z-index:3;">
                            <i class="bi bi-hourglass-split"></i> Cancel Requested
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ir-card-body">
                        <div class="ir-type-row">
                            <span class="ir-type-pill">
                                <i class="bi <?= $ticon ?>"></i>
                                <?= htmlspecialchars(strtoupper($r['type_name'] ?? 'Unknown')) ?>
                            </span>
                        </div>
                        <div class="ir-card-title"><?= htmlspecialchars($r['barangay'] ?? 'Unknown') ?></div>
                        <div class="ir-card-meta">
                            <i class="bi bi-clock" style="color:var(--fc-muted)"></i>
                            <?= date('M d, Y · g:i A', strtotime($r['created_at'])) ?>
                        </div>
                        <?php if (!empty($r['street_landmark'])): ?>
                        <div class="ir-card-meta">
                            <i class="bi bi-geo-alt-fill" style="color:var(--fc-primary)"></i>
                            <?= htmlspecialchars($r['street_landmark']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($r['description'])): ?>
                        <div class="ir-card-desc"><?= htmlspecialchars($r['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="ir-card-footer">
                        <div class="ir-reporter">
                            <div class="ir-avatar"><?= strtoupper(substr($r['reporter'] ?? 'U', 0, 1)) ?></div>
                            <span><?= htmlspecialchars($r['reporter'] ?? 'Unknown') ?></span>
                        </div>
                        <button class="fc-btn fc-btn-primary no-print" style="font-size:12px;padding:7px 14px;" onclick="event.stopPropagation();openAdminPanel(<?= $r['id'] ?>)">
                            Manage
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="fc-pagination no-print">
                <a href="?status=<?= $filter ?>&page=<?= $page-1 ?>" class="fc-page-btn <?= $page<=1?'disabled':'' ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?status=<?= $filter ?>&page=<?= $p ?>" class="fc-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <a href="?status=<?= $filter ?>&page=<?= $page+1 ?>" class="fc-page-btn <?= $page>=$total_pages?'disabled':'' ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <span class="fc-page-info">Page <?= $page ?> of <?= $total_pages ?> &bull; <?= $total_count ?> total</span>
            </div>
            <?php endif; ?>

            <div style="margin-top:14px;font-size:12px;color:var(--fc-muted);font-family:'Lexend',sans-serif;" class="no-print">
                Showing <?= count($all_rows) ?> of <?= $total_count ?> incident<?= $total_count!==1?'s':'' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Slide-in Panel Overlay -->
<div class="ir-panel-overlay no-print" id="panelOverlay" onclick="closeAdminPanel()"></div>

<?php foreach ($all_rows as $r):
    $sid   = (int)$r['status_id'];
    $scfg  = $statusCfg[$sid] ?? $statusCfg[1];
    $ticon = getTypeIcon($r['type_name'] ?? '', $typeIcons);
    $evs   = $evidence_map[$r['id']] ?? [];
    $thumb = !empty($evs) ? $evs[0]['file_path'] : null;
?>
<div class="ir-panel no-print" id="adminPanel<?= $r['id'] ?>">
    <div class="ir-panel-img">
        <?php if ($thumb): ?>
            <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
        <?php else: ?>
            <div class="ir-modal-placeholder"><i class="bi <?= $ticon ?>"></i></div>
        <?php endif; ?>
        <div class="ir-modal-img-overlay"></div>
        <button class="ir-modal-close" onclick="closeAdminPanel()"><i class="bi bi-x"></i></button>
        <div class="ir-modal-img-bottom">
            <div style="display:flex;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                <span class="ir-status-badge" style="background:<?= $scfg['bg'] ?>;color:<?= $scfg['color'] ?>;border:1px solid <?= $scfg['color'] ?>40;position:static;">
                    <i class="bi <?= $scfg['icon'] ?>"></i> <?= $scfg['label'] ?>
                </span>
                <span style="background:rgba(0,0,0,.5);color:#fff;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">#<?= $r['id'] ?></span>
            </div>
            <div style="color:#fff;font-size:16px;font-weight:700;line-height:1.3;">
                <?= htmlspecialchars($r['type_name'] ?? 'Incident') ?> — <?= htmlspecialchars($r['barangay'] ?? '') ?>
            </div>
            <div style="color:rgba(255,255,255,.65);font-size:12px;margin-top:3px;">
                <?= date('M d, Y · g:i A', strtotime($r['created_at'])) ?> · <?= htmlspecialchars($r['reporter'] ?? '') ?>
            </div>
        </div>
    </div>

    <div class="ir-panel-body">
        <div class="ir-section-label">Incident Details</div>
        <div class="ir-detail-row"><span>Type</span><strong><?= htmlspecialchars($r['type_name'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Barangay</span><strong><?= htmlspecialchars($r['barangay'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Location</span><strong><?= htmlspecialchars($r['street_landmark'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Reporter</span>
            <div style="text-align:right;">
                <strong><?= htmlspecialchars($r['reporter'] ?? '—') ?></strong>
                <?php if ($r['reporter_phone']): ?>
                <div style="font-size:11.5px;color:var(--fc-muted);"><?= htmlspecialchars($r['reporter_phone']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($r['description'])): ?>
        <div style="margin-top:10px;">
            <div class="ir-section-label">Description</div>
            <p style="font-size:13px;color:#475569;line-height:1.7;"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($evs)): ?>
        <div style="margin-top:16px;">
            <div class="ir-section-label">Evidence Photos (<?= count($evs) ?>)</div>
            <div class="ir-evidence-grid">
                <?php foreach ($evs as $ev): ?>
                <div class="ir-evidence-thumb">
                    <img src="../<?= htmlspecialchars($ev['file_path']) ?>" alt="<?= htmlspecialchars($ev['file_name']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($r['cancel_request']) && $r['cancel_request'] === 'pending'): ?>
        <!-- ⚠ CANCELLATION REQUEST ALERT — shown prominently -->
        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--fc-border);">
            <div style="background:#fff7ed;border:2px solid #f59e0b;border-radius:12px;padding:16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:18px;"></i>
                    <div style="font-family:'Lexend',sans-serif;font-weight:700;font-size:14px;color:#92400e;">
                        User Requested Cancellation
                    </div>
                </div>
                <?php if (!empty($r['cancel_reason'])): ?>
                <div style="background:#fffbeb;border-radius:8px;padding:10px 12px;margin-bottom:12px;font-size:13px;color:#78350f;line-height:1.6;">
                    <strong>Reason:</strong> <?= htmlspecialchars($r['cancel_reason']) ?>
                </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="cancel_incident_id" value="<?= $r['id'] ?>">
                    <div class="mb-2">
                        <label class="fc-form-label">Admin Note <span style="color:var(--fc-muted);font-weight:400;">(optional)</span></label>
                        <input type="text" name="cancel_admin_note" class="fc-form-control"
                               placeholder="e.g. Confirmed false alarm, Unit already dispatched...">
                    </div>
                    <div style="display:flex;gap:8px;margin-top:10px;">
                        <button type="submit" name="cancel_action" value="approve"
                                class="fc-btn fc-btn-success" style="flex:1;justify-content:center;"
                                onclick="return confirm('Approve cancellation for Report #<?= $r['id'] ?>?')">
                            <i class="bi bi-check-circle-fill"></i> Approve
                        </button>
                        <button type="submit" name="cancel_action" value="reject"
                                class="fc-btn fc-btn-danger" style="flex:1;justify-content:center;"
                                onclick="return confirm('Reject this cancellation request?')">
                            <i class="bi bi-x-circle-fill"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($r['cancel_request']) && $r['cancel_request'] === 'rejected' && !empty($r['cancel_admin_note'])): ?>
        <div style="margin-top:14px;background:#fff1f2;border:1.5px solid #fecdd3;border-radius:10px;padding:10px 12px;font-size:12.5px;color:#9f1239;">
            <i class="bi bi-x-circle-fill" style="margin-right:5px;"></i>
            <strong>Cancellation Rejected:</strong> <?= htmlspecialchars($r['cancel_admin_note']) ?>
        </div>
        <?php endif; ?>

        <!-- Update form with frontend validation -->
        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--fc-border);">
            <div class="ir-section-label">Update Report</div>
            <form method="POST" id="updateForm<?= $r['id'] ?>" onsubmit="return validateUpdate(<?= $r['id'] ?>)">
                <input type="hidden" name="update_id" value="<?= $r['id'] ?>">
                <div class="mb-3">
                    <label class="fc-form-label">Assign Response Team</label>
                    <select name="team_id" class="fc-form-control" id="team_<?= $r['id'] ?>">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($teams_arr as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $r['assigned_team_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fc-form-label">Update Status</label>
                    <select name="status" class="fc-form-control" id="status_<?= $r['id'] ?>">
                        <?php foreach (['pending','assigned','responding','resolved','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($statusMap[$s]??0)===$sid ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="validErr<?= $r['id'] ?>" style="font-size:11.5px;color:var(--fc-danger);margin-top:4px;display:none;">
                        ⚠ Please assign a team before setting status to Assigned or Responding.
                    </div>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="fc-btn" style="flex:1;justify-content:center;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);" onclick="closeAdminPanel()">
                        <i class="bi bi-x"></i> Close
                    </button>
                    <button type="submit" class="fc-btn fc-btn-primary" style="flex:1;justify-content:center;">
                        <i class="bi bi-save-fill"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function openAdminPanel(id) {
    document.getElementById('panelOverlay').classList.add('open');
    document.getElementById('adminPanel' + id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeAdminPanel() {
    document.getElementById('panelOverlay').classList.remove('open');
    document.querySelectorAll('.ir-panel.open').forEach(p => p.classList.remove('open'));
    document.body.style.overflow = '';
}

// Frontend validation: warn if setting Assigned/Responding without a team
function validateUpdate(id) {
    const status  = document.getElementById('status_' + id).value;
    const team    = document.getElementById('team_' + id).value;
    const errBox  = document.getElementById('validErr' + id);
    if ((status === 'assigned' || status === 'responding') && !team) {
        errBox.style.display = 'block';
        return false;
    }
    errBox.style.display = 'none';
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>