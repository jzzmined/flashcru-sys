<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid = (int) $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// Handle Edit (only pending reports)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id     = (int)$_POST['edit_id'];
    $description = sanitize($_POST['description']    ?? '');
    $landmark    = sanitize($_POST['street_landmark'] ?? '');
    $check = $conn->query("SELECT id FROM incidents WHERE id=$edit_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET description='$description', street_landmark='$landmark', updated_at=NOW() WHERE id=$edit_id");
        logActivity($uid, "Edited incident report #$edit_id");
        $msg = "Report #$edit_id updated successfully.";
    } else {
        $msg      = "You can only edit pending reports.";
        $msg_type = 'error';
    }
}

// Handle Cancel (only pending reports)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $check = $conn->query("SELECT id FROM incidents WHERE id=$cancel_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET status_id=5, updated_at=NOW() WHERE id=$cancel_id");
        logActivity($uid, "Cancelled incident report #$cancel_id");
        $msg = "Report #$cancel_id has been cancelled.";
    } else {
        $msg      = "You can only cancel pending reports.";
        $msg_type = 'error';
    }
}

// FIX: status_map now matches actual DB values (dispatched/ongoing were wrong)
$filter     = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$status_map = ['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5];
$status_filter = isset($status_map[$filter]) ? " AND i.status_id = ".$status_map[$filter] : '';

// Pagination
$per_page   = 9;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $per_page;

$total_count_res = $conn->query("SELECT COUNT(*) c FROM incidents i WHERE i.user_id = $uid" . $status_filter);
$total_count = (int)$total_count_res->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));

