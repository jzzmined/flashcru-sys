<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = '';
$statusMap   = ['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5];
$statusByID  = array_flip($statusMap);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id       = (int)$_POST['update_id'];
    $status   = sanitize($_POST['status']);
    $team_id  = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;
    $t_sql    = $team_id ? $team_id : 'NULL';
    $status_id = $statusMap[$status] ?? 1;
    $conn->query("UPDATE incidents SET status_id=$status_id, assigned_team_id=$t_sql, updated_at=NOW() WHERE id=$id");
    logActivity($_SESSION['user_id'], "Updated incident #$id → status: $status");
    $msg = "Incident #$id updated successfully.";
}

$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$where  = ($filter && isset($statusMap[$filter])) ? "WHERE i.status_id = ".$statusMap[$filter] : '';

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay,
           u.full_name AS reporter, u.contact_number AS reporter_phone,
           t.team_name, i.assigned_team_id
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN users           u  ON i.user_id         = u.user_id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    $where
    ORDER BY i.created_at DESC
");
if (!$reports) die("Reports query failed: ".$conn->error);

$teams_res = $conn->query("SELECT team_id AS id, team_name AS name FROM teams ORDER BY team_name");
$teams_arr = [];
if ($teams_res) while ($t = $teams_res->fetch_assoc()) $teams_arr[] = $t;

// Count per status for tabs
$cnt_res = $conn->query("SELECT status_id, COUNT(*) c FROM incidents GROUP BY status_id");
$counts  = [];
while ($c = $cnt_res->fetch_assoc()) $counts[$c['status_id']] = $c['c'];
$total = array_sum($counts);

// Status config
$statusCfg = [
    1 => ['label'=>'Pending',    'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.15)',  'icon'=>'bi-clock-fill'],
    2 => ['label'=>'Assigned',   'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.15)',  'icon'=>'bi-person-check-fill'],
    3 => ['label'=>'Responding', 'color'=>'#8b5cf6', 'bg'=>'rgba(139,92,246,.15)',  'icon'=>'bi-activity'],
    4 => ['label'=>'Resolved',   'color'=>'#21bf73', 'bg'=>'rgba(33,191,115,.15)',  'icon'=>'bi-patch-check-fill'],
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

// Collect rows + evidence
$all_rows = [];
while ($r = $reports->fetch_assoc()) $all_rows[] = $r;
$inc_ids = array_column($all_rows, 'id');
$evidence_map = [];
if (!empty($inc_ids)) {
    $ids_str = implode(',', $inc_ids);
    $ev_res  = $conn->query("SELECT * FROM incident_evidence WHERE incident_id IN ($ids_str) ORDER BY uploaded_at ASC");
    if ($ev_res) while ($ev = $ev_res->fetch_assoc()) $evidence_map[$ev['incident_id']][] = $ev;
}
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>
    <div class="fc-main">

        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()" style="display:block;"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Manage Reports</div>
                    <div class="fc-breadcrumb">Admin / Incident Reports</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
            <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div>
            <?php endif; ?>

            <!-- Filter tabs with counts -->
            <div class="fc-filter-tabs" style="margin-bottom:24px;">
                <a href="?status=" class="fc-filter-tab <?= $filter==='' ? 'active':'' ?>">
                    All <span class="fc-tab-count"><?= $total ?></span>
                </a>
                <?php foreach (['pending'=>1,'assigned'=>2,'responding'=>3,'resolved'=>4,'cancelled'=>5] as $val=>$sid): ?>
                <a href="?status=<?= $val ?>" class="fc-filter-tab <?= $filter===$val ? 'active':'' ?>">
                    <?= ucfirst($val) ?>
                    <?php if (!empty($counts[$sid])): ?>
                    <span class="fc-tab-count"><?= $counts[$sid] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($all_rows)): ?>
            <div class="fc-empty" style="padding:80px 28px;">
                <i class="bi bi-inbox"></i>
                <h6>No incidents found</h6>
            </div>
            <?php else: ?>

            <!-- Card Grid -->
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
                        <button class="fc-btn fc-btn-primary" style="font-size:12px;padding:7px 16px;" onclick="event.stopPropagation();openAdminPanel(<?= $r['id'] ?>)">
                            View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:22px;font-size:12.5px;color:var(--fc-muted);font-family:'Lexend',sans-serif;">
                Showing <?= count($all_rows) ?> incident<?= count($all_rows)!==1?'s':'' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Slide-in Panel Overlay ── -->
<div class="ir-panel-overlay" id="panelOverlay" onclick="closeAdminPanel()"></div>

<?php foreach ($all_rows as $r):
    $sid   = (int)$r['status_id'];
    $scfg  = $statusCfg[$sid] ?? $statusCfg[1];
    $ticon = getTypeIcon($r['type_name'] ?? '', $typeIcons);
    $evs   = $evidence_map[$r['id']] ?? [];
    $thumb = !empty($evs) ? $evs[0]['file_path'] : null;
?>
<div class="ir-panel" id="adminPanel<?= $r['id'] ?>">

    <!-- Panel image header -->
    <div class="ir-panel-img">
        <?php if ($thumb): ?>
            <img src="../<?= htmlspecialchars($thumb) ?>" alt="evidence">
        <?php else: ?>
            <div class="ir-modal-placeholder"><i class="bi <?= $ticon ?>"></i></div>
        <?php endif; ?>
        <div class="ir-modal-img-overlay"></div>
        <button class="ir-modal-close" onclick="closeAdminPanel()"><i class="bi bi-x"></i></button>
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
                <?= date('M d, Y · g:i A', strtotime($r['created_at'])) ?> · <?= htmlspecialchars($r['reporter'] ?? '') ?>
            </div>
        </div>
    </div>

    <div class="ir-panel-body">

        <!-- Details -->
        <div class="ir-section-label">Incident Details</div>
        <div class="ir-detail-row"><span>Type</span><strong><?= htmlspecialchars($r['type_name'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Barangay</span><strong><?= htmlspecialchars($r['barangay'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Location</span><strong><?= htmlspecialchars($r['street_landmark'] ?? '—') ?></strong></div>
        <div class="ir-detail-row"><span>Reporter</span>
            <div>
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

        <!-- Update form -->
        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--fc-border);">
            <div class="ir-section-label">Update Report</div>
            <form method="POST">
                <input type="hidden" name="update_id" value="<?= $r['id'] ?>">
                <div class="mb-3">
                    <label class="fc-form-label">Assign Response Team</label>
                    <select name="team_id" class="fc-form-control">
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
                    <select name="status" class="fc-form-control">
                        <?php foreach (['pending','assigned','responding','resolved','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($statusMap[$s]??0)===$sid ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="fc-btn" style="flex:1;justify-content:center;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);" onclick="closeAdminPanel()">
                        <i class="bi bi-x"></i> Cancel
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
</script>

<?php include '../includes/footer.php'; ?>