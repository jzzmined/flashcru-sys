<?php
/**
 * FlashCru Emergency Response System
 * Incidents Management Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Incidents';
$db = new Database();

// Handle Delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    $db->delete('incidents', 'incident_id = :id', ['id' => $id]);
    logActivity($_SESSION['user_id'], $id, 'incident_deleted', 'Incident #' . $id . ' deleted');
    header('Location: incidents.php?msg=deleted');
    exit();
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['incident_id'];
    $status = sanitize($_POST['status']);
    $resolved_at = ($status === 'resolved') ? date('Y-m-d H:i:s') : null;
    $db->update('incidents', 
        ['status' => $status, 'resolved_at' => $resolved_at], 
        'incident_id = :id', 
        ['id' => $id]
    );
    logActivity($_SESSION['user_id'], $id, 'status_updated', 'Status changed to ' . $status);
    header('Location: incidents.php?msg=updated');
    exit();
}

// Handle Create/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_incident'])) {
    $data = [
        'incident_type' => sanitize($_POST['incident_type']),
        'title'         => sanitize($_POST['title']),
        'description'   => sanitize($_POST['description']),
        'location'      => sanitize($_POST['location']),
        'latitude'      => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude'     => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'status'        => sanitize($_POST['status']),
        'priority'      => sanitize($_POST['priority']),
        'assigned_team' => !empty($_POST['assigned_team']) ? (int)$_POST['assigned_team'] : null,
        'reported_by'   => $_SESSION['user_id']
    ];

    if (!empty($_POST['incident_id'])) {
        $id = (int)$_POST['incident_id'];
        $db->update('incidents', $data, 'incident_id = :id', ['id' => $id]);
        logActivity($_SESSION['user_id'], $id, 'incident_updated', 'Incident updated');
        header('Location: incidents.php?msg=updated');
    } else {
        $new_id = $db->insert('incidents', $data);
        logActivity($_SESSION['user_id'], $new_id, 'incident_created', 'New incident: ' . $data['title']);
        header('Location: incidents.php?msg=created');
    }
    exit();
}

// Filters
$filter_status = sanitize($_GET['status'] ?? 'all');
$filter_type   = sanitize($_GET['type'] ?? 'all');
$search        = sanitize($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND i.status = :status";
    $params['status'] = $filter_status;
}
if ($filter_type !== 'all') {
    $where .= " AND i.incident_type = :type";
    $params['type'] = $filter_type;
}
if (!empty($search)) {
    $where .= " AND (i.title LIKE :search OR i.location LIKE :search2)";
    $params['search']  = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}

$incidents = $db->fetchAll("
    SELECT i.*, t.team_name, u.full_name AS reporter
    FROM incidents i
    LEFT JOIN teams t ON i.assigned_team = t.team_id
    LEFT JOIN users u ON i.reported_by = u.user_id
    WHERE $where
    ORDER BY FIELD(i.status,'critical','active','pending','resolved'), i.created_at DESC
", $params);

$teams = $db->fetchAll("SELECT team_id, team_name, team_type, status FROM teams ORDER BY team_name");

// Edit data
$edit_incident = null;
if (isset($_GET['edit'])) {
    $edit_incident = $db->fetchOne("SELECT * FROM incidents WHERE incident_id = ?", [(int)$_GET['edit']]);
}
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
                    <h2 style="font-size:22px;font-weight:800;">🚨 Incidents</h2>
                    <p class="text-muted" style="font-size:13px;">Manage all emergency incidents</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('incidentModal')">
                    + New Incident
                </button>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">
                    ✅ Incident <?php echo htmlspecialchars($_GET['msg']); ?> successfully!
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="panel mb-20" style="padding: 16px 20px;">
                <form method="GET" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="🔍 Search incidents..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="max-width:220px;">

                    <select name="status" class="form-control" style="max-width:150px;">
                        <option value="all">All Status</option>
                        <option value="critical" <?php echo $filter_status=='critical'?'selected':''; ?>>Critical</option>
                        <option value="active"   <?php echo $filter_status=='active'?'selected':''; ?>>Active</option>
                        <option value="pending"  <?php echo $filter_status=='pending'?'selected':''; ?>>Pending</option>
                        <option value="resolved" <?php echo $filter_status=='resolved'?'selected':''; ?>>Resolved</option>
                    </select>

                    <select name="type" class="form-control" style="max-width:150px;">
                        <option value="all">All Types</option>
                        <option value="fire"     <?php echo $filter_type=='fire'?'selected':''; ?>>🔥 Fire</option>
                        <option value="medical"  <?php echo $filter_type=='medical'?'selected':''; ?>>🚑 Medical</option>
                        <option value="accident" <?php echo $filter_type=='accident'?'selected':''; ?>>🚗 Accident</option>
                        <option value="rescue"   <?php echo $filter_type=='rescue'?'selected':''; ?>>🚒 Rescue</option>
                        <option value="other"    <?php echo $filter_type=='other'?'selected':''; ?>>⚠️ Other</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="incidents.php" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>

            <!-- Incidents Table -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        All Incidents 
                        <span style="font-size:13px;color:#6B7280;font-weight:400;">
                            (<?php echo count($incidents); ?> results)
                        </span>
                    </h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title & Location</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned Team</th>
                                <th>Reported</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                                <tr><td colspan="8">
                                    <div class="empty-state">
                                        <p>No incidents found</p>
                                    </div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($incidents as $inc): ?>
                                <tr>
                                    <td class="text-muted">#<?php echo $inc['incident_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inc['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">📍 <?php echo htmlspecialchars($inc['location']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $icons = ['fire'=>'🔥','medical'=>'🚑','accident'=>'🚗','rescue'=>'🚒','other'=>'⚠️'];
                                        echo ($icons[$inc['incident_type']] ?? '⚠️') . ' ' . ucfirst($inc['incident_type']);
                                        ?>
                                    </td>
                                    <td><span class="badge badge-<?php echo $inc['status']; ?>"><?php echo ucfirst($inc['status']); ?></span></td>
                                    <td><span class="badge badge-<?php echo $inc['priority']; ?>"><?php echo ucfirst($inc['priority']); ?></span></td>
                                    <td class="text-muted"><?php echo $inc['team_name'] ?? '<span style="color:#6B7280">Unassigned</span>'; ?></td>
                                    <td class="text-muted" style="font-size:12px;"><?php echo date('M j, Y', strtotime($inc['created_at'])); ?></td>
                                    <td>
                                        <div class="flex gap-8">
                                            <a href="incidents.php?edit=<?php echo $inc['incident_id']; ?>" class="btn btn-secondary btn-sm">✏️</a>
                                            <?php if (isAdmin()): ?>
                                            <a href="incidents.php?delete=<?php echo $inc['incident_id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this incident?')">🗑️</a>
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
        </div>
    </div>
</div>

<!-- Add/Edit Incident Modal -->
<div class="modal-overlay <?php echo ($edit_incident) ? 'active' : ''; ?>" id="incidentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $edit_incident ? '✏️ Edit Incident' : '+ New Incident'; ?></h3>
            <button class="modal-close" onclick="closeModal('incidentModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="incident_id" value="<?php echo $edit_incident['incident_id'] ?? ''; ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Incident Type *</label>
                        <select name="incident_type" class="form-control" required>
                            <option value="fire"     <?php echo ($edit_incident['incident_type']??'')==='fire'?'selected':''; ?>>🔥 Fire</option>
                            <option value="medical"  <?php echo ($edit_incident['incident_type']??'')==='medical'?'selected':''; ?>>🚑 Medical</option>
                            <option value="accident" <?php echo ($edit_incident['incident_type']??'')==='accident'?'selected':''; ?>>🚗 Accident</option>
                            <option value="rescue"   <?php echo ($edit_incident['incident_type']??'')==='rescue'?'selected':''; ?>>🚒 Rescue</option>
                            <option value="other"    <?php echo ($edit_incident['incident_type']??'')==='other'?'selected':''; ?>>⚠️ Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select name="priority" class="form-control" required>
                            <option value="low"      <?php echo ($edit_incident['priority']??'')==='low'?'selected':''; ?>>Low</option>
                            <option value="medium"   <?php echo ($edit_incident['priority']??'medium')==='medium'?'selected':''; ?>>Medium</option>
                            <option value="high"     <?php echo ($edit_incident['priority']??'')==='high'?'selected':''; ?>>High</option>
                            <option value="critical" <?php echo ($edit_incident['priority']??'')==='critical'?'selected':''; ?>>Critical</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo htmlspecialchars($edit_incident['title'] ?? ''); ?>"
                           placeholder="Brief incident title">
                </div>

                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-control" required
                           value="<?php echo htmlspecialchars($edit_incident['location'] ?? ''); ?>"
                           placeholder="Street address or landmark">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Latitude (GPS)</label>
                        <input type="text" name="latitude" class="form-control"
                               value="<?php echo $edit_incident['latitude'] ?? ''; ?>"
                               placeholder="e.g. 7.0731">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude (GPS)</label>
                        <input type="text" name="longitude" class="form-control"
                               value="<?php echo $edit_incident['longitude'] ?? ''; ?>"
                               placeholder="e.g. 125.6128">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="pending"  <?php echo ($edit_incident['status']??'pending')==='pending'?'selected':''; ?>>Pending</option>
                            <option value="active"   <?php echo ($edit_incident['status']??'')==='active'?'selected':''; ?>>Active</option>
                            <option value="critical" <?php echo ($edit_incident['status']??'')==='critical'?'selected':''; ?>>Critical</option>
                            <option value="resolved" <?php echo ($edit_incident['status']??'')==='resolved'?'selected':''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign Team</label>
                        <select name="assigned_team" class="form-control">
                            <option value="">-- No Team --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"
                                    <?php echo ($edit_incident['assigned_team']??0)==$team['team_id']?'selected':''; ?>>
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                    (<?php echo $team['status']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Detailed incident description..."><?php echo htmlspecialchars($edit_incident['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('incidentModal')">Cancel</button>
                <button type="submit" name="save_incident" class="btn btn-primary">
                    <?php echo $edit_incident ? 'Update Incident' : 'Create Incident'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { 
    document.getElementById(id).classList.remove('active');
    <?php if ($edit_incident): ?>
    window.location.href = 'incidents.php';
    <?php endif; ?>
}
</script>
</body>
</html>