// FIX: Removed broken LEFT JOIN report_status (table doesn't exist)
$reports = $conn->query("
    SELECT i.*, it.name AS type_name, t.team_name,
           b.name AS barangay
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    LEFT JOIN barangays       b  ON i.barangay_id = b.id
    WHERE i.user_id = $uid $status_filter
    ORDER BY i.created_at DESC
    LIMIT $per_page OFFSET $offset
");
if (!$reports) die("Query failed: ".$conn->error);

// Count per status for tab badges
$counts = [];
$cnt_res = $conn->query("SELECT status_id, COUNT(*) c FROM incidents WHERE user_id=$uid GROUP BY status_id");
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
while ($r = $reports->fetch_assoc()) { $all_rows[] = $r; }

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
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">My Reports</div>
                    <div class="fc-breadcrumb">My Incident Reports</div>
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
            <div class="fc-alert fc-alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>">
                <i class="bi bi-<?= $msg_type === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i> <?= $msg ?>
            </div>
            <?php endif; ?>

            <!-- Notification toast (for status updates) -->
            <div id="fc-notif-toast">
                <span class="notif-icon"><i class="bi bi-bell-fill"></i></span>
                <span id="notif-message">Your report status has been updated.</span>
                <button class="notif-close" onclick="closeToast()">×</button>
            </div>

            <!-- FIX: Filter tab labels now match DB status names -->
            <div class="fc-filter-tabs">
                <a href="?status=" class="fc-filter-tab <?= $filter==='' ? 'active' : '' ?>">
                    All <span class="fc-tab-count"><?= $total ?></span>
                </a>
                <?php foreach (['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5] as $val=>$sid): ?>
                <a href="?status=<?= $val ?>" class="fc-filter-tab <?= $filter===$val ? 'active' : '' ?>">
                    <?= ucfirst($val) ?>
                    <?php if (!empty($counts[$sid])): ?>
                    <span class="fc-tab-count"><?= $counts[$sid] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($all_rows)): ?>
            <div class="fc-card">
                <div class="fc-empty" style="padding:80px 28px;">
                    <i class="bi bi-file-earmark-x"></i>
                    <h6>No Reports Found</h6>
                    <p><?= $filter ? "No reports with status \"".ucfirst($filter)."\"." : "You haven't submitted any incident reports yet." ?></p>
                    <a href="report_incident.php" class="fc-btn fc-btn-primary" style="margin-top:16px;"><i class="bi bi-plus-circle-fill"></i> Submit a Report</a>
                </div>
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
                    </div>
                    <div class="ir-card-body">
                        <div class="ir-type-row">
                            <span class="ir-type-pill"><i class="bi <?= $ticon ?>"></i><?= htmlspecialchars(strtoupper($r['type_name'] ?? 'Unknown')) ?></span>
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
                        <div style="font-size:12px;color:var(--fc-muted);">
                            <?php if ($r['team_name']): ?>
                                <i class="bi bi-people-fill" style="color:var(--fc-success)"></i>
                                <span style="color:var(--fc-success);font-weight:600;"><?= htmlspecialchars($r['team_name']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--fc-muted);">Unassigned</span>
                            <?php endif; ?>
                        </div>
                        <button class="fc-btn fc-btn-primary" style="font-size:12px;padding:7px 14px;" onclick="event.stopPropagation();openUserModal(<?= $r['id'] ?>)">
                            View
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="fc-pagination">
                <a href="?status=<?= $filter ?>&page=<?= $page-1 ?>" class="fc-page-btn <?= $page<=1?'disabled':'' ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?status=<?= $filter ?>&page=<?= $p ?>" class="fc-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <a href="?status=<?= $filter ?>&page=<?= $page+1 ?>" class="fc-page-btn <?= $page>=$total_pages?'disabled':'' ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <span class="fc-page-info">Page <?= $page ?> of <?= $total_pages ?></span>
            </div>
            <?php endif; ?>

            <div style="margin-top:16px;font-size:12px;color:var(--fc-muted);font-family:'Lexend',sans-serif;">
                Showing <?= count($all_rows) ?> of <?= $total_count ?> report<?= $total_count!==1?'s':'' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail modals -->
<?php foreach ($all_rows as $r):
    $sid   = (int)$r['status_id'];
    $scfg  = $statusCfg[$sid] ?? $statusCfg[1];
    $ticon = getTypeIcon($r['type_name'] ?? '', $typeIcons);
    $evs   = $evidence_map[$r['id']] ?? [];
    $thumb = !empty($evs) ? $evs[0]['file_path'] : null;
    $steps = [1=>'Pending',2=>'Assigned',3=>'Responding',4=>'Resolved'];
?>
<div class="ir-modal-overlay" id="userModal<?= $r['id'] ?>" onclick="closeUserModal(event, this)">
    <div class="ir-modal">
        <div class="ir-modal-img">
            <?php if ($thumb): ?>
                <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
            <?php else: ?>
                <div class="ir-modal-placeholder"><i class="bi <?= $ticon ?>"></i></div>
            <?php endif; ?>
            <div class="ir-modal-img-overlay"></div>
            <button class="ir-modal-close" onclick="document.getElementById('userModal<?= $r['id'] ?>').classList.remove('open');document.body.style.overflow=''"><i class="bi bi-x"></i></button>
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
                    <?= date('M d, Y · g:i A', strtotime($r['created_at'])) ?>
                </div>
            </div>
        </div>

        <div class="ir-modal-body">
            <!-- Timeline -->
            <div class="ir-section-label">Status Timeline</div>
            <div class="ir-timeline" style="margin-bottom:18px;">
                <?php foreach ($steps as $step_id => $step_label): ?>
                <div class="ir-timeline-step <?= $sid > $step_id ? 'done' : ($sid === $step_id ? 'current' : '') ?>">
                    <div class="ir-timeline-dot">
                        <?php if ($sid > $step_id): ?><i class="bi bi-check2"></i>
                        <?php elseif ($sid === $step_id): ?><i class="bi bi-arrow-right-short"></i>
                        <?php else: ?><?= $step_id ?><?php endif; ?>
                    </div>
                    <div class="ir-timeline-label"><?= $step_label ?></div>
                </div>
                <?php endforeach; ?>
                <?php if ($sid == 5): ?>
                <div class="ir-timeline-step">
                    <div class="ir-timeline-dot" style="background:#94a3b8;border-color:#94a3b8;color:#fff;"><i class="bi bi-x"></i></div>
                    <div class="ir-timeline-label" style="color:#94a3b8;">Cancelled</div>
                </div>
                <?php endif; ?>
            </div>

            <div style="height:1px;background:var(--fc-border);margin:18px 0;"></div>

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
            <div style="margin-top:12px;">
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

            <!-- Actions: only for pending reports -->
            <?php if ($sid === 1): ?>
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--fc-border);display:flex;gap:10px;flex-wrap:wrap;">
                <button class="fc-btn fc-btn-primary" style="flex:1;justify-content:center;" onclick="openEditModal(<?= $r['id'] ?>)">
                    <i class="bi bi-pencil-fill"></i> Edit Report
                </button>
                <button class="fc-btn fc-btn-danger" style="flex:1;justify-content:center;" onclick="openCancelModal(<?= $r['id'] ?>)">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Modals -->
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
                        <input type="text" class="fc-form-control" value="<?= htmlspecialchars($r['type_name']) ?>" readonly style="background:#f7f9fc;cursor:not-allowed;">
                    </div>
                    <div class="mb-3">
                        <label class="fc-form-label">Barangay</label>
                        <input type="text" class="fc-form-control" value="<?= htmlspecialchars($r['barangay'] ?? '') ?>" readonly style="background:#f7f9fc;cursor:not-allowed;">
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

<!-- Cancel Modals -->
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
                <button type="button" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);" data-bs-dismiss="modal">Keep It</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="fc-btn fc-btn-danger"><i class="bi bi-x-circle-fill"></i> Yes, Cancel</button>
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
    document.querySelectorAll('.ir-modal-overlay.open').forEach(m => { m.classList.remove('open'); });
    document.body.style.overflow = '';
    new bootstrap.Modal(document.getElementById('editModal' + id)).show();
}
function openCancelModal(id) {
    document.querySelectorAll('.ir-modal-overlay.open').forEach(m => { m.classList.remove('open'); });
    document.body.style.overflow = '';
    new bootstrap.Modal(document.getElementById('cancelModal' + id)).show();
}

