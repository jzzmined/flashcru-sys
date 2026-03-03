<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid = (int) $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id     = (int)$_POST['edit_id'];
    $description = sanitize($_POST['description']    ?? '');
    $landmark    = sanitize($_POST['street_landmark'] ?? '');
    $check = $conn->query("SELECT id FROM incidents WHERE id=$edit_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET description='$description', street_landmark='$landmark', updated_at=NOW() WHERE id=$edit_id");
        $msg = "Report #$edit_id updated successfully.";
    } else {
        $msg      = "You can only edit pending reports.";
        $msg_type = 'error';
    }
}

// Handle Cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $check = $conn->query("SELECT id FROM incidents WHERE id=$cancel_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET status_id=5, updated_at=NOW() WHERE id=$cancel_id");
        $msg = "Report #$cancel_id has been cancelled.";
    } else {
        $msg      = "You can only cancel pending reports.";
        $msg_type = 'error';
    }
}

$filter     = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$status_map = ['pending'=>1,'dispatched'=>2,'ongoing'=>3,'resolved'=>4,'cancelled'=>5];
$status_filter = isset($status_map[$filter]) ? " AND i.status_id = ".$status_map[$filter] : '';

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, t.team_name,
           b.name AS barangay, rs.name AS status_name, rs.color AS status_color
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    LEFT JOIN barangays       b  ON i.barangay_id = b.id
    LEFT JOIN report_status   rs ON i.status_id = rs.id
    WHERE i.user_id = $uid $status_filter
    ORDER BY i.created_at DESC
");
if (!$reports) die("Query failed: ".$conn->error);

// Count per status for tab badges
$counts = [];
$cnt_res = $conn->query("SELECT status_id, COUNT(*) c FROM incidents WHERE user_id=$uid GROUP BY status_id");
while ($c = $cnt_res->fetch_assoc()) $counts[$c['status_id']] = $c['c'];
$total = array_sum($counts);

// Status config: label, color, icon
$statusCfg = [
    1 => ['label'=>'Pending',    'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.15)',  'icon'=>'bi-clock-fill'],
    2 => ['label'=>'Assigned',   'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.15)',  'icon'=>'bi-person-check-fill'],
    3 => ['label'=>'Responding', 'color'=>'#8b5cf6', 'bg'=>'rgba(139,92,246,.15)',  'icon'=>'bi-activity'],
    4 => ['label'=>'Resolved',   'color'=>'#21bf73', 'bg'=>'rgba(33,191,115,.15)',  'icon'=>'bi-patch-check-fill'],
    5 => ['label'=>'Cancelled',  'color'=>'#94a3b8', 'bg'=>'rgba(148,163,184,.15)', 'icon'=>'bi-x-circle-fill'],
];

// Incident type icon map
$typeIcons = [
    'fire'      => 'bi-fire',
    'flood'     => 'bi-water',
    'flooding'  => 'bi-water',
    'earthquake'=> 'bi-house-fill',
    'accident'  => 'bi-car-front-fill',
    'vehicular' => 'bi-car-front-fill',
    'medical'   => 'bi-heart-pulse-fill',
    'missing'   => 'bi-person-x-fill',
    'crime'     => 'bi-shield-exclamation',
    'typhoon'   => 'bi-cloud-lightning-rain-fill',
];
function getTypeIcon($name, $icons) {
    $lower = strtolower($name);
    foreach ($icons as $key => $icon) {
        if (str_contains($lower, $key)) return $icon;
    }
    return 'bi-exclamation-triangle-fill';
}

