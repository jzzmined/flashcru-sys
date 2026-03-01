<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid = (int) $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id     = (int) $_POST['edit_id'];
    $description = sanitize($_POST['description'] ?? '');
    $landmark    = sanitize($_POST['street_landmark'] ?? '');

    // Only allow edit if still pending (status_id = 1) and belongs to user
    $check = $conn->query("SELECT id FROM incidents WHERE id=$edit_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET description='$description', street_landmark='$landmark', updated_at=NOW() WHERE id=$edit_id");
        $msg = "Report #$edit_id updated successfully.";
    } else {
        $msg = "You can only edit pending reports.";
        $msg_type = 'error';
    }
}

// Handle Cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int) $_POST['cancel_id'];

    // Only allow cancel if still pending and belongs to user
    $check = $conn->query("SELECT id FROM incidents WHERE id=$cancel_id AND user_id=$uid AND status_id=1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE incidents SET status_id=5, updated_at=NOW() WHERE id=$cancel_id");
        $msg = "Report #$cancel_id has been cancelled.";
    } else {
        $msg = "You can only cancel pending reports.";
        $msg_type = 'error';
    }
}

$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$status_map = ['pending' => 1, 'dispatched' => 2, 'ongoing' => 3, 'resolved' => 4, 'cancelled' => 5];
$status_filter = isset($status_map[$filter]) ? " AND i.status_id = " . $status_map[$filter] : '';

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, t.team_name AS team_name,
           b.name AS barangay, rs.name AS status_name, rs.color AS status_color
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    LEFT JOIN barangays       b  ON i.barangay_id = b.id
    LEFT JOIN report_status   rs ON i.status_id = rs.id
    WHERE i.user_id = $uid $status_filter
    ORDER BY i.created_at DESC
");
if (!$reports)
    die("Query failed: " . $conn->error);
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
                    <div class="fc-breadcrumb">Recent Reports</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">

            <?php if ($msg): ?>
                <div class="fc-alert fc-alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>">
                    <i class="bi bi-<?= $msg_type === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <!-- Filter + New Report -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
                <div class="fc-filter-tabs" style="margin-bottom:0;">
                    <?php
                    $tabs = [
                        '' => 'All', 'pending' => 'Pending', 'dispatched' => 'Dispatched',
                        'ongoing' => 'Ongoing', 'resolved' => 'Resolved', 'cancelled' => 'Cancelled'
                    ];
                    foreach ($tabs as $val => $label): ?>
                        <a href="?status=<?= $val ?>" class="fc-filter-tab <?= $filter === $val ? 'active' : '' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="report_incident.php" class="fc-btn fc-btn-primary" style="font-size:13px;padding:10px 20px;">
                    <i class="bi bi-plus-circle-fill"></i> New Report
                </a>
            </div>

            <div class="fc-card">
                <?php if ($reports->num_rows === 0): ?>
                    <div class="fc-empty">
                        <i class="bi bi-file-earmark-x"></i>
                        <h6>No Reports Found</h6>
                        <p style="font-size:13px;">
                            <?= $filter ? "No reports with status \"$filter\"." : "You have not submitted any incident reports yet." ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="fc-table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Type</th><th>Barangay</th><th>Location</th>
                                    <th>Team</th><th>Status</th><th>Date</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $reports->fetch_assoc()): ?>
                                    <tr id="report-<?= $r['id'] ?>">
                                        <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                        <td><span class="fc-pill"><?= htmlspecialchars($r['type_name']) ?></span></td>
                                        <td><?= htmlspecialchars($r['barangay'] ?? 'N/A') ?></td>
                                        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            <?= htmlspecialchars($r['street_landmark'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <?php if ($r['team_name']): ?>
                                                <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--fc-muted);font-size:12px;">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="background:<?= $r['status_color'] ?>22;color:<?= $r['status_color'] ?>;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;">
                                                <?= htmlspecialchars($r['status_name']) ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--fc-muted);font-size:12.5px;white-space:nowrap;">
                                            <?= date('M d, Y g:i A', strtotime($r['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ((int)$r['status_id'] === 1): ?>
                                                <!-- Edit button — only for Pending -->
                                                <button class="fc-icon-btn" title="Edit Report"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?= $r['id'] ?>">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <!-- Cancel button — only for Pending -->
                                                <button class="fc-icon-btn" title="Cancel Report"
                                                    style="color:var(--fc-danger);"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelModal<?= $r['id'] ?>">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="color:var(--fc-muted);font-size:11px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Description row -->
                                    <?php if (!empty($r['description'])): ?>
                                        <tr style="background:#fafbff;">
                                            <td colspan="8" style="padding:8px 18px 13px;color:var(--fc-muted);font-size:12.5px;border-top:none;">
                                                <i class="bi bi-chat-text-fill" style="color:var(--fc-primary);margin-right:6px;"></i>
                                                <?= nl2br(htmlspecialchars($r['description'])) ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="bi bi-pencil-fill" style="color:var(--fc-primary);margin-right:8px;"></i>
                                                        Edit Report #<?= $r['id'] ?>
                                                    </h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_id" value="<?= $r['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="fc-form-label">Incident Type</label>
                                                            <input type="text" class="fc-form-control"
                                                                value="<?= htmlspecialchars($r['type_name']) ?>"
                                                                readonly style="background:#f7f9fc;">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fc-form-label">Barangay</label>
                                                            <input type="text" class="fc-form-control"
                                                                value="<?= htmlspecialchars($r['barangay'] ?? '') ?>"
                                                                readonly style="background:#f7f9fc;">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fc-form-label">Location / Landmark</label>
                                                            <input type="text" name="street_landmark" class="fc-form-control"
                                                                placeholder="e.g. Near the market"
                                                                value="<?= htmlspecialchars($r['street_landmark'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="fc-form-label">Description</label>
                                                            <textarea name="description" class="fc-form-control" rows="4"
                                                                placeholder="Describe the incident..."><?= htmlspecialchars($r['description'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="fc-btn"
                                                            style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="fc-btn fc-btn-primary">
                                                            <i class="bi bi-save-fill"></i> Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Confirmation Modal -->
                                    <div class="modal fade" id="cancelModal<?= $r['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="bi bi-exclamation-triangle-fill" style="color:var(--fc-danger);margin-right:8px;"></i>
                                                        Cancel Report
                                                    </h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body" style="font-size:13.5px;">
                                                    Are you sure you want to cancel <strong>Report #<?= $r['id'] ?></strong>? This cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="fc-btn"
                                                        style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);"
                                                        data-bs-dismiss="modal">No, Keep It</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                                                        <button type="submit" class="fc-btn"
                                                            style="background:var(--fc-danger);color:#fff;">
                                                            <i class="bi bi-x-circle-fill"></i> Yes, Cancel
                                                        </button>
                                                    </form>
                                                </div>
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