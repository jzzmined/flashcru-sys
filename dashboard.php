<?php
/**
 * FlashCru Emergency Response System
 * Dashboard — v4.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Dashboard';
$db = new Database();

// ── Stats ─────────────────────────────────────────────────────
$total     = $db->fetchOne("SELECT COUNT(*) AS c FROM incidents")['c'] ?? 0;
$pending   = $db->fetchOne("SELECT COUNT(*) AS c FROM incidents i JOIN report_status rs ON i.status_id = rs.id WHERE rs.name = 'Pending'")['c'] ?? 0;
$ongoing   = $db->fetchOne("SELECT COUNT(*) AS c FROM incidents i JOIN report_status rs ON i.status_id = rs.id WHERE rs.name IN ('Dispatched','Ongoing')")['c'] ?? 0;
$resolved  = $db->fetchOne("SELECT COUNT(*) AS c FROM incidents i JOIN report_status rs ON i.status_id = rs.id WHERE rs.name = 'Resolved'")['c'] ?? 0;
$today     = $db->fetchOne("SELECT COUNT(*) AS c FROM incidents WHERE DATE(created_at) = CURDATE()")['c'] ?? 0;
$teams_avail = $db->fetchOne("SELECT COUNT(*) AS c FROM teams WHERE status = 'available'")['c'] ?? 0;

// ── Recent Incidents ──────────────────────────────────────────
$recent = $db->fetchAll("
    SELECT i.*, it.name AS type_name, it.icon AS type_icon, it.color AS type_color,
           b.name AS barangay_name, rs.name AS status_name, rs.color AS status_color, t.team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays b       ON i.barangay_id = b.id
    LEFT JOIN report_status rs  ON i.status_id = rs.id
    LEFT JOIN teams t           ON i.assigned_team_id = t.team_id
    ORDER BY i.created_at DESC
    LIMIT 8
");

// ── Incident Type Breakdown ───────────────────────────────────
$type_stats = $db->fetchAll("
    SELECT it.name, it.icon, it.color, COUNT(i.id) AS cnt
    FROM incident_types it
    LEFT JOIN incidents i ON i.incident_type_id = it.id
    GROUP BY it.id
    ORDER BY cnt DESC
    LIMIT 5
");

// ── Team Status ───────────────────────────────────────────────
$team_list = $db->fetchAll("
    SELECT t.team_name, t.team_type, t.status,
           COUNT(DISTINCT tm.team_mem_id) AS members
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    GROUP BY t.team_id
    ORDER BY t.status, t.team_name
    LIMIT 6
");

// ── Recent Activity Log ───────────────────────────────────────
$activity = $db->fetchAll("
    SELECT al.*, u.full_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="fcMainContent">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <!-- Welcome Strip -->
            <div style="background:linear-gradient(135deg,#FD5E53 0%,#E04840 60%,#C0392B 100%);border-radius:var(--radius-lg);padding:22px 28px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 8px 28px rgba(253,94,83,0.32);position:relative;overflow:hidden;">
                <div style="position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:rgba(255,255,255,0.06);border-radius:50%;"></div>
                <div style="position:absolute;right:60px;bottom:-60px;width:160px;height:160px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
                <div style="position:relative;">
                    <div style="font-family:'Lexend',sans-serif;font-size:19px;font-weight:800;color:#fff;letter-spacing:-0.3px;">
                        Good <?php echo (date('H') < 12) ? 'Morning' : ((date('H') < 17) ? 'Afternoon' : 'Evening'); ?>, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Admin')[0]); ?> 👋
                    </div>
                    <div style="font-size:13px;color:rgba(255,255,255,0.72);margin-top:4px;">
                        Here's the current emergency response overview — <?php echo date('l, F j, Y'); ?>
                    </div>
                </div>
                <div style="display:flex;gap:20px;position:relative;">
                    <div style="text-align:center;">
                        <div style="font-family:'Lexend',sans-serif;font-size:28px;font-weight:900;color:#fff;"><?php echo $today; ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.65);font-weight:500;">Today's Reports</div>
                    </div>
                    <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
                    <div style="text-align:center;">
                        <div style="font-family:'Lexend',sans-serif;font-size:28px;font-weight:900;color:#B0EACD;"><?php echo $teams_avail; ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.65);font-weight:500;">Teams Available</div>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="cards-grid cards-grid-4" style="margin-bottom:28px;">
                <div class="stat-card total">
                    <div class="stat-icon red">📋</div>
                    <div class="stat-info">
                        <div class="card-label">Total Reports</div>
                        <div class="stat-value red"><?php echo $total; ?></div>
                        <div class="stat-delta">All time</div>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon orange">⏳</div>
                    <div class="stat-info">
                        <div class="card-label">Pending</div>
                        <div class="stat-value orange"><?php echo $pending; ?></div>
                        <div class="stat-delta">Awaiting dispatch</div>
                    </div>
                </div>
                <div class="stat-card ongoing">
                    <div class="stat-icon purple">⚡</div>
                    <div class="stat-info">
                        <div class="card-label">Active / Ongoing</div>
                        <div class="stat-value purple"><?php echo $ongoing; ?></div>
                        <div class="stat-delta">In progress</div>
                    </div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="card-label">Resolved</div>
                        <div class="stat-value green"><?php echo $resolved; ?></div>
                        <div class="stat-delta">Completed</div>
                    </div>
                </div>
            </div>

            <!-- Main grid: Recent Incidents + Sidebar panels -->
            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

                <!-- Recent Incidents -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">🚨 Recent Incidents</h3>
                        <a href="incidents.php" class="btn btn-secondary btn-sm">View All →</a>
                    </div>
                    <?php if (empty($recent)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-title">No incidents recorded</div>
                        <div class="empty-state-desc">Incidents will appear here when reported.</div>
                    </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Team</th>
                                    <th>Status</th>
                                    <th>Filed</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recent as $r):
                                $sc = $r['status_color'] ?? '#64748B';
                                $sn = $r['status_name']  ?? 'Unknown';
                                $tc = $r['type_color']   ?? '#FD5E53';
                                // Map status to CSS class
                                $sclass = strtolower(str_replace(' ', '-', $sn));
                            ?>
                                <tr>
                                    <td><span class="incident-id">#<?php echo $r['id']; ?></span></td>
                                    <td>
                                        <span class="type-badge" style="background:<?php echo $tc; ?>15;color:<?php echo $tc; ?>;border:1px solid <?php echo $tc; ?>30;">
                                            <?php echo htmlspecialchars($r['type_icon'] ?? ''); ?> <?php echo htmlspecialchars($r['type_name'] ?? '—'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:12.5px;"><?php echo htmlspecialchars($r['barangay_name'] ?? '—'); ?></div>
                                        <?php if (!empty($r['street_landmark'])): ?>
                                        <div class="incident-addr">📍 <?php echo htmlspecialchars($r['street_landmark']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['team_name'])): ?>
                                            <span class="team-text"><?php echo htmlspecialchars($r['team_name']); ?></span>
                                        <?php else: ?>
                                            <span class="team-unassigned">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-<?php echo $sclass; ?>">
                                            <?php echo htmlspecialchars($sn); ?>
                                        </span>
                                    </td>
                                    <td style="font-size:11px;color:var(--muted);font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                                        <?php echo date('M j', strtotime($r['created_at'])); ?><br>
                                        <span style="color:var(--subtle);"><?php echo date('g:i A', strtotime($r['created_at'])); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right column -->
                <div style="display:flex;flex-direction:column;gap:18px;">

                    <!-- Type Breakdown -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">📊 By Incident Type</h3>
                        </div>
                        <div class="panel-body" style="padding:14px 18px;">
                            <?php if (empty($type_stats)): ?>
                            <div style="text-align:center;color:var(--subtle);font-size:13px;padding:20px 0;">No data yet</div>
                            <?php else: ?>
                            <?php
                            $max_cnt = max(array_column($type_stats, 'cnt')) ?: 1;
                            foreach ($type_stats as $ts):
                                $pct = round(($ts['cnt'] / $max_cnt) * 100);
                                $color = $ts['color'] ?? '#FD5E53';
                            ?>
                            <div style="margin-bottom:12px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                    <span style="font-size:12.5px;font-weight:600;color:var(--ink);">
                                        <?php echo htmlspecialchars($ts['icon'] ?? ''); ?> <?php echo htmlspecialchars($ts['name']); ?>
                                    </span>
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:<?php echo $color; ?>;"><?php echo $ts['cnt']; ?></span>
                                </div>
                                <div style="height:6px;background:var(--bg);border-radius:99px;overflow:hidden;">
                                    <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:99px;transition:width 0.6s ease;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Team Status -->
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">👥 Team Status</h3>
                            <a href="teams.php" class="btn btn-secondary btn-xs">Manage</a>
                        </div>
                        <div style="padding:8px 0;">
                            <?php if (empty($team_list)): ?>
                            <div style="text-align:center;color:var(--subtle);font-size:13px;padding:20px;">No teams found</div>
                            <?php else: ?>
                            <?php
                            $type_icons = ['fire'=>'🔥','medical'=>'🚑','police'=>'🚔','rescue'=>'🚒'];
                            foreach ($team_list as $t):
                                $icon = $type_icons[$t['team_type']] ?? '👥';
                                $sbadge = match($t['status']) {
                                    'available' => 'badge-available',
                                    'busy'      => 'badge-busy',
                                    default     => 'badge-offline',
                                };
                            ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid var(--bg);">
                                <span style="font-size:18px;width:24px;text-align:center;"><?php echo $icon; ?></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:12.5px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($t['team_name']); ?></div>
                                    <div style="font-size:11px;color:var(--subtle);"><?php echo $t['members']; ?> members</div>
                                </div>
                                <span class="<?php echo $sbadge; ?>"><?php echo ucfirst($t['status']); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Activity Log -->
                    <?php if (!empty($activity)): ?>
                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title">🕐 Recent Activity</h3>
                        </div>
                        <div class="panel-body" style="padding:10px 18px;">
                            <div class="activity-feed">
                                <?php foreach ($activity as $act): ?>
                                <div class="activity-item">
                                    <div class="activity-dot" style="background:var(--green-bg);">🔔</div>
                                    <div class="activity-body">
                                        <div class="activity-title"><?php echo htmlspecialchars($act['action'] ?? 'Activity'); ?></div>
                                        <div class="activity-meta">
                                            <?php echo htmlspecialchars($act['full_name'] ?? 'System'); ?> ·
                                            <?php echo date('M j, g:i A', strtotime($act['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>