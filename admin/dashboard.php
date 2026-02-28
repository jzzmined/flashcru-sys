<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$total      = totalIncidents();
$pending    = countByStatus('pending');
// Query using the correct column 'status_id'
$active_res = $conn->query("SELECT COUNT(*) AS c FROM incidents WHERE status_id IN (2, 3)");
$active = ($active_res) ? (int)$active_res->fetch_assoc()['c'] : 0;
$resolved   = countByStatus('resolved');
$users      = totalUsers();
$teams      = totalTeams();

$recent = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay,
           u.full_name AS reporter, t.team_name AS team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN users           u  ON i.user_id         = u.user_id
    LEFT JOIN teams           t  ON i.assigned_team_id         = t.team_id
    ORDER BY i.created_at DESC
    LIMIT 8
");

if (!$recent) {
    die("Recent incidents query failed: " . $conn->error);
}

$log = $conn->query("
    SELECT al.*, u.full_name AS uname
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
");
if (!$log) die("Log query failed: " . $conn->error);
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <!-- TOPBAR -->
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Admin Dashboard</div>
                    <div class="fc-breadcrumb">FlashCru / Control Center</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">

            <!-- Hero Banner -->
            <div style="background:linear-gradient(135deg,#0d1a2e,#1a0a09);border-radius:var(--fc-radius);padding:26px 30px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;position:relative;overflow:hidden;">
                <div style="position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:90px;opacity:.06;font-family:Lexend,sans-serif;font-weight:800;color:#fff;">FC</div>
                <div style="position:relative;z-index:1;">
                    <div style="font-family:Lexend,sans-serif;font-weight:800;font-size:19px;color:#fff;margin-bottom:4px;">
                        FlashCru Control Center
                    </div>
                    <div style="color:rgba(255,255,255,.5);font-size:12.5px;">
                        <?= date('l, F j, Y \a\t g:i A') ?>
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;position:relative;z-index:1;">
                    <a href="manage_reports.php" class="fc-btn fc-btn-primary" style="font-size:13px;padding:10px 20px;">
                        <i class="bi bi-file-earmark-text-fill"></i> View Reports
                    </a>
                    <a href="manage_teams.php" class="fc-btn fc-btn-outline" style="font-size:13px;padding:10px 20px;">
                        <i class="bi bi-people-fill"></i> Teams
                    </a>
                </div>
            </div>

            <!-- Stat cards row 1 -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-red">
                        <div class="fc-stat-icon c-red"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="fc-stat-val"><?= $total ?></div>
                        <div class="fc-stat-lbl">Total Incidents</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card c-ylw">
                        <div class="fc-stat-icon c-ylw"><i class="bi bi-hourglass-split"></i></div>
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

            <!-- Stat cards row 2 -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="fc-stat-card" style="border-left:4px solid var(--fc-success);">
                        <div style="display:flex;align-items:center;gap:16px;">
                            <div class="fc-stat-icon c-grn"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="fc-stat-val"><?= $users ?></div>
                                <div class="fc-stat-lbl">Registered Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fc-stat-card" style="border-left:4px solid #5b7cf7;">
                        <div style="display:flex;align-items:center;gap:16px;">
                            <div class="fc-stat-icon c-blu"><i class="bi bi-shield-fill-check"></i></div>
                            <div>
                                <div class="fc-stat-val"><?= $teams ?></div>
                                <div class="fc-stat-lbl">Response Teams</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents + Activity Log -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> Latest Incidents
                            </div>
                            <a href="manage_reports.php" class="fc-btn fc-btn-primary" style="font-size:12px;padding:6px 14px;">
                                Manage All
                            </a>
                        </div>
                        <?php if (!$recent || $recent->num_rows === 0): ?>
                        <div class="fc-empty"><i class="bi bi-inbox"></i><h6>No Incidents Yet</h6></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="fc-table">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Type</th><th>Reporter</th>
                                        <th>Barangay</th><th>Team</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                        <td><span class="fc-pill"><?= htmlspecialchars($r['type_name']) ?></span></td>
                                        <td><?= htmlspecialchars($r['reporter']) ?></td>
                                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                                        <td>
                                            <?php if ($r['team_name']): ?>
                                                <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--fc-muted);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= getStatusBadge($r['status']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-activity" style="color:var(--fc-success)"></i> Activity Log
                            </div>
                        </div>
                        <div>
                            <?php while ($l = $log->fetch_assoc()): ?>
                            <div class="fc-log-item">
                                <div class="fc-log-dot"></div>
                                <div>
                                    <div class="fc-log-action"><?= htmlspecialchars($l['action']) ?></div>
                                    <div class="fc-log-meta">
                                        <?= htmlspecialchars($l['uname'] ?? 'System') ?>
                                        &bull; <?= date('M d, g:i A', strtotime($l['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>