// Fetch evidence for all reports
$inc_ids = [];
$all_rows = [];
$reports->data_seek(0);
while ($r = $reports->fetch_assoc()) { $all_rows[] = $r; $inc_ids[] = $r['id']; }
$evidence_map = [];
if (!empty($inc_ids)) {
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
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">My Reports</div>
                    <div class="fc-breadcrumb">Recent Incident Reports</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <a href="report_incident.php" class="fc-btn fc-btn-primary" style="font-size:13px;padding:9px 18px;">
                    <i class="bi bi-plus-circle-fill"></i> New Report
                </a>
            </div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
            <div class="fc-alert fc-alert-<?= $msg_type==='error'?'error':'success' ?>">
                <i class="bi bi-<?= $msg_type==='error'?'exclamation-circle-fill':'check-circle-fill' ?>"></i> <?= $msg ?>
            </div>
            <?php endif; ?>

            <!-- Filter tabs with counts -->
            <div class="fc-filter-tabs" style="margin-bottom:24px;">
                <a href="?status=" class="fc-filter-tab <?= $filter==='' ? 'active' : '' ?>">
                    All <span class="fc-tab-count"><?= $total ?></span>
                </a>
                <?php foreach (['pending'=>1,'dispatched'=>2,'ongoing'=>3,'resolved'=>4,'cancelled'=>5] as $val=>$sid): ?>
                <a href="?status=<?= $val ?>" class="fc-filter-tab <?= $filter===$val ? 'active' : '' ?>">
                    <?= ucfirst($val) ?>
                    <?php if (!empty($counts[$sid])): ?>
                    <span class="fc-tab-count"><?= $counts[$sid] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($all_rows)): ?>
            <div class="fc-empty" style="padding:80px 28px;">
                <i class="bi bi-file-earmark-x"></i>
                <h6>No Reports Found</h6>
                <p style="font-size:13px;margin-bottom:20px;">
                    <?= $filter ? "No reports with status \"$filter\"." : "You haven't submitted any incident reports yet." ?>
                </p>
                <a href="report_incident.php" class="fc-btn fc-btn-primary"><i class="bi bi-plus-circle-fill"></i> Submit a Report</a>
            </div>
            <?php else: ?>

            <!-- Card grid -->
            <div class="ir-grid">
                <?php foreach ($all_rows as $r):
                    $sid   = (int)$r['status_id'];
                    $scfg  = $statusCfg[$sid] ?? $statusCfg[1];
                    $ticon = getTypeIcon($r['type_name'] ?? '', $typeIcons);
                    $evs   = $evidence_map[$r['id']] ?? [];
                    $thumb = !empty($evs) ? $evs[0]['file_path'] : null;
                ?>
                <div class="ir-card" onclick="openUserModal(<?= $r['id'] ?>)">

                    <!-- Image / placeholder -->
                    <div class="ir-card-img">
                        <?php if ($thumb): ?>
                            <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
                        <?php else: ?>
                            <div class="ir-card-placeholder">
                                <i class="bi <?= $ticon ?>"></i>
                            </div>
                        <?php endif; ?>
                        <div class="ir-card-img-overlay"></div>

                        <!-- Status badge top-left -->
                        <div class="ir-status-badge" style="background:<?= $scfg['bg'] ?>;color:<?= $scfg['color'] ?>;border:1px solid <?= $scfg['color'] ?>40;">
                            <i class="bi <?= $scfg['icon'] ?>"></i> <?= $scfg['label'] ?>
                        </div>

                        <!-- ID top-right -->
                        <div class="ir-id-badge">#<?= $r['id'] ?></div>
                    </div>

                    <!-- Body -->
                    <div class="ir-card-body">
                        <div class="ir-type-row">
                            <span class="ir-type-pill">
                                <i class="bi <?= $ticon ?>"></i>
                                <?= htmlspecialchars(strtoupper($r['type_name'] ?? 'Unknown')) ?>
                            </span>
                        </div>

                        <div class="ir-card-title"><?= htmlspecialchars($r['barangay'] ?? 'Unknown Location') ?></div>

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
                        <div class="ir-card-desc">
                            <?= htmlspecialchars($r['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer -->
                    <div class="ir-card-footer">
                        <div class="ir-reporter">
                            <div class="ir-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
                            <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                        </div>
                        <button class="fc-btn fc-btn-primary" style="font-size:12px;padding:7px 16px;" onclick="event.stopPropagation();openUserModal(<?= $r['id'] ?>)">
                            View Details
                        </button>
                    </div>
                </div>

                <!-- ── User Detail Modal ── -->
                <div class="ir-modal-overlay" id="userModal<?= $r['id'] ?>" onclick="closeUserModal(event,this)">
                    <div class="ir-modal">

                        <!-- Modal image header -->
                        <div class="ir-modal-img">
                            <?php if ($thumb): ?>
                                <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
                            <?php else: ?>
                                <div class="ir-modal-placeholder"><i class="bi <?= $ticon ?>"></i></div>
                            <?php endif; ?>
                            <div class="ir-modal-img-overlay"></div>
                            <button class="ir-modal-close" onclick="document.getElementById('userModal<?= $r['id'] ?>').classList.remove('open')">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="ir-modal-img-bottom">
                                <div style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">
                                    <span class="ir-status-badge" style="background:<?= $scfg['bg'] ?>;color:<?= $scfg['color'] ?>;border:1px solid <?= $scfg['color'] ?>40;position:static;">
                                        <i class="bi <?= $scfg['icon'] ?>"></i> <?= $scfg['label'] ?>
                                    </span>
                                    <span style="background:rgba(0,0,0,.5);color:#fff;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">#<?= $r['id'] ?></span>
                                </div>
                                <div style="color:#fff;font-size:16px;font-weight:700;line-height:1.3;">
                                    <?= htmlspecialchars($r['type_name'] ?? 'Incident') ?> — <?= htmlspecialchars($r['barangay'] ?? '') ?>
                                </div>
                                <div style="color:rgba(255,255,255,.65);font-size:12px;margin-top:3px;">
                                    Submitted <?= date('M d, Y · g:i A', strtotime($r['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <div class="ir-modal-body">

                            <!-- Status timeline -->
                            <div class="ir-section-label">Report Progress</div>
                            <div class="ir-timeline">
                                <?php
                                $steps = [1=>'Submitted',2=>'Assigned',3=>'Responding',4=>'Resolved'];
                                foreach ($steps as $step_id => $step_label):
                                    $cls = '';
                                    if ($sid >= $step_id) $cls = $sid > $step_id ? 'done' : 'current';
                                    if ($sid == 5) $cls = $step_id == 1 ? 'done' : ''; // cancelled
                                ?>
                                <div class="ir-timeline-step <?= $cls ?>">
                                    <div class="ir-timeline-dot">
                                        <?php if ($cls==='done'): ?><i class="bi bi-check"></i>
                                        <?php elseif ($cls==='current'): ?><i class="bi bi-circle-fill" style="font-size:8px;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ir-timeline-label"><?= $step_label ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php if ($sid == 5): ?>
                                <div class="ir-timeline-step cancelled-step">
                                    <div class="ir-timeline-dot cancelled"><i class="bi bi-x"></i></div>
                                    <div class="ir-timeline-label" style="color:#94a3b8;">Cancelled</div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div style="height:1px;background:var(--fc-border);margin:18px 0;"></div>

                            <!-- Details -->
                            <div class="ir-section-label">Incident Details</div>
                            <div class="ir-detail-row"><span>Type</span><strong><?= htmlspecialchars($r['type_name'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row"><span>Barangay</span><strong><?= htmlspecialchars($r['barangay'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row"><span>Location</span><strong><?= htmlspecialchars($r['street_landmark'] ?? '—') ?></strong></div>
                            <div class="ir-detail-row"><span>Assigned Team</span>
                                <strong style="color:<?= $r['team_name'] ? 'var(--fc-success)' : 'var(--fc-muted)' ?>">
                                    <?= htmlspecialchars($r['team_name'] ?? 'Not yet assigned') ?>
                                </strong>
                            </div>
                            <?php if (!empty($r['description'])): ?>
                            <div style="margin-top:10px;">
                                <div class="ir-section-label">Description</div>
                                <p style="font-size:13px;color:#475569;line-height:1.7;"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- Evidence -->
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

                            <!-- Actions for pending -->
                            <?php if ($sid === 1): ?>
                            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--fc-border);display:flex;gap:10px;flex-wrap:wrap;">
                                <button class="fc-btn fc-btn-primary" style="flex:1;justify-content:center;" onclick="openEditModal(<?= $r['id'] ?>)">
                                    <i class="bi bi-pencil-fill"></i> Edit Report
                                </button>
                                <button class="fc-btn" style="flex:1;justify-content:center;background:#fff;border:1.5px solid var(--fc-danger);color:var(--fc-danger);" onclick="openCancelModal(<?= $r['id'] ?>)">
                                    <i class="bi bi-x-circle-fill"></i> Cancel
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>

            <!-- Showing count -->
            <div style="margin-top:22px;font-size:12.5px;color:var(--fc-muted);font-family:'Lexend',sans-serif;">
                Showing <?= count($all_rows) ?> report<?= count($all_rows)!==1?'s':'' ?>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal (Bootstrap, outside grid) -->
<?php foreach ($all_rows as $r): if ((int)$r['status_id'] !== 1) continue; ?>
<div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill" style="color:var(--fc-primary);margin-right:8px;"></i> Edit Report #<?= $r['id'] ?></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" value="<?= $r['id'] ?>">
                    <div class="mb-3">
                        <label class="fc-form-label">Incident Type</label>
                        <input type="text" class="fc-form-control" value="<?= htmlspecialchars($r['type_name']) ?>" readonly style="background:#f7f9fc;">
                    </div>
                    <div class="mb-3">
                        <label class="fc-form-label">Barangay</label>
                        <input type="text" class="fc-form-control" value="<?= htmlspecialchars($r['barangay'] ?? '') ?>" readonly style="background:#f7f9fc;">
                    </div>
                    <div class="mb-3">
                        <label class="fc-form-label">Location / Landmark</label>
                        <input type="text" name="street_landmark" class="fc-form-control" value="<?= htmlspecialchars($r['street_landmark'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="fc-form-label">Description</label>
                        <textarea name="description" class="fc-form-control" rows="4"><?= htmlspecialchars($r['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="fc-btn fc-btn-primary"><i class="bi bi-save-fill"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill" style="color:var(--fc-danger);margin-right:8px;"></i> Cancel Report</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:13.5px;">
                Cancel <strong>Report #<?= $r['id'] ?></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);" data-bs-dismiss="modal">No, Keep It</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="fc-btn" style="background:var(--fc-danger);color:#fff;"><i class="bi bi-x-circle-fill"></i> Yes, Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function openUserModal(id) {
    document.getElementById('userModal' + id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeUserModal(e, el) {
    if (e.target === el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
function openEditModal(id) {
    // close detail modal first
    document.querySelectorAll('.ir-modal-overlay.open').forEach(m => m.classList.remove('open'));
    document.body.style.overflow = '';
    var bsModal = new bootstrap.Modal(document.getElementById('editModal' + id));
    bsModal.show();
}
function openCancelModal(id) {
    document.querySelectorAll('.ir-modal-overlay.open').forEach(m => m.classList.remove('open'));
    document.body.style.overflow = '';
    var bsModal = new bootstrap.Modal(document.getElementById('cancelModal' + id));
    bsModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>