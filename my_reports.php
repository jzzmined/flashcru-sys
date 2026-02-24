<?php
/**
 * FlashCru Emergency Response System
 * My Reports — v4.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'My Reports';
$db = new Database();
$uid = $_SESSION['user_id'];

$reports = $db->fetchAll("
    SELECT i.*,
           it.name AS type_name, it.icon AS type_icon, it.color AS type_color,
           b.name  AS barangay_name,
           rs.name AS status_name, rs.color AS status_color,
           t.team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays b       ON i.barangay_id = b.id
    LEFT JOIN report_status rs  ON i.status_id = rs.id
    LEFT JOIN teams t           ON i.assigned_team_id = t.team_id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
", [$uid]);

// Counts
$total    = count($reports);
$pending  = count(array_filter($reports, fn($r) => $r['status_name'] === 'Pending'));
$active   = count(array_filter($reports, fn($r) => in_array($r['status_name'], ['Dispatched','Ongoing'])));
$resolved = count(array_filter($reports, fn($r) => $r['status_name'] === 'Resolved'));
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

            <div class="flex-between mb-20">
                <div>
                    <h2 class="page-title">📋 My Reports</h2>
                    <p class="page-subtitle">Track and manage your submitted incident reports</p>
                </div>
                <a href="report_incident.php" class="btn btn-primary">+ New Report</a>
            </div>

            <!-- Mini stat bar -->
            <?php if ($total > 0): ?>
            <div class="quick-stat-bar">
                <div class="quick-stat-item">
                    <div class="qs-label">Total</div>
                    <div class="qs-value"><?php echo $total; ?></div>
                </div>
                <div class="quick-stat-item">
                    <div class="qs-label">Pending</div>
                    <div class="qs-value" style="color:var(--pending);"><?php echo $pending; ?></div>
                </div>
                <div class="quick-stat-item">
                    <div class="qs-label">Active</div>
                    <div class="qs-value" style="color:var(--ongoing);"><?php echo $active; ?></div>
                </div>
                <div class="quick-stat-item">
                    <div class="qs-label">Resolved</div>
                    <div class="qs-value" style="color:var(--green);"><?php echo $resolved; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($reports)): ?>
            <div class="panel">
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-title">No reports submitted yet</div>
                    <div class="empty-state-desc">When you submit an incident report, it will appear here.</div>
                    <a href="report_incident.php" class="btn btn-primary" style="margin-top:18px;">🚨 Submit a Report</a>
                </div>
            </div>
            <?php else: ?>

            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">📁 Submitted Reports
                        <span style="font-size:12px;color:var(--muted);font-weight:400;font-family:'JetBrains Mono',monospace;">(<?php echo $total; ?>)</span>
                    </h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Assigned Team</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reports as $r):
                            $sc = $r['status_color'] ?? '#64748B';
                            $sn = $r['status_name']  ?? 'Unknown';
                            $tc = $r['type_color']   ?? '#FD5E53';
                            $sclass = 'badge-' . strtolower(str_replace(' ', '-', $sn));
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
                                        <span class="team-unassigned">Not yet assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $sclass; ?>"><?php echo htmlspecialchars($sn); ?></span>
                                </td>
                                <td style="font-size:11px;color:var(--muted);font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                                    <?php echo date('M j, Y', strtotime($r['created_at'])); ?><br>
                                    <span style="color:var(--subtle);"><?php echo date('g:i A', strtotime($r['created_at'])); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-secondary btn-sm"
                                            onclick="openIncidentDetail(<?php echo htmlspecialchars(json_encode($r)); ?>)">
                                        👁️ View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Incident Detail Modal -->
<div class="modal-overlay" id="incidentDetailModal">
    <div class="modal-box modal-box-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="incidentDetailTitle">📋 Report Details</h3>
            <button class="modal-close" onclick="closeModal('incidentDetailModal')">×</button>
        </div>
        <div class="modal-body" id="incidentDetailBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('incidentDetailModal')">Close</button>
            <a href="report_incident.php" class="btn btn-primary">+ New Report</a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>