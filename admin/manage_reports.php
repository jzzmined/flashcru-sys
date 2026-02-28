<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = '';

$statusMap = [
    'pending'    => 1,
    'assigned'   => 2,
    'responding' => 3,
    'resolved'   => 4,
    'cancelled'  => 5
];
$statusByID = array_flip($statusMap);

// Handle status + team update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id        = (int)$_POST['update_id'];
    $status    = sanitize($_POST['status']);
    $team_id   = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;
    $t_sql     = $team_id ? $team_id : 'NULL';
    $status_id = $statusMap[$status] ?? 1;

    $conn->query("UPDATE incidents SET status_id=$status_id, assigned_team_id=$t_sql, updated_at=NOW() WHERE id=$id");
    logActivity($_SESSION['user_id'], "Updated incident #$id → status: $status");
    $msg = "Incident #$id updated successfully.";
}

$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$where  = ($filter && isset($statusMap[$filter])) ? "WHERE i.status_id = " . $statusMap[$filter] : '';

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay,
           u.full_name AS reporter, u.contact_number AS reporter_phone,
           t.team_name AS team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN users           u  ON i.user_id         = u.user_id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    $where
    ORDER BY i.created_at DESC
");
if (!$reports) die("Reports query failed: " . $conn->error);

$teams_res = $conn->query("SELECT team_id AS id, team_name AS name FROM teams WHERE status='active' ORDER BY team_name");
$teams_arr = [];
if ($teams_res) while ($t = $teams_res->fetch_assoc()) $teams_arr[] = $t;

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

            <!-- Filter Tabs -->
            <div class="fc-filter-tabs">
                <?php
                $tabs = ['' => 'All', 'pending' => 'Pending', 'assigned' => 'Assigned',
                         'responding' => 'Responding', 'resolved' => 'Resolved', 'cancelled' => 'Cancelled'];
                foreach ($tabs as $v => $l):
                ?>
                <a href="?status=<?= $v ?>" class="fc-filter-tab <?= $filter === $v ? 'active' : '' ?>">
                    <?= $l ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="fc-card">
                <?php if ($reports->num_rows === 0): ?>
                <div class="fc-empty"><i class="bi bi-inbox"></i><h6>No incidents found</h6></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>ID</th><th>Type</th><th>Reporter</th><th>Barangay</th>
                                <th>Team</th><th>Status</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $reports->fetch_assoc()): ?>
                            <tr>
                                <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                <td><span class="fc-pill"><?= htmlspecialchars($r['type_name']) ?></span></td>
                                <td>
                                    <div style="font-weight:500;"><?= htmlspecialchars($r['reporter']) ?></div>
                                    <?php if ($r['reporter_phone']): ?>
                                    <div style="font-size:11.5px;color:var(--fc-muted);"><?= htmlspecialchars($r['reporter_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['barangay']) ?></td>
                                <td>
                                    <?php if ($r['team_name']): ?>
                                    <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--fc-muted);font-size:12px;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($statusByID[$r['status_id']] ?? 'pending') ?></td>
                                <td style="color:var(--fc-muted);font-size:12px;white-space:nowrap;">
                                    <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                </td>
                                <td>
                                    <button class="fc-icon-btn" title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal<?= $r['id'] ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Description row -->
                            <?php if (!empty($r['description']) || !empty($r['location_detail'])): ?>
                            <tr style="background:#fafbff;">
                                <td colspan="8" style="padding:7px 18px 12px;color:var(--fc-muted);font-size:12.5px;border-top:none;">
                                    <?php if ($r['location_detail']): ?>
                                    <i class="bi bi-geo-alt-fill" style="color:var(--fc-success);"></i>
                                    <?= htmlspecialchars($r['location_detail']) ?> &nbsp;
                                    <?php endif; ?>
                                    <?php if ($r['description']): ?>
                                    <i class="bi bi-chat-text-fill" style="color:var(--fc-primary);"></i>
                                    <?= nl2br(htmlspecialchars($r['description'])) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="modal<?= $r['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-pencil-fill" style="color:var(--fc-primary);margin-right:8px;"></i>
                                                Update Incident #<?= $r['id'] ?>
                                            </h5>
                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?= $r['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="fc-form-label">Incident Type</label>
                                                    <input type="text" class="fc-form-control"
                                                           value="<?= htmlspecialchars($r['type_name']) ?>"
                                                           readonly style="background:#f7f9fc;">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="fc-form-label">Reporter</label>
                                                    <input type="text" class="fc-form-control"
                                                           value="<?= htmlspecialchars($r['reporter']) ?>"
                                                           readonly style="background:#f7f9fc;">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="fc-form-label">Assign Response Team</label>
                                                    <select name="team_id" class="fc-form-control">
                                                        <option value="">— Unassigned —</option>
                                                        <?php foreach ($teams_arr as $t): ?>
                                                        <option value="<?= $t['id'] ?>"
                                                            <?= $r['team_id'] == $t['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($t['name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="fc-form-label">Update Status</label>
                                                    <select name="status" class="fc-form-control">
                                                        <?php foreach (['pending','assigned','responding','resolved','cancelled'] as $s): ?>
                                                        <option value="<?= $s ?>" <?= ($statusMap[$s] ?? 0) === (int)$r['status_id'] ? 'selected' : '' ?>></option>
                                                            <?= ucfirst($s) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="fc-btn fc-btn-primary">
                                                    <i class="bi bi-save-fill"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>