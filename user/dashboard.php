<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid     = (int)$_SESSION['user_id'];
$total   = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid")->fetch_assoc()['c'];
$pending = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=1")->fetch_assoc()['c'];
$active  = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=2")->fetch_assoc()['c'];
$resolved= (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=3")->fetch_assoc()['c'];

$recent  = $conn->query("
    SELECT i.*, it.type_name AS type_name, t.team_name AS team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.incident_type_id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    WHERE i.user_id = $uid
    ORDER BY i.created_at DESC
    LIMIT 6
");
if (!$recent) die("Recent query failed: " . $conn->error);

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$firstname = explode(' ', $_SESSION['name'])[0];
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <!-- TOPBAR -->
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcOpenSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="fc-page-title">Dashboard</div>
                    <div class="fc-breadcrumb">Home / Dashboard</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <div class="fc-notif-btn">
                    <i class="bi bi-bell"></i>
                    <?php if ($pending > 0): ?><span class="fc-notif-dot"></span><?php endif; ?>
                </div>
                <div class="fc-tb-user">
                    <div class="fc-user-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
                    <div>
                        <div class="fc-tb-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                        <div class="fc-tb-role">Community Member</div>
                    </div>
                </div>
            </div>
        </div><!-- /topbar -->

        <div class="fc-content">

            <!-- Welcome Banner -->
            <div class="fc-welcome">
                <div class="fc-welcome-title"><?= $greeting ?>, <?= htmlspecialchars($firstname) ?>!</div>
                <div class="fc-welcome-sub">Stay safe. Report incidents. Let FlashCru handle the rest.</div>
                <a href="report_incident.php" class="fc-btn fc-btn-primary" style="font-size:13px;padding:10px 22px;">
                    <i class="bi bi-plus-circle-fill"></i> Report Incident
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-red">
                        <div class="fc-stat-icon c-red"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="fc-stat-val" data-target="<?= $total ?>"><?= $total ?></div>
                        <div class="fc-stat-lbl">Total Reports</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-ylw">
                        <div class="fc-stat-icon c-ylw"><i class="bi bi-clock-fill"></i></div>
                        <div class="fc-stat-val"><?= $pending ?></div>
                        <div class="fc-stat-lbl">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-blu">
                        <div class="fc-stat-icon c-blu"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="fc-stat-val"><?= $active ?></div>
                        <div class="fc-stat-lbl">Active Response</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-grn">
                        <div class="fc-stat-icon c-grn"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="fc-stat-val"><?= $resolved ?></div>
                        <div class="fc-stat-lbl">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports Table -->
            <div class="fc-card">
                <div class="fc-card-header">
                    <div class="fc-card-title">
                        <i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> Recent Reports
                    </div>
                    <a href="my_reports.php" class="fc-btn fc-btn-primary" style="font-size:12px;padding:7px 16px;">
                        View All
                    </a>
                </div>
                <div>
                    <?php if ($recent->num_rows === 0): ?>
                    <div class="fc-empty">
                        <i class="bi bi-file-earmark-x"></i>
                        <h6>No Reports Yet</h6>
                        <p style="font-size:13px;">You haven't submitted any incident reports.</p>
                        <a href="report_incident.php" class="fc-btn fc-btn-primary" style="margin-top:8px;font-size:13px;">
                            Report Now
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="fc-table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Type</th>
                                    <th>Barangay</th>
                                    <th>Team Assigned</th>
                                    <th>Status</th>
                                    <th>Date Filed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $recent->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                    <td><span class="fc-pill"><i class="bi bi-tag-fill"></i><?= htmlspecialchars($r['type_name']) ?></span></td>
                                    <td><?= htmlspecialchars($r['barangay']) ?></td>
                                    <td>
                                        <?php if ($r['team_name']): ?>
                                            <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--fc-muted);font-size:12px;">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getStatusBadge($r['status']) ?></td>
                                    <td style="color:var(--fc-muted);font-size:12.5px;white-space:nowrap;">
                                        <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /fc-content -->
    </div><!-- /fc-main -->
</div><!-- /fc-app -->

<?php include '../includes/footer.php'; ?>