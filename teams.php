<?php
/**
 * FlashCru Emergency Response System
 * Teams Management Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Teams';
$db = new Database();

// Handle Delete Team
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    $db->delete('teams', 'team_id = :id', ['id' => $id]);
    header('Location: teams.php?msg=deleted');
    exit();
}

// Handle Status Update
if (isset($_POST['update_team_status'])) {
    $id     = (int)$_POST['team_id'];
    $status = sanitize($_POST['status']);
    $db->update('teams', ['status' => $status], 'team_id = :id', ['id' => $id]);
    header('Location: teams.php?msg=updated');
    exit();
}

// Handle Save Team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
    $data = [
        'team_name'      => sanitize($_POST['team_name']),
        'team_type'      => sanitize($_POST['team_type']),
        'status'         => sanitize($_POST['status']),
        'location'       => sanitize($_POST['location']),
        'contact_number' => sanitize($_POST['contact_number'])
    ];

    if (!empty($_POST['team_id'])) {
        $db->update('teams', $data, 'team_id = :id', ['id' => (int)$_POST['team_id']]);
        header('Location: teams.php?msg=updated');
    } else {
        $db->insert('teams', $data);
        header('Location: teams.php?msg=created');
    }
    exit();
}

// Fetch all teams with member count
$teams = $db->fetchAll("
    SELECT t.*,
           COUNT(tm.team_mem_id) AS member_count,
           COUNT(i.incident_id) AS active_incidents
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    LEFT JOIN incidents i ON t.team_id = i.assigned_team AND i.status IN ('active','critical')
    GROUP BY t.team_id
    ORDER BY t.status, t.team_name
");

// Available responders for team member assignment
$responders = $db->fetchAll("SELECT user_id, full_name, role FROM users WHERE status = 'active' ORDER BY full_name");

// Edit team
$edit_team = null;
if (isset($_GET['edit'])) {
    $edit_team = $db->fetchOne("SELECT * FROM teams WHERE team_id = ?", [(int)$_GET['edit']]);
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
                    <h2 style="font-size:22px;font-weight:800;">👥 Teams</h2>
                    <p class="text-muted" style="font-size:13px;">Manage emergency response teams</p>
                </div>
                <?php if (isAdmin() || isDispatcher()): ?>
                <button class="btn btn-primary" onclick="openModal('teamModal')">+ New Team</button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">✅ Team <?php echo htmlspecialchars($_GET['msg']); ?> successfully!</div>
            <?php endif; ?>

            <!-- Team Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <h3>Available</h3>
                        <div class="stat-value green">
                            <?php echo count(array_filter($teams, fn($t) => $t['status'] === 'available')); ?>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🚨</div>
                    <div class="stat-info">
                        <h3>Busy</h3>
                        <div class="stat-value red">
                            <?php echo count(array_filter($teams, fn($t) => $t['status'] === 'busy')); ?>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cyan">👥</div>
                    <div class="stat-info">
                        <h3>Total Teams</h3>
                        <div class="stat-value cyan"><?php echo count($teams); ?></div>
                    </div>
                </div>
            </div>

            <!-- Teams Grid -->
            <div class="teams-grid">
                <?php foreach ($teams as $team): ?>
                <?php
                $type_icons = ['fire'=>'🔥','medical'=>'🚑','police'=>'🚔','rescue'=>'🚒'];
                $icon = $type_icons[$team['team_type']] ?? '👥';
                ?>
                <div class="team-card">
                    <div class="team-card-header">
                        <div class="team-card-icon"><?php echo $icon; ?></div>
                        <div class="team-card-info">
                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                            <p><?php echo ucfirst($team['team_type']); ?> Department</p>
                        </div>
                        <span class="badge badge-<?php echo $team['status']; ?>">
                            <?php echo ucfirst($team['status']); ?>
                        </span>
                    </div>

                    <div class="team-card-body">
                        <div class="team-detail">
                            <span>📍 Location</span>
                            <span><?php echo htmlspecialchars($team['location'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="team-detail">
                            <span>📞 Contact</span>
                            <span><?php echo htmlspecialchars($team['contact_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="team-detail">
                            <span>👤 Members</span>
                            <span><?php echo $team['member_count']; ?> members</span>
                        </div>
                        <div class="team-detail">
                            <span>🚨 Active Incidents</span>
                            <span><?php echo $team['active_incidents']; ?></span>
                        </div>
                    </div>

                    <div class="team-card-footer">
                        <!-- Quick Status Change -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                            <select name="status" class="form-control" style="width:auto;padding:6px 10px;font-size:12px;"
                                    onchange="this.form.submit()">
                                <option value="available" <?php echo $team['status']==='available'?'selected':''; ?>>Available</option>
                                <option value="busy"      <?php echo $team['status']==='busy'?'selected':''; ?>>Busy</option>
                                <option value="offline"   <?php echo $team['status']==='offline'?'selected':''; ?>>Offline</option>
                            </select>
                            <button type="submit" name="update_team_status" style="display:none;"></button>
                        </form>

                        <div class="flex gap-8">
                            <a href="teams.php?edit=<?php echo $team['team_id']; ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <?php if (isAdmin()): ?>
                            <a href="teams.php?delete=<?php echo $team['team_id']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this team?')">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- Team Modal -->
<div class="modal-overlay <?php echo $edit_team ? 'active' : ''; ?>" id="teamModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $edit_team ? '✏️ Edit Team' : '+ New Team'; ?></h3>
            <button class="modal-close" onclick="closeModal('teamModal')">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="team_id" value="<?php echo $edit_team['team_id'] ?? ''; ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Team Name *</label>
                        <input type="text" name="team_name" class="form-control" required
                               value="<?php echo htmlspecialchars($edit_team['team_name'] ?? ''); ?>"
                               placeholder="e.g. Fire Squad Alpha">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Team Type *</label>
                        <select name="team_type" class="form-control" required>
                            <option value="fire"    <?php echo ($edit_team['team_type']??'')==='fire'?'selected':''; ?>>🔥 Fire</option>
                            <option value="medical" <?php echo ($edit_team['team_type']??'')==='medical'?'selected':''; ?>>🚑 Medical</option>
                            <option value="police"  <?php echo ($edit_team['team_type']??'')==='police'?'selected':''; ?>>🚔 Police</option>
                            <option value="rescue"  <?php echo ($edit_team['team_type']??'')==='rescue'?'selected':''; ?>>🚒 Rescue</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="available" <?php echo ($edit_team['status']??'available')==='available'?'selected':''; ?>>Available</option>
                            <option value="busy"      <?php echo ($edit_team['status']??'')==='busy'?'selected':''; ?>>Busy</option>
                            <option value="offline"   <?php echo ($edit_team['status']??'')==='offline'?'selected':''; ?>>Offline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control"
                               value="<?php echo htmlspecialchars($edit_team['contact_number'] ?? ''); ?>"
                               placeholder="e.g. 911-001">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Base Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo htmlspecialchars($edit_team['location'] ?? ''); ?>"
                           placeholder="Station address">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('teamModal')">Cancel</button>
                <button type="submit" name="save_team" class="btn btn-primary">
                    <?php echo $edit_team ? 'Update Team' : 'Create Team'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    <?php if ($edit_team): ?>window.location.href = 'teams.php';<?php endif; ?>
}
</script>

<style>
.teams-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.team-card {
    background: #111827;
    border: 1px solid #1E293B;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s;
}

.team-card:hover {
    border-color: #334155;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.team-card-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: 1px solid #1E293B;
}

.team-card-icon {
    font-size: 28px;
    width: 52px;
    height: 52px;
    background: #0F172A;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.team-card-info {
    flex: 1;
}

.team-card-info h3 {
    font-size: 15px;
    font-weight: 700;
    color: #E5E7EB;
    margin-bottom: 2px;
}

.team-card-info p {
    font-size: 12px;
    color: #6B7280;
}

.team-card-body {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.team-detail {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.team-detail span:first-child {
    color: #6B7280;
}

.team-detail span:last-child {
    color: #E5E7EB;
    font-weight: 500;
}

.team-card-footer {
    padding: 14px 20px;
    border-top: 1px solid #1E293B;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

@media (max-width: 1100px) { .teams-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 700px)  { .teams-grid { grid-template-columns: 1fr; } }
</style>
</body>
</html>