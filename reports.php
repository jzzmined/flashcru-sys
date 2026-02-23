<?php
/**
 * FlashCru Emergency Response System
 * Reports & Analytics — Red/White/Blue Theme v3.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Reports';
$db = new Database();

// Date filters
$date_from = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$date_to   = sanitize($_GET['date_to']   ?? date('Y-m-d'));

// Stats
$total    = $db->count('incidents', "DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$critical = $db->count('incidents', "status='critical' AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$resolved = $db->count('incidents', "status='resolved' AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$active   = $db->count('incidents', "status='active'   AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);

// By type
$by_type = $db->fetchAll("
    SELECT incident_type, COUNT(*) as count
    FROM incidents
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY incident_type ORDER BY count DESC
", [$date_from, $date_to]);

// By status
$by_status = $db->fetchAll("
    SELECT status, COUNT(*) as count
    FROM incidents
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status ORDER BY count DESC
", [$date_from, $date_to]);

// Team performance
$team_performance = $db->fetchAll("
    SELECT t.team_name, t.team_type, t.status,
           COUNT(i.incident_id) as total_assigned,
           SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM teams t
    LEFT JOIN incidents i ON t.team_id = i.assigned_team
    GROUP BY t.team_id
    ORDER BY total_assigned DESC
");

// Activity log
$activity = $db->fetchAll("
    SELECT al.*, u.full_name, i.title as incident_title
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    LEFT JOIN incidents i ON al.incident_id = i.incident_id
    ORDER BY al.created_at DESC
    LIMIT 15
");

// Avg response
$avg_response = $db->fetchOne("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes
    FROM incidents
    WHERE status = 'resolved' AND resolved_at IS NOT NULL
      AND DATE(created_at) BETWEEN ? AND ?
", [$date_from, $date_to]);

$avg_minutes = round($avg_response['avg_minutes'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        @media print {
            .sidebar, header, .btn { display:none !important; }
            .main-content { margin-left:0 !important; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <!-- Page Header -->
            <div class="flex-between mb-20">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--navy);">📊 Reports &amp; Analytics</h2>
                    <p class="text-muted" style="font-size:13px;margin-top:3px;">View system performance and statistics</p>
                </div>
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print Report</button>
            </div>

            <!-- Date Filter -->
            <div class="panel mb-24" style="padding:16px 20px;">
                <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <label style="font-size:12px;color:var(--gray-400);display:block;margin-bottom:4px;font-weight:600;">From</label>
                        <input type="date" name="date_from" class="form-control"
                               value="<?php echo $date_from; ?>" style="max-width:160px;">
                    </div>
                    <div>
                        <label style="font-size:12px;color:var(--gray-400);display:block;margin-bottom:4px;font-weight:600;">To</label>
                        <input type="date" name="date_to" class="form-control"
                               value="<?php echo $date_to; ?>" style="max-width:160px;">
                    </div>
                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary btn-sm">Generate</button>
                        <a href="reports.php" class="btn btn-secondary btn-sm" style="margin-left:8px;">Reset</a>
                    </div>
                    <div style="margin-left:auto;margin-top:20px;font-size:12px;color:var(--gray-400);">
                        Showing: <strong><?php echo date('M j, Y', strtotime($date_from)); ?></strong>
                        → <strong><?php echo date('M j, Y', strtotime($date_to)); ?></strong>
                    </div>
                </form>
            </div>

            <!-- Summary KPIs — 5 columns -->
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;">

                <div class="stat-card total" style="display:flex;align-items:center;gap:14px;">
                    <div class="stat-icon cyan">📋</div>
                    <div class="stat-info">
                        <h3 class="card-label">Total</h3>
                        <div class="stat-value cyan"><?php echo $total; ?></div>
                    </div>
                </div>

                <div class="stat-card critical" style="display:flex;align-items:center;gap:14px;">
                    <div class="stat-icon red">🚨</div>
                    <div class="stat-info">
                        <h3 class="card-label">Critical</h3>
                        <div class="stat-value red"><?php echo $critical; ?></div>
                    </div>
                </div>

                <div class="stat-card active-c" style="display:flex;align-items:center;gap:14px;">
                    <div class="stat-icon amber">⚡</div>
                    <div class="stat-info">
                        <h3 class="card-label">Active</h3>
                        <div class="stat-value amber"><?php echo $active; ?></div>
                    </div>
                </div>

                <div class="stat-card resolved" style="display:flex;align-items:center;gap:14px;">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <h3 class="card-label">Resolved</h3>
                        <div class="stat-value green"><?php echo $resolved; ?></div>
                    </div>
                </div>

                <div class="stat-card today" style="display:flex;align-items:center;gap:14px;">
                    <div class="stat-icon amber" style="background:#FEF3C7;">⏱️</div>
                    <div class="stat-info">
                        <h3 class="card-label">Avg Response</h3>
                        <div class="stat-value amber"><?php echo $avg_minutes; ?>m</div>
                    </div>
                </div>

            </div>

            <!-- Charts Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

                <!-- By Type -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">📊 Incidents by Type</h3>
                    </div>
                    <div class="panel-body">
                        <?php
                        $type_icons   = ['fire'=>'🔥','medical'=>'🚑','accident'=>'🚗','rescue'=>'🚒','other'=>'⚠️'];
                        $type_colors  = [
                            'fire'     => 'var(--orange)',
                            'medical'  => 'var(--blue-500)',
                            'accident' => 'var(--amber)',
                            'rescue'   => 'var(--purple)',
                            'other'    => 'var(--gray-400)',
                        ];
                        $type_tracks  = [
                            'fire'     => '#FFF7ED',
                            'medical'  => 'var(--blue-100)',
                            'accident' => 'var(--amber-light)',
                            'rescue'   => 'var(--purple-light)',
                            'other'    => 'var(--gray-100)',
                        ];
                        foreach ($by_type as $row):
                            $pct   = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
                            $color = $type_colors[$row['incident_type']] ?? 'var(--gray-400)';
                            $track = $type_tracks[$row['incident_type']] ?? 'var(--gray-100)';
                            $icon  = $type_icons[$row['incident_type']] ?? '⚠️';
                        ?>
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:13px;">
                                <span><?php echo $icon; ?> <?php echo ucfirst($row['incident_type']); ?></span>
                                <span style="font-weight:700;color:<?php echo $color; ?>;">
                                    <?php echo $row['count']; ?> (<?php echo $pct; ?>%)
                                </span>
                            </div>
                            <div style="height:8px;background:<?php echo $track; ?>;border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;transition:width 0.8s ease;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_type)): ?>
                        <div class="empty-state">No data for this period</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- By Status -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">📈 Incidents by Status</h3>
                    </div>
                    <div class="panel-body">
                        <?php
                        $status_colors = [
                            'critical' => 'var(--red-600)',
                            'active'   => 'var(--blue-500)',
                            'pending'  => 'var(--amber)',
                            'resolved' => 'var(--green)',
                        ];
                        $status_tracks = [
                            'critical' => 'var(--red-100)',
                            'active'   => 'var(--blue-100)',
                            'pending'  => 'var(--amber-light)',
                            'resolved' => 'var(--green-light)',
                        ];
                        $status_icons  = ['critical'=>'🚨','active'=>'⚡','pending'=>'⏳','resolved'=>'✅'];
                        foreach ($by_status as $row):
                            $pct   = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
                            $color = $status_colors[$row['status']] ?? 'var(--gray-400)';
                            $track = $status_tracks[$row['status']] ?? 'var(--gray-100)';
                            $icon  = $status_icons[$row['status']] ?? '•';
                        ?>
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:13px;">
                                <span><?php echo $icon; ?> <?php echo ucfirst($row['status']); ?></span>
                                <span style="font-weight:700;color:<?php echo $color; ?>;">
                                    <?php echo $row['count']; ?> (<?php echo $pct; ?>%)
                                </span>
                            </div>
                            <div style="height:8px;background:<?php echo $track; ?>;border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;transition:width 0.8s ease;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_status)): ?>
                        <div class="empty-state">No data for this period</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /charts row -->

            <!-- Team Performance -->
            <div class="panel mb-24">
                <div class="panel-header">
                    <h3 class="panel-title">🚒 Team Performance</h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Total Assigned</th>
                                <th>Resolved</th>
                                <th>Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_performance as $team):
                                $rate = $team['total_assigned'] > 0
                                    ? round(($team['resolved_count'] / $team['total_assigned']) * 100) : 0;
                                $type_icons = ['fire'=>'🔥','medical'=>'🚑','police'=>'🚔','rescue'=>'🚒'];
                                $icon = $type_icons[$team['team_type']] ?? '👥';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                <td><?php echo $icon; ?> <?php echo ucfirst($team['team_type']); ?></td>
                                <td><span class="badge badge-<?php echo $team['status']; ?>"><?php echo ucfirst($team['status']); ?></span></td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;"><?php echo (int)$team['total_assigned']; ?></td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--green);"><?php echo (int)$team['resolved_count']; ?></td>
                                <td style="min-width:160px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="flex:1;height:6px;background:var(--green-light);border-radius:3px;">
                                            <div style="height:100%;width:<?php echo $rate; ?>%;background:var(--green);border-radius:3px;transition:width 0.8s ease;"></div>
                                        </div>
                                        <span style="font-size:12px;font-weight:700;color:var(--green);min-width:36px;text-align:right;"><?php echo $rate; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">📝 Recent Activity Log</h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Incident</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activity)): ?>
                            <tr><td colspan="6"><div class="empty-state">No activity logged yet</div></td></tr>
                            <?php else: ?>
                            <?php
                            $action_colors = [
                                'incident_created'  => 'var(--green)',
                                'incident_resolved' => 'var(--blue-500)',
                                'incident_updated'  => 'var(--amber)',
                                'incident_deleted'  => 'var(--red-600)',
                                'team_assigned'     => 'var(--orange)',
                                'status_updated'    => 'var(--purple)',
                                'login'             => 'var(--cyan)',
                                'logout'            => 'var(--gray-400)',
                            ];
                            foreach ($activity as $log):
                                $color = $action_colors[$log['action']] ?? 'var(--gray-400)';
                            ?>
                            <tr>
                                <td class="text-muted">#<?php echo $log['active_log_id']; ?></td>
                                <td>
                                    <span style="color:<?php echo $color; ?>;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:0.04em;">
                                        <?php echo str_replace('_', ' ', $log['action']); ?>
                                    </span>
                                </td>
                                <td style="font-size:13px;"><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                                <td class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($log['incident_title'] ?? '—'); ?></td>
                                <td class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                                <td class="text-muted" style="font-size:12px;white-space:nowrap;">
                                    <?php echo date('M j, g:i A', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->
</body>
</html>