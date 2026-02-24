<?php
/**
 * FlashCru Emergency Response System
 * Incidents — v4.0
 * Status flow: Pending → Dispatched → Ongoing → Resolved → Cancelled
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Incidents';
$db = new Database();

// ── Handle Status Update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $iid    = (int)$_POST['incident_id'];
    $sid    = (int)$_POST['status_id'];
    $db->update('incidents', ['status_id' => $sid], 'id = :id', ['id' => $iid]);
    logActivity($_SESSION['user_id'], $iid, 'status_updated', 'Incident status changed');
    header('Location: incidents.php?msg=updated');
    exit();
}

// ── Handle Team Assignment ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_team'])) {
    $iid  = (int)$_POST['incident_id'];
    $tid  = (int)$_POST['team_id'];
    $db->update('incidents', ['assigned_team_id' => $tid ?: null], 'id = :id', ['id' => $iid]);
    // Mark team busy
    if ($tid) $db->update('teams', ['status' => 'busy'], 'team_id = :id', ['id' => $tid]);
    logActivity($_SESSION['user_id'], $iid, 'team_assigned', 'Team assigned to incident');
    header('Location: incidents.php?msg=assigned');
    exit();
}

// ── Handle Delete ─────────────────────────────────────────────
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    $db->delete('incidents', 'id = :id', ['id' => $id]);
    header('Location: incidents.php?msg=deleted');
    exit();
}

// ── Fetch Dropdown Data ───────────────────────────────────────
$statuses  = $db->fetchAll("SELECT * FROM report_status ORDER BY id ASC");
$teams     = $db->fetchAll("SELECT * FROM teams ORDER BY team_name ASC");
$types     = $db->fetchAll("SELECT * FROM incident_types ORDER BY name ASC");
$barangays = $db->fetchAll("SELECT * FROM barangays ORDER BY name ASC");

// ── Filter ────────────────────────────────────────────────────
$where = '1=1';
$params = [];

if (!empty($_GET['status'])) {
    $where .= ' AND rs.name = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['type'])) {
    $where .= ' AND it.name = ?';
    $params[] = $_GET['type'];
}

// ── Fetch Incidents ───────────────────────────────────────────
$incidents = $db->fetchAll("
    SELECT i.*,
           it.name AS type_name, it.icon AS type_icon, it.color AS type_color,
           b.name  AS barangay_name,
           rs.name AS status_name, rs.color AS status_color, rs.id AS status_id_val,
           t.team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays b       ON i.barangay_id = b.id
    LEFT JOIN report_status rs  ON i.status_id = rs.id
    LEFT JOIN teams t           ON i.assigned_team_id = t.team_id
    WHERE $where
    ORDER BY i.created_at DESC
", $params);

// Edit incident
$edit_incident = null;
if (isset($_GET['edit'])) {
    $edit_incident = $db->fetchOne("
        SELECT i.*, b.name AS barangay_name
        FROM incidents i
        LEFT JOIN barangays b ON i.barangay_id = b.id
        WHERE i.id = ?", [(int)$_GET['edit']]);
}
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

            <!-- Page Header -->
            <div class="flex-between mb-20">
                <div>
                    <h2 class="page-title">🚨 Incidents</h2>
                    <p class="page-subtitle">Monitor and manage all emergency incident reports</p>
                </div>
                <div class="flex gap-8">
                    <?php if (isAdmin() || isDispatcher()): ?>
                    <a href="report_incident.php" class="btn btn-primary">+ New Report</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success" data-autodismiss>
                ✅ Incident <?php echo htmlspecialchars($_GET['msg']); ?> successfully.
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="panel" style="margin-bottom:20px;">
                <div class="panel-body" style="padding:14px 20px;">
                    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                        <div style="flex:1;min-width:140px;">
                            <label class="form-label">Filter by Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['name']); ?>"
                                    <?php echo (($_GET['status'] ?? '') === $st['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1;min-width:140px;">
                            <label class="form-label">Filter by Type</label>
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                <?php foreach ($types as $ty): ?>
                                <option value="<?php echo htmlspecialchars($ty['name']); ?>"
                                    <?php echo (($_GET['type'] ?? '') === $ty['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ty['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                            <a href="incidents.php" class="btn btn-secondary btn-sm">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Incidents Table -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">📋 Incident Reports
                        <span style="font-size:12px;color:var(--muted);font-weight:400;font-family:'JetBrains Mono',monospace;">(<?php echo count($incidents); ?>)</span>
                    </h3>
                </div>

                <?php if (empty($incidents)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-title">No incidents found</div>
                    <div class="empty-state-desc">No reports match your current filters.</div>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-sort>#</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Assigned Team</th>
                                <th>Status</th>
                                <th data-sort>Filed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($incidents as $r):
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
                                        <span class="team-unassigned">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isAdmin() || isDispatcher()): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="incident_id" value="<?php echo $r['id']; ?>">
                                        <select name="status_id" class="form-control" style="width:auto;padding:5px 28px 5px 10px;font-size:12px;" onchange="this.form.submit()">
                                            <?php foreach ($statuses as $st): ?>
                                            <option value="<?php echo $st['id']; ?>" <?php echo ($r['status_id'] == $st['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($st['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_status" style="display:none;"></button>
                                    </form>
                                    <?php else: ?>
                                    <span class="status-badge <?php echo $sclass; ?>"><?php echo htmlspecialchars($sn); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:11px;color:var(--muted);font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                                    <?php echo date('M j, Y', strtotime($r['created_at'])); ?><br>
                                    <span style="color:var(--subtle);"><?php echo date('g:i A', strtotime($r['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="flex gap-8">
                                        <button class="btn btn-secondary btn-sm"
                                                onclick="openIncidentDetail(<?php echo htmlspecialchars(json_encode($r)); ?>)">
                                            👁️ View
                                        </button>
                                        <?php if (isAdmin() || isDispatcher()): ?>
                                        <button class="btn btn-success btn-sm"
                                                onclick="openAssignModal(<?php echo $r['id']; ?>, <?php echo (int)$r['assigned_team_id']; ?>)">
                                            👥 Assign
                                        </button>
                                        <?php endif; ?>
                                        <?php if (isAdmin()): ?>
                                        <a href="incidents.php?delete=<?php echo $r['id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           data-confirm="Delete incident #<?php echo $r['id']; ?>? This cannot be undone.">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Incident Detail Modal -->
<div class="modal-overlay" id="incidentDetailModal">
    <div class="modal-box modal-box-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="incidentDetailTitle">🚨 Incident Details</h3>
            <button class="modal-close" onclick="closeModal('incidentDetailModal')">×</button>
        </div>
        <div class="modal-body" id="incidentDetailBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('incidentDetailModal')">Close</button>
        </div>
    </div>
</div>

<!-- Assign Team Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">👥 Assign Team</h3>
            <button class="modal-close" onclick="closeModal('assignModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="incident_id" id="assignIncidentId">
                <div class="form-group">
                    <label class="form-label">Select Response Team</label>
                    <select name="team_id" id="assignTeamSelect" class="form-control" required>
                        <option value="">— Remove Assignment —</option>
                        <?php foreach ($teams as $t):
                            $scolor = match($t['status']) { 'available' => '🟢', 'busy' => '🟡', default => '⚫' };
                        ?>
                        <option value="<?php echo $t['team_id']; ?>">
                            <?php echo $scolor; ?> <?php echo htmlspecialchars($t['team_name']); ?> (<?php echo ucfirst($t['status']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="font-size:12px;color:var(--muted);margin-top:4px;">Teams marked 🟢 are currently available.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" name="assign_team" class="btn btn-success">✅ Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
function openAssignModal(incidentId, currentTeamId) {
    document.getElementById('assignIncidentId').value = incidentId;
    var sel = document.getElementById('assignTeamSelect');
    if (sel && currentTeamId) {
        sel.value = currentTeamId;
    }
    openModal('assignModal');
}
</script>
</body>
</html>