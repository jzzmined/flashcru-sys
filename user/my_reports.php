<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid    = (int)$_SESSION['user_id'];
$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$where  = "WHERE i.user_id = $uid" . ($filter ? " AND i.status = '$filter'" : '');

$reports = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay, t.name AS team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN teams           t  ON i.team_id         = t.id
    $where
    ORDER BY i.created_at DESC
");
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
                    <div class="fc-breadcrumb">Dashboard / My Reports</div>
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

            <!-- Filter + New Report -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
                <div class="fc-filter-tabs" style="margin-bottom:0;">
                    <?php
                    $tabs = ['' => 'All', 'pending' => 'Pending', 'assigned' => 'Assigned',
                             'responding' => 'Responding', 'resolved' => 'Resolved', 'cancelled' => 'Cancelled'];
                    foreach ($tabs as $val => $label):
                    ?>
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
                                <th>ID</th>
                                <th>Type</th>
                                <th>Barangay</th>
                                <th>Location</th>
                                <th>Team</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $reports->fetch_assoc()): ?>
                            <tr id="report-<?= $r['id'] ?>">
                                <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                <td><span class="fc-pill"><?= htmlspecialchars($r['type_name']) ?></span></td>
                                <td><?= htmlspecialchars($r['barangay']) ?></td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($r['location_detail'] ?: '—') ?>
                                </td>
                                <td>
                                    <?php if ($r['team_name']): ?>
                                        <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--fc-muted);font-size:12px;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                                <td style="color:var(--fc-muted);font-size:12.5px;white-space:nowrap;">
                                    <?= date('M d, Y g:i A', strtotime($r['created_at'])) ?>
                                </td>
                            </tr>
                            <?php if (!empty($r['description'])): ?>
                            <tr style="background:#fafbff;">
                                <td colspan="7" style="padding:8px 18px 13px;color:var(--fc-muted);font-size:12.5px;border-top:none;">
                                    <i class="bi bi-chat-text-fill" style="color:var(--fc-primary);margin-right:6px;"></i>
                                    <?= nl2br(htmlspecialchars($r['description'])) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
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