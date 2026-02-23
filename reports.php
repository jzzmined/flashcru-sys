<?php
/**
 * FlashCru Emergency Response System
 * Reports & Analytics Page
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

// Stats for date range
$total       = $db->count('incidents', "DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$critical    = $db->count('incidents', "status='critical' AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$resolved    = $db->count('incidents', "status='resolved' AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);
$active      = $db->count('incidents', "status='active' AND DATE(created_at) BETWEEN :f AND :t", ['f'=>$date_from,'t'=>$date_to]);

// Incidents by type
$by_type = $db->fetchAll("
    SELECT incident_type, COUNT(*) as count 
    FROM incidents 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY incident_type 
    ORDER BY count DESC
", [$date_from, $date_to]);

// Incidents by status
$by_status = $db->fetchAll("
    SELECT status, COUNT(*) as count 
    FROM incidents 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status 
    ORDER BY count DESC
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

// Recent activity log
$activity = $db->fetchAll("
    SELECT al.*, u.full_name, i.title as incident_title
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    LEFT JOIN incidents i ON al.incident_id = i.incident_id
    ORDER BY al.created_at DESC
    LIMIT 15
");

// Average response time
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
    <title><?php echo $page_title; ?> - FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
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
                    <h2 style="font-size:22px;font-weight:800;">📊 Reports & Analytics</h2>
                    <p class="text-muted" style="font-size:13px;">View system performance and statistics</p>
                </div>
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print Report</button>
            </div>

            <!-- Date Filter -->
            <div class="panel mb-20" style="padding:16px 20px;">
                <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <label style="font-size:12px;color:#9CA3AF;display:block;margin-bottom:4px;">Date From</label>
                        <input type="date" name="date_from" class="form-control"
                               value="<?php echo $date_from; ?>" style="max-width:160px;">
                    </div>
                    <div>
                        <label style="font-size:12px;color:#9CA3AF;display:block;margin-bottom:4px;">Date To</label>
                        <input type="date" name="date_to" class="form-control"
                               value="<?php echo $date_to; ?>" style="max-width:160px;">
                    </div>
                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary btn-sm">Generate</button>
                        <a href="reports.php" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                    <div style="margin-left:auto;margin-top:20px;font-size:12px;color:#6B7280;">
                        Showing: <?php echo date('M j, Y', strtotime($date_from)); ?> 
                        → <?php echo date('M j, Y', strtotime($date_to)); ?>
                    </div>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon cyan">📋</div>
                    <div class="stat-info"><h3>Total</h3><div class="stat-value cyan"><?php echo $total; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🚨</div>
                    <div class="stat-info"><h3>Critical</h3><div class="stat-value red"><?php echo $critical; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">⚡</div>
                    <div class="stat-info"><h3>Active</h3><div class="stat-value blue"><?php echo $active; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info"><h3>Resolved</h3><div class="stat-value green"><?php echo $resolved; ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">⏱️</div>
                    <div class="stat-info"><h3>Avg Response</h3><div class="stat-value yellow"><?php echo $avg_minutes; ?>m</div></div>
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
                        $type_icons = ['fire'=>'🔥','medical'=>'🚑','accident'=>'🚗','rescue'=>'🚒','other'=>'⚠️'];
                        $type_colors = ['fire'=>'#EF4444','medical'=>'#22C55E','accident'=>'#3B82F6','rescue'=>'#F97316','other'=>'#8B5CF6'];
                        foreach ($by_type as $row):
                            $pct = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
                            $color = $type_colors[$row['incident_type']] ?? '#6B7280';
                            $icon  = $type_icons[$row['incident_type']] ?? '⚠️';
                        ?>
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="font-size:13px;"><?php echo $icon; ?> <?php echo ucfirst($row['incident_type']); ?></span>
                                <span style="font-size:13px;font-weight:600;color:<?php echo $color; ?>">
                                    <?php echo $row['count']; ?> (<?php echo $pct; ?>%)
                                </span>
                            </div>
                            <div style="height:8px;background:#1E293B;border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;transition:width 1s;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_type)): ?>
                            <p class="text-muted text-center">No data for this period</p>
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
                        $status_colors = ['critical'=>'#EF4444','active'=>'#3B82F6','pending'=>'#FACC15','resolved'=>'#22C55E'];
                        $status_icons  = ['critical'=>'🚨','active'=>'⚡','pending'=>'⏳','resolved'=>'✅'];
                        foreach ($by_status as $row):
                            $pct   = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
                            $color = $status_colors[$row['status']] ?? '#6B7280';
                            $icon  = $status_icons[$row['status']] ?? '•';
                        ?>
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="font-size:13px;"><?php echo $icon; ?> <?php echo ucfirst($row['status']); ?></span>
                                <span style="font-size:13px;font-weight:600;color:<?php echo $color; ?>">
                                    <?php echo $row['count']; ?> (<?php echo $pct; ?>%)
                                </span>
                            </div>
                            <div style="height:8px;background:#1E293B;border-radius:4px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($by_status)): ?>
                            <p class="text-muted text-center">No data for this period</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Team Performance -->
            <div class="panel mb-20">
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
                                    ? round(($team['resolved_count'] / $team['total_assigned']) * 100)
                                    : 0;
                                $type_icons = ['fire'=>'🔥','medical'=>'🚑','police'=>'🚔','rescue'=>'🚒'];
                                $icon = $type_icons[$team['team_type']] ?? '👥';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                <td><?php echo $icon; ?> <?php echo ucfirst($team['team_type']); ?></td>
                                <td><span class="badge badge-<?php echo $team['status']; ?>"><?php echo ucfirst($team['status']); ?></span></td>
                                <td><?php echo $team['total_assigned']; ?></td>
                                <td><?php echo $team['resolved_count']; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;height:6px;background:#1E293B;border-radius:3px;">
                                            <div style="height:100%;width:<?php echo $rate; ?>%;background:#22C55E;border-radius:3px;"></div>
                                        </div>
                                        <span style="font-size:12px;font-weight:600;color:#22C55E;"><?php echo $rate; ?>%</span>
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
                            <?php foreach ($activity as $log): ?>
                            <tr>
                                <td class="text-muted">#<?php echo $log['active_log_id']; ?></td>
                                <td>
                                    <?php
                                    $action_colors = [
                                        'incident_created' => '#22C55E',
                                        'incident_resolved' => '#3B82F6',
                                        'team_assigned' => '#F97316',
                                        'status_updated' => '#FACC15',
                                        'login' => '#8B5CF6',
                                        'logout' => '#6B7280'
                                    ];
                                    $color = $action_colors[$log['action']] ?? '#9CA3AF';
                                    ?>
                                    <span style="color:<?php echo $color; ?>;font-weight:600;font-size:12px;">
                                        <?php echo str_replace('_', ' ', strtoupper($log['action'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($log['incident_title'] ?? '—'); ?></td>
                                <td class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                                <td class="text-muted" style="font-size:12px;">
                                    <?php echo date('M j, g:i A', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>