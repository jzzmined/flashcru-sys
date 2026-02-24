<?php
/**
 * FlashCru Emergency Response System
 * Teams Management — v4.0
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$page_title = 'Teams';
$db = new Database();

// Handle Delete
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
        'contact_number' => sanitize($_POST['contact_number']),
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

// Fetch teams
$teams = $db->fetchAll("
    SELECT t.*,
           COUNT(DISTINCT tm.team_mem_id) AS member_count,
           SUM(CASE WHEN rs.name IN ('Dispatched','Ongoing') THEN 1 ELSE 0 END) AS active_incidents
    FROM teams t
    LEFT JOIN team_members tm  ON t.team_id = tm.team_id
    LEFT JOIN incidents i      ON t.team_id = i.assigned_team_id
    LEFT JOIN report_status rs ON i.status_id = rs.id
    GROUP BY t.team_id
    ORDER BY FIELD(t.status,'available','busy','offline'), t.team_name
");

$edit_team = null;
if (isset($_GET['edit'])) {
    $edit_team = $db->fetchOne("SELECT * FROM teams WHERE team_id = ?", [(int)$_GET['edit']]);
}

$count_available = count(array_filter($teams, fn($t) => $t['status'] === 'available'));
$count_busy      = count(array_filter($teams, fn($t) => $t['status'] === 'busy'));
$count_offline   = count(array_filter($teams, fn($t) => $t['status'] === 'offline'));
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
                    <h2 class="page-title">👥 Teams</h2>
                    <p class="page-subtitle">Manage emergency response teams and their availability</p>
                </div>
                <?php if (isAdmin() || isDispatcher()): ?>
                <button class="btn btn-primary" onclick="openModal('teamModal')">+ New Team</button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success" data-autodismiss>✅ Team <?php echo htmlspecialchars($_GET['msg']); ?> successfully.</div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="cards-grid cards-grid-3" style="margin-bottom:28px;">
                <div class="stat-card resolved">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="card-label">Available</div>
                        <div class="stat-value green"><?php echo $count_available; ?></div>
                        <div class="stat-delta">Ready to deploy</div>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon orange">🚨</div>
                    <div class="stat-info">
                        <div class="card-label">Busy / On Scene</div>
                        <div class="stat-value orange"><?php echo $count_busy; ?></div>
                        <div class="stat-delta">Currently deployed</div>
                    </div>
                </div>
                <div class="stat-card today">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info">
                        <div class="card-label">Total Teams</div>
                        <div class="stat-value blue"><?php echo count($teams); ?></div>
                        <div class="stat-delta"><?php echo $count_offline; ?> offline</div>
                    </div>
                </div>
            </div>

            <!-- Teams Grid -->
            <?php if (empty($teams)): ?>
            <div class="panel">
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <div class="empty-state-title">No teams yet</div>
                    <div class="empty-state-desc">
                        <a href="#" onclick="openModal('teamModal');return false;" style="color:var(--primary);">Create the first team →</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="teams-grid">
                <?php
                $type_icons   = ['fire'=>'🔥','medical'=>'🚑','police'=>'🚔','rescue'=>'🚒'];
                $type_class   = ['fire'=>'fire','medical'=>'medical','police'=>'police','rescue'=>'rescue'];
                foreach ($teams as $team):
                    $icon   = $type_icons[$team['team_type']] ?? '👥';
                    $tclass = $type_class[$team['team_type']] ?? 'police';
                    $sbadge = 'badge-' . ($team['status'] ?: 'offline');
                ?>
                <div class="team-card">
                    <div class="team-card-header">
                        <div class="team-card-icon <?php echo $tclass; ?>"><?php echo $icon; ?></div>
                        <div class="team-card-info">
                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                            <p><?php echo ucfirst($team['team_type']); ?> Department</p>
                        </div>
                        <span class="<?php echo $sbadge; ?>"><?php echo ucfirst($team['status']); ?></span>
                    </div>

                    <div class="team-card-body">
                        <div class="team-detail">
                            <span>📍 Location</span>
                            <span><?php echo htmlspecialchars($team['location'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="team-detail">
                            <span>📞 Contact</span>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:12px;"><?php echo htmlspecialchars($team['contact_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="team-detail">
                            <span>👤 Members</span>
                            <span style="font-weight:700;color:var(--dispatched);"><?php echo (int)$team['member_count']; ?></span>
                        </div>
                        <div class="team-detail">
                            <span>🚨 Active Incidents</span>
                            <span style="font-weight:700;color:<?php echo $team['active_incidents'] > 0 ? 'var(--primary)' : 'var(--green)'; ?>;">
                                <?php echo (int)$team['active_incidents']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="team-card-footer">
                        <!-- Quick Status Change -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                            <select name="status" class="form-control"
                                    style="width:auto;padding:6px 28px 6px 10px;font-size:12px;"
                                    onchange="this.form.submit()">
                                <option value="available" <?php echo $team['status']==='available'?'selected':''; ?>>🟢 Available</option>
                                <option value="busy"      <?php echo $team['status']==='busy'?'selected':''; ?>>🟡 Busy</option>
                                <option value="offline"   <?php echo $team['status']==='offline'?'selected':''; ?>>⚫ Offline</option>
                            </select>
                            <button type="submit" name="update_team_status" style="display:none;"></button>
                        </form>

                        <div class="flex gap-8">
                            <a href="teams.php?edit=<?php echo $team['team_id']; ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <?php if (isAdmin()): ?>
                            <a href="teams.php?delete=<?php echo $team['team_id']; ?>"
                               class="btn btn-danger btn-sm"
                               data-confirm="Delete team '<?php echo htmlspecialchars($team['team_name']); ?>'?">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Team Modal -->
<div class="modal-overlay <?php echo $edit_team ? 'active' : ''; ?>" id="teamModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $edit_team ? '✏️ Edit Team' : '+ New Team'; ?></h3>
            <button class="modal-close" onclick="<?php echo $edit_team ? "location.href='teams.php'" : "closeModal('teamModal')"; ?>">×</button>
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
                            <?php foreach (['fire'=>'🔥 Fire','medical'=>'🚑 Medical','police'=>'🚔 Police','rescue'=>'🚒 Rescue'] as $val => $lbl): ?>
                            <option value="<?php echo $val; ?>" <?php echo (($edit_team['team_type']??'')===$val)?'selected':''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="available" <?php echo (($edit_team['status']??'available')==='available')?'selected':''; ?>>🟢 Available</option>
                            <option value="busy"      <?php echo (($edit_team['status']??'')==='busy')?'selected':''; ?>>🟡 Busy</option>
                            <option value="offline"   <?php echo (($edit_team['status']??'')==='offline')?'selected':''; ?>>⚫ Offline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control"
                               value="<?php echo htmlspecialchars($edit_team['contact_number'] ?? ''); ?>"
                               placeholder="e.g. 082-221-3233">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Base Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo htmlspecialchars($edit_team['location'] ?? ''); ?>"
                           placeholder="Station address or landmark">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="<?php echo $edit_team ? "location.href='teams.php'" : "closeModal('teamModal')"; ?>">Cancel</button>
                <button type="submit" name="save_team" class="btn btn-primary">
                    <?php echo $edit_team ? 'Update Team' : 'Create Team'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>