<?php
/**
 * FlashCru Emergency Response System
 * Dashboard — Red/White/Blue Theme v3.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Dashboard';
$db = new Database();

// ── KPI Counts ─────────────────────────────────────────────────
$counts = $db->fetchOne("
    SELECT
        SUM(status = 'critical') AS critical,
        SUM(status = 'active')   AS active,
        SUM(status = 'pending')  AS pending,
        SUM(status = 'resolved') AS resolved,
        COUNT(*)                 AS total
    FROM incidents
");

$today_count = $db->count('incidents', 'DATE(created_at) = CURDATE()');

// Teams
$teams_total     = $db->count('teams');
$teams_available = $db->count('teams', "status = 'available'");

// Recent incidents
$recent_incidents = $db->fetchAll("
    SELECT i.*, t.team_name
    FROM incidents i
    LEFT JOIN teams t ON i.assigned_team = t.team_id
    ORDER BY FIELD(i.status,'critical','active','pending','resolved'), i.created_at DESC
    LIMIT 10
");

// Teams with stats
$all_teams = $db->fetchAll("
    SELECT t.*,
        COUNT(DISTINCT tm.team_mem_id) AS member_count,
        SUM(CASE WHEN i.status IN ('active','critical') THEN 1 ELSE 0 END) AS on_scene,
        SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    LEFT JOIN incidents i ON t.team_id = i.assigned_team
    GROUP BY t.team_id
    ORDER BY t.status, t.team_name
");

// Greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$day_str  = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">

        <?php include 'includes/header.php'; ?>

        <!-- Tabs -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('overview', this)">📊 Overview</button>
            <button class="tab-btn"        onclick="switchTab('incidents', this)">🔴 Recent Incidents</button>
            <button class="tab-btn"        onclick="switchTab('map', this)">🗺️ Live Map</button>
            <button class="tab-btn"        onclick="switchTab('teams', this)">👥 Team Status</button>
        </div>

        <div class="page-content">

            <!-- ══ OVERVIEW ══════════════════════════════════════════ -->
            <div class="tab-panel active" id="tab-overview">

                <div class="greeting">
                    <h1>
                        <?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System'); ?>! ⚡
                    </h1>
                    <p>
                        Here's what's happening with FlashCru right now.
                        <span style="color:var(--red-600);font-weight:600;"><?php echo $day_str; ?></span>
                    </p>
                </div>

                <!-- 8-card KPI grid -->
                <div class="cards-grid cards-grid-4">

                    <div class="stat-card critical" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon red">🚨</div>
                        <div class="stat-info">
                            <h3 class="card-label">Critical</h3>
                            <div class="stat-value red"><?php echo (int)($counts['critical'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="stat-card active-c" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon amber">⚡</div>
                        <div class="stat-info">
                            <h3 class="card-label">Active</h3>
                            <div class="stat-value amber"><?php echo (int)($counts['active'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="stat-card pending" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon purple">⏳</div>
                        <div class="stat-info">
                            <h3 class="card-label">Pending</h3>
                            <div class="stat-value purple"><?php echo (int)($counts['pending'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="stat-card resolved" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon green">✅</div>
                        <div class="stat-info">
                            <h3 class="card-label">Resolved</h3>
                            <div class="stat-value green"><?php echo (int)($counts['resolved'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="stat-card teams" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon blue">🚒</div>
                        <div class="stat-info">
                            <h3 class="card-label">Teams Available</h3>
                            <div class="stat-value blue"><?php echo $teams_available; ?>/<?php echo $teams_total; ?></div>
                        </div>
                    </div>

                    <div class="stat-card total" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon slate">📋</div>
                        <div class="stat-info">
                            <h3 class="card-label">Total Incidents</h3>
                            <div class="stat-value" style="color:var(--navy);font-size:32px;font-weight:800;font-family:'JetBrains Mono',monospace;"><?php echo (int)($counts['total'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="stat-card today" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon cyan">📅</div>
                        <div class="stat-info">
                            <h3 class="card-label">Today</h3>
                            <div class="stat-value cyan"><?php echo $today_count; ?></div>
                        </div>
                    </div>

                    <div class="stat-card tteams" style="display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon purple">🏢</div>
                        <div class="stat-info">
                            <h3 class="card-label">Total Teams</h3>
                            <div class="stat-value tteams"><?php echo $teams_total; ?></div>
                        </div>
                    </div>

                </div><!-- /cards-grid -->

                <!-- Quick Nav -->
                <div class="quick-nav">
                    <div style="font-size:42px;margin-bottom:12px;opacity:0.6;">📡</div>
                    <h3>Quick Navigation</h3>
                    <p>Use the tabs above to view Recent Incidents, the Live Map, or Team Status.</p>
                    <div class="quick-nav-btns">
                        <button class="qbtn qbtn-red"    onclick="switchTab('incidents',document.querySelectorAll('.tab-btn')[1])">🔴 Recent Incidents</button>
                        <button class="qbtn qbtn-blue"   onclick="switchTab('map',document.querySelectorAll('.tab-btn')[2])">🗺️ Live Map</button>
                        <button class="qbtn qbtn-purple" onclick="switchTab('teams',document.querySelectorAll('.tab-btn')[3])">👥 Team Status</button>
                    </div>
                </div>

            </div><!-- /tab-overview -->


            <!-- ══ RECENT INCIDENTS ═══════════════════════════════ -->
            <div class="tab-panel" id="tab-incidents">

                <div class="section-header">
                    <div class="section-title">🔴 Recent Incidents</div>
                    <a href="incidents.php" class="view-all">View All →</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title &amp; Location</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned Team</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_incidents)): ?>
                            <tr><td colspan="6">
                                <div class="empty-state">No incidents found</div>
                            </td></tr>
                            <?php else: ?>
                            <?php
                            $type_icons = ['fire'=>'🔥','medical'=>'🚑','rescue'=>'🚒','accident'=>'🚗','other'=>'⚠️'];
                            $type_classes = ['fire'=>'fire','medical'=>'medical','rescue'=>'rescue','accident'=>'accident','other'=>'other'];
                            foreach ($recent_incidents as $inc):
                                $icon  = $type_icons[$inc['incident_type']] ?? '⚠️';
                                $tclass = $type_classes[$inc['incident_type']] ?? 'other';
                                $status = strtoupper($inc['status']);
                                $priority = strtoupper($inc['priority']);
                            ?>
                            <tr onclick="window.location='incidents.php?edit=<?php echo $inc['incident_id']; ?>'">
                                <td><span class="incident-id">#<?php echo $inc['incident_id']; ?></span></td>
                                <td>
                                    <div class="incident-title"><?php echo htmlspecialchars($inc['title']); ?></div>
                                    <div class="incident-addr">📍 <?php echo htmlspecialchars($inc['location']); ?></div>
                                </td>
                                <td>
                                    <span class="type-badge <?php echo $tclass; ?>"><?php echo $icon; ?> <?php echo ucfirst($inc['incident_type']); ?></span>
                                </td>
                                <td><span class="badge badge-<?php echo $inc['status']; ?>"><?php echo $status; ?></span></td>
                                <td><span class="priority-badge <?php echo $priority; ?>"><?php echo $priority; ?></span></td>
                                <td>
                                    <?php if ($inc['team_name']): ?>
                                        <span class="team-text"><?php echo htmlspecialchars($inc['team_name']); ?></span>
                                    <?php else: ?>
                                        <span class="team-unassigned">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div><!-- /tab-incidents -->


            <!-- ══ LIVE MAP ═══════════════════════════════════════ -->
            <div class="tab-panel" id="tab-map">

                <div class="section-header">
                    <div class="section-title">🗺️ Live Incident Map</div>
                    <div class="map-legend-bar">
                        <span><span class="map-legend-dot" style="background:var(--red-600);"></span>Critical</span>
                        <span><span class="map-legend-dot" style="background:var(--amber);"></span>Active</span>
                        <span><span class="map-legend-dot" style="background:var(--purple);"></span>Pending</span>
                        <span><span class="map-legend-dot" style="background:var(--green);"></span>Resolved</span>
                    </div>
                </div>

                <div class="map-wrapper">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d31478.12!2d125.6128!3d7.0731!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1"
                        allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>

            </div><!-- /tab-map -->


            <!-- ══ TEAM STATUS ════════════════════════════════════ -->
            <div class="tab-panel" id="tab-teams">

                <div class="section-header">
                    <div class="section-title">👥 Team Status</div>
                    <a href="teams.php" class="view-all">Manage Teams →</a>
                </div>

                <?php if (empty($all_teams)): ?>
                <div class="empty-state">No teams found.</div>
                <?php else: ?>
                <div class="teams-grid">
                    <?php
                    $type_icons   = ['fire'=>'🔥','medical'=>'🚑','rescue'=>'🚒','police'=>'🚔'];
                    $type_classes = ['fire'=>'fire','medical'=>'medical','rescue'=>'rescue','police'=>'police'];
                    foreach ($all_teams as $team):
                        $icon  = $type_icons[$team['team_type']] ?? '👥';
                        $tclass = $type_classes[$team['team_type']] ?? 'police';
                        $status_map = ['available'=>'available','busy'=>'busy','offline'=>'offline','on-call'=>'on-call'];
                        $avail_class = $status_map[$team['status']] ?? 'offline';
                    ?>
                    <div class="team-card">
                        <div class="team-card-header">
                            <div class="team-name-row">
                                <div class="team-icon <?php echo $tclass; ?>"><?php echo $icon; ?></div>
                                <div>
                                    <div class="team-card-name"><?php echo htmlspecialchars($team['team_name']); ?></div>
                                    <div class="team-card-type"><?php echo ucfirst($team['team_type']); ?> Response</div>
                                </div>
                            </div>
                            <span class="avail-pill <?php echo $avail_class; ?>"><?php echo ucfirst($team['status']); ?></span>
                        </div>
                        <div class="team-stats">
                            <div>
                                <div class="team-stat-label">Members</div>
                                <div class="team-stat-val" style="color:var(--blue-500);"><?php echo (int)$team['member_count']; ?></div>
                            </div>
                            <div>
                                <div class="team-stat-label">On Scene</div>
                                <div class="team-stat-val" style="color:var(--amber);"><?php echo (int)$team['on_scene']; ?></div>
                            </div>
                            <div>
                                <div class="team-stat-label">Resolved</div>
                                <div class="team-stat-val" style="color:var(--green);"><?php echo (int)$team['resolved_count']; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div><!-- /tab-teams -->

        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
}
</script>
</body>
</html>