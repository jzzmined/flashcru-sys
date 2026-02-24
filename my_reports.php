<?php
/**
 * FlashCru Emergency Response System
 * My Reports — Red/White/Blue Theme v3.0
 * NEW FILE: Shows logged-in user's submitted incident reports
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'My Reports';
$db = new Database();

$uid = $_SESSION['user_id'];

$reports = $db->fetchAll("
    SELECT
        i.*,
        it.name             AS type_name,
        it.icon             AS type_icon,
        it.color            AS type_color,
        b.name              AS barangay_name,
        rs.name             AS status_name,
        rs.color            AS status_color,
        t.team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays b       ON i.barangay_id = b.id
    LEFT JOIN report_status rs  ON i.status_id = rs.id
    LEFT JOIN teams t           ON i.assigned_team_id = t.team_id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
", [$uid]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — FlashCru</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <div class="flex-between mb-20">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--navy);">📋 My Reports</h2>
                    <p class="text-muted" style="font-size:13px;margin-top:3px;">Track the status of your submitted incident reports</p>
                </div>
                <a href="report_incident.php" class="btn btn-primary">+ New Report</a>
            </div>

            <?php if (empty($reports)): ?>
            <div class="panel">
                <div class="empty-state" style="padding:60px 20px;">
                    <div style="font-size:48px;margin-bottom:16px;opacity:0.4;">📭</div>
                    <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:8px;">No reports submitted yet</div>
                    <p style="font-size:13px;color:var(--gray-400);margin-bottom:20px;">When you submit an incident report, it will appear here.</p>
                    <a href="report_incident.php" class="btn btn-primary">🚨 Submit a Report</a>
                </div>
            </div>
            <?php else: ?>

            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">📁 Your Submitted Reports
                        <span style="font-size:13px;color:var(--gray-400);font-weight:400;">(<?php echo count($reports); ?> total)</span>
                    </h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Incident Type</th>
                                <th>Location</th>
                                <th>Assigned Team</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reports as $i => $r):
                            $sc = $r['status_color'] ?? '#6B7280';
                            $sn = $r['status_name']  ?? 'Unknown';
                            $tc = $r['type_color']   ?? '#A63244';
                        ?>
                            <tr>
                                <td><span class="incident-id">#<?php echo $r['id']; ?></span></td>
                                <td>
                                    <span class="type-badge" style="background:<?php echo $tc; ?>18;color:<?php echo $tc; ?>;border:1px solid <?php echo $tc; ?>33;">
                                        <?php echo htmlspecialchars($r['type_name'] ?? '—'); ?>
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
                                    <span style="background:<?php echo $sc; ?>22;color:<?php echo $sc; ?>;border:1px solid <?php echo $sc; ?>44;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;">
                                        <?php echo $sn; ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size:11px;white-space:nowrap;">
                                    <?php echo date('M j, Y', strtotime($r['created_at'])); ?><br>
                                    <span style="color:var(--gray-400);"><?php echo date('g:i A', strtotime($r['created_at'])); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-secondary btn-sm"
                                            onclick="openDetail(<?php echo htmlspecialchars(json_encode($r)); ?>)">
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

<!-- Detail Modal -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="detailTitle">📋 Report Details</h3>
            <button class="modal-close" onclick="closeModal('detailModal')">×</button>
        </div>
        <div class="modal-body" id="detailBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Close</button>
            <a href="report_incident.php" class="btn btn-primary">+ New Report</a>
        </div>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function openDetail(r) {
    const sc = r.status_color || '#6B7280';
    const sn = r.status_name  || 'Unknown';
    document.getElementById('detailTitle').textContent = '📋 Report #' + r.id;
    document.getElementById('detailBody').innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;width:38%;">Incident Type</td>
                <td style="padding:9px 0;font-weight:600;">${r.type_name || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Barangay</td>
                <td style="padding:9px 0;">${r.barangay_name || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Street / Landmark</td>
                <td style="padding:9px 0;">${r.street_landmark || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Contact Number</td>
                <td style="padding:9px 0;">${r.contact_number || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Assigned Team</td>
                <td style="padding:9px 0;">${r.team_name || 'Not yet assigned'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Status</td>
                <td style="padding:9px 0;">
                    <span style="background:${sc}22;color:${sc};border:1px solid ${sc}44;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;">${sn}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:9px 0;color:#9CA3AF;">Date Filed</td>
                <td style="padding:9px 0;font-size:12px;">${r.created_at || '—'}</td>
            </tr>
        </table>
        <div style="margin-top:14px;">
            <div style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">DESCRIPTION</div>
            <div style="font-size:13px;color:#374151;line-height:1.6;background:#F9FAFB;padding:12px;border-radius:8px;">${r.description || '—'}</div>
        </div>
    `;
    openModal('detailModal');
}
</script>
</body>
</html>