<?php
/**
 * FlashCru Emergency Response System
 * Incidents Management — Red/White/Blue Theme v3.0
 * Updated: Uses barangays, incident_types, report_status tables
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Incidents';
$db = new Database();

// ── Handle Delete ─────────────────────────────────────────────
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    $db->delete('incidents', 'id = :id', ['id' => $id]);
    logActivity($_SESSION['user_id'], $id, 'incident_deleted', 'Incident #' . $id . ' deleted');
    header('Location: incidents.php?msg=deleted');
    exit();
}

// ── Handle Status Update ──────────────────────────────────────
if (isset($_POST['update_status'])) {
    $id        = (int)$_POST['incident_id'];
    $status_id = (int)$_POST['status_id'];
    $db->update('incidents',
        ['status_id' => $status_id, 'updated_at' => date('Y-m-d H:i:s')],
        'id = :id', ['id' => $id]
    );
    logActivity($_SESSION['user_id'], $id, 'status_updated', 'Status updated for Incident #' . $id);
    header('Location: incidents.php?msg=updated');
    exit();
}

// ── Handle Assign Team ────────────────────────────────────────
if (isset($_POST['assign_team'])) {
    $id      = (int)$_POST['incident_id'];
    $team_id = (int)$_POST['team_id'];
    // Set to Dispatched (status_id = 2) when assigning
    $db->update('incidents',
        ['assigned_team_id' => $team_id, 'status_id' => 2, 'updated_at' => date('Y-m-d H:i:s')],
        'id = :id', ['id' => $id]
    );
    logActivity($_SESSION['user_id'], $id, 'team_assigned', 'Team assigned to Incident #' . $id);
    header('Location: incidents.php?msg=assigned');
    exit();
}

// ── Filters ───────────────────────────────────────────────────
$filter_status = sanitize($_GET['status'] ?? 'all');
$filter_type   = sanitize($_GET['type']   ?? 'all');
$search        = sanitize($_GET['search'] ?? '');

$where  = "1=1";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND rs.name = :status";
    $params['status'] = $filter_status;
}
if ($filter_type !== 'all') {
    $where .= " AND it.id = :type";
    $params['type'] = $filter_type;
}
if (!empty($search)) {
    $where .= " AND (u.full_name LIKE :search OR b.name LIKE :search2 OR i.description LIKE :search3)";
    $params['search']  = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
    $params['search3'] = '%' . $search . '%';
}

$incidents = $db->fetchAll("
    SELECT i.*,
           t.team_name,
           t.team_id,
           u.full_name         AS reporter,
           it.name             AS type_name,
           it.icon             AS type_icon,
           it.color            AS type_color,
           b.name              AS barangay_name,
           rs.name             AS status_name,
           rs.color            AS status_color,
           rs.id               AS status_id
    FROM incidents i
    LEFT JOIN teams t           ON i.assigned_team_id = t.team_id
    LEFT JOIN users u           ON i.user_id = u.user_id
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays b       ON i.barangay_id = b.id
    LEFT JOIN report_status rs  ON i.status_id = rs.id
    WHERE $where
    ORDER BY i.created_at DESC
", $params);

// Fetch dropdown data
$all_teams      = $db->fetchAll("SELECT team_id, team_name, team_type, status FROM teams ORDER BY team_name");
$all_statuses   = $db->fetchAll("SELECT * FROM report_status ORDER BY id ASC");
$all_types      = $db->fetchAll("SELECT * FROM incident_types ORDER BY name ASC");
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

            <!-- Page Header -->
            <div class="flex-between mb-20">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--navy);">🚨 Incident Reports</h2>
                    <p class="text-muted" style="font-size:13px;margin-top:3px;">Manage and track all emergency incident reports</p>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">✅ Incident <?php echo htmlspecialchars($_GET['msg']); ?> successfully!</div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="panel mb-24" style="padding:16px 20px;">
                <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <input type="text" name="search" class="form-control"
                           placeholder="🔍 Search reporter, barangay, description..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="max-width:260px;">

                    <select name="status" class="form-control" style="max-width:160px;">
                        <option value="all">All Statuses</option>
                        <?php foreach ($all_statuses as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['name']); ?>"
                            <?php echo $filter_status === $s['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="type" class="form-control" style="max-width:160px;">
                        <option value="all">All Types</option>
                        <?php foreach ($all_types as $it): ?>
                        <option value="<?php echo $it['id']; ?>"
                            <?php echo $filter_type == $it['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($it['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="incidents.php" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>

            <!-- Incidents Table -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">📋 All Reports
                        <span style="font-size:13px;color:var(--gray-400);font-weight:400;">
                            (<?php echo count($incidents); ?> result<?php echo count($incidents) !== 1 ? 's' : ''; ?>)
                        </span>
                    </h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Reporter</th>
                                <th>Location</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Assigned Team</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                            <tr><td colspan="9">
                                <div class="empty-state">No incidents found matching your filters.</div>
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($incidents as $inc):
                                $sc = $inc['status_color'] ?? '#6B7280';
                                $sn = $inc['status_name']  ?? 'Unknown';
                                $tc = $inc['type_color']   ?? '#A63244';
                            ?>
                            <tr>
                                <td><span class="incident-id">#<?php echo $inc['id']; ?></span></td>
                                <td>
                                    <span class="type-badge" style="background:<?php echo $tc; ?>18;color:<?php echo $tc; ?>;border:1px solid <?php echo $tc; ?>33;">
                                        <?php if (!empty($inc['type_icon'])): ?>
                                            <i class="<?php echo htmlspecialchars($inc['type_icon']); ?>"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($inc['type_name'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td style="font-size:12.5px;">
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($inc['reporter'] ?? '—'); ?></div>
                                    <?php if (!empty($inc['contact_number'])): ?>
                                        <div style="font-size:11px;color:var(--gray-400);"><?php echo htmlspecialchars($inc['contact_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="incident-title"><?php echo htmlspecialchars($inc['barangay_name'] ?? '—'); ?></div>
                                    <?php if (!empty($inc['street_landmark'])): ?>
                                        <div class="incident-addr">📍 <?php echo htmlspecialchars($inc['street_landmark']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:180px;font-size:12px;color:var(--gray-500);">
                                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo htmlspecialchars($inc['description'] ?? '—'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="background:<?php echo $sc; ?>22;color:<?php echo $sc; ?>;border:1px solid <?php echo $sc; ?>44;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;">
                                        <?php echo $sn; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($inc['team_name'])): ?>
                                        <span class="team-text"><?php echo htmlspecialchars($inc['team_name']); ?></span>
                                    <?php else: ?>
                                        <span class="team-unassigned">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted" style="font-size:11px;white-space:nowrap;">
                                    <?php echo date('M j, Y', strtotime($inc['created_at'])); ?><br>
                                    <span style="color:var(--gray-400);"><?php echo date('g:i A', strtotime($inc['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="flex gap-8">
                                        <!-- View -->
                                        <button class="btn btn-secondary btn-sm"
                                                onclick="openView(<?php echo htmlspecialchars(json_encode($inc)); ?>)"
                                                title="View Details">👁️</button>
                                        <!-- Update Status -->
                                        <?php if (isAdmin() || isDispatcher()): ?>
                                        <button class="btn btn-secondary btn-sm"
                                                onclick="openStatusModal(<?php echo $inc['id']; ?>, <?php echo $inc['status_id'] ?? 1; ?>)"
                                                title="Update Status">📝</button>
                                        <!-- Assign Team -->
                                        <button class="btn btn-primary btn-sm"
                                                onclick="openAssignModal(<?php echo $inc['id']; ?>, <?php echo $inc['team_id'] ?? 'null'; ?>)"
                                                title="Assign Team">👥</button>
                                        <?php endif; ?>
                                        <?php if (isAdmin()): ?>
                                        <a href="incidents.php?delete=<?php echo $inc['id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this incident report?')">🗑️</a>
                                        <?php endif; ?>
                                    </div>
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

<!-- ── View Detail Modal ──────────────────────────────────────── -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="viewModalTitle">📋 Incident Details</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">×</button>
        </div>
        <div class="modal-body" id="viewModalBody" style="font-size:13.5px;"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- ── Update Status Modal ────────────────────────────────────── -->
<div class="modal-overlay" id="statusModal">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-header">
            <h3 class="modal-title">📝 Update Status</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="incident_id" id="status_incident_id">
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select name="status_id" id="status_select" class="form-control" required>
                        <?php foreach ($all_statuses as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Assign Team Modal ──────────────────────────────────────── -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-header">
            <h3 class="modal-title">👥 Assign Team</h3>
            <button class="modal-close" onclick="closeModal('assignModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="incident_id" id="assign_incident_id">
                <div class="form-group">
                    <label class="form-label">Select Response Team</label>
                    <select name="team_id" id="assign_team_select" class="form-control" required>
                        <option value="">— No Team —</option>
                        <?php foreach ($all_teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>">
                            <?php echo htmlspecialchars($team['team_name']); ?>
                            (<?php echo ucfirst($team['status']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-group" style="margin-top:10px;">
                        <p style="font-size:12px;color:var(--gray-400);">
                            ⚡ Assigning a team will automatically update the status to <strong>Dispatched</strong>.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" name="assign_team" class="btn btn-primary">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function openStatusModal(incidentId, currentStatusId) {
    document.getElementById('status_incident_id').value = incidentId;
    document.getElementById('status_select').value = currentStatusId;
    openModal('statusModal');
}

function openAssignModal(incidentId, currentTeamId) {
    document.getElementById('assign_incident_id').value = incidentId;
    if (currentTeamId) {
        document.getElementById('assign_team_select').value = currentTeamId;
    }
    openModal('assignModal');
}

function openView(inc) {
    const sc = inc.status_color || '#6B7280';
    const sn = inc.status_name  || 'Unknown';
    document.getElementById('viewModalTitle').textContent = '📋 Incident Report #' + inc.id;
    document.getElementById('viewModalBody').innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;width:38%;">Reporter</td>
                <td style="padding:9px 0;font-weight:600;">${inc.reporter || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Contact</td>
                <td style="padding:9px 0;">${inc.contact_number || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Incident Type</td>
                <td style="padding:9px 0;font-weight:600;">${inc.type_name || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Barangay</td>
                <td style="padding:9px 0;">${inc.barangay_name || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Street / Landmark</td>
                <td style="padding:9px 0;">${inc.street_landmark || '—'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Assigned Team</td>
                <td style="padding:9px 0;">${inc.team_name || 'Unassigned'}</td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Status</td>
                <td style="padding:9px 0;">
                    <span style="background:${sc}22;color:${sc};border:1px solid ${sc}44;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;">${sn}</span>
                </td>
            </tr>
            <tr style="border-bottom:1px solid #F3F4F6;">
                <td style="padding:9px 0;color:#9CA3AF;">Date Reported</td>
                <td style="padding:9px 0;font-size:12px;">${inc.created_at || '—'}</td>
            </tr>
        </table>
        <div style="margin-top:14px;">
            <div style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">DESCRIPTION</div>
            <div style="font-size:13px;color:#374151;line-height:1.6;">${inc.description || '—'}</div>
        </div>
    `;
    openModal('viewModal');
}
</script>
</body>
</html>