// Notification toast (checks for status changes every 30s)
function closeToast() {
    document.getElementById('fc-notif-toast').classList.remove('show');
}
let lastStatuses = {};
<?php foreach ($all_rows as $r): ?>
lastStatuses[<?= $r['id'] ?>] = <?= $r['status_id'] ?>;
<?php endforeach; ?>

function checkStatusUpdates() {
    fetch('<?= $_SERVER['PHP_SELF'] ?>?poll=1')
        .then(r => r.json())
        .then(data => {
            data.forEach(item => {
                if (lastStatuses[item.id] !== undefined && lastStatuses[item.id] !== item.status_id) {
                    const labels = {1:'Pending',2:'Assigned',3:'Responding',4:'Resolved',5:'Cancelled'};
                    document.getElementById('notif-message').textContent =
                        'Report #' + item.id + ' status updated to: ' + (labels[item.status_id] || 'Unknown');
                    document.getElementById('fc-notif-toast').classList.add('show');
                    lastStatuses[item.id] = item.status_id;
                    setTimeout(() => document.getElementById('fc-notif-toast').classList.remove('show'), 6000);
                }
            });
        }).catch(() => {});
}
// Only poll if not on a form submission
<?php if (!$_POST): ?>
setInterval(checkStatusUpdates, 30000);
<?php endif; ?>
</script>

<?php
// Handle AJAX poll request
if (isset($_GET['poll'])) {
    $rows = $conn->query("SELECT id, status_id FROM incidents WHERE user_id=$uid ORDER BY created_at DESC LIMIT 20");
    $out = [];
    while ($r = $rows->fetch_assoc()) $out[] = $r;
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}
?>

<?php include '../includes/footer.php'; ?>