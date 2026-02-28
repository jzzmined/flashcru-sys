<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';

// Add team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = sanitize($_POST['team_name'] ?? '');
    $type = sanitize($_POST['team_type'] ?? '');
    $lead = sanitize($_POST['team_lead'] ?? '');

    if (!$name || !$type) {
        $err = 'Team name and type are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO teams (team_name, team_type, team_lead, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->bind_param("sss", $name, $type, $lead);
        if ($stmt->execute()) {
            $tid = $conn->insert_id;
            if (!empty($_POST['member_name'])) {
                foreach ($_POST['member_name'] as $i => $mn) {
                    $mn = trim($mn);
                    $mr = trim($_POST['member_role'][$i] ?? '');
                    if ($mn) {
                        $ms = $conn->prepare("INSERT INTO team_members (team_id, name, role) VALUES (?, ?, ?)");
                        $ms->bind_param("iss", $tid, $mn, $mr);
                        $ms->execute();
                    }
                }
            }
            logActivity($_SESSION['user_id'], "Created team: $name");
            $msg = "Team \"$name\" added successfully.";
        } else {
            $err = 'Failed to create team.';
        }
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE teams SET status = IF(status='active','inactive','active') WHERE team_id=$id");
    $msg = 'Team status updated.';
}

// Delete team
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM team_members WHERE team_id=$id");
    $conn->query("DELETE FROM teams WHERE team_id=$id");
    logActivity($_SESSION['user_id'], "Deleted team #$id");
    $msg = 'Team deleted.';
}

$teams = $conn->query("
    SELECT t.*, COUNT(tm.team_mem_id) AS member_count
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    GROUP BY t.team_id
    ORDER BY t.created_at DESC
");
if (!$teams) die("Teams query failed: " . $conn->error);
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcOpenSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Response Teams</div>
                    <div class="fc-breadcrumb">Admin / Manage Teams</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?><div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div><?php endif; ?>
            <?php if ($err): ?><div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= $err ?></div><?php endif; ?>

            <div class="row g-4">
                <!-- ADD TEAM FORM -->
                <div class="col-lg-4">
                    <form method="POST">
                        <div class="fc-form-section">
                            <div class="fc-form-section-title">
                                <i class="bi bi-plus-circle-fill"></i> Add New Team
                            </div>
                            <div class="mb-3">
                                <label class="fc-form-label">Team Name <span style="color:var(--fc-primary)">*</span></label>
                                <input type="text" name="team_name" class="fc-form-control"
                                       placeholder="e.g. Alpha Medical Unit" required>
                            </div>
                            <div class="mb-3">
                                <label class="fc-form-label">Team Type <span style="color:var(--fc-primary)">*</span></label>
                                <select name="team_type" class="fc-form-control" required>
                                    <option value="">Select type</option>
                                    <option>Medical</option>
                                    <option>Fire</option>
                                    <option>Security</option>
                                    <option>Search &amp; Rescue</option>
                                    <option>Road Assistance</option>
                                    <option>General Response</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fc-form-label">Team Lead</label>
                                <input type="text" name="team_lead" class="fc-form-control"
                                       placeholder="Lead's full name">
                            </div>

                            <label class="fc-form-label">Members</label>
                            <div id="fcMemberRows" style="margin-bottom:10px;"></div>
                            <button type="button" class="fc-btn" style="font-size:12px;padding:7px 14px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);margin-bottom:16px;" onclick="fcAddMember()">
                                <i class="bi bi-plus-circle"></i> Add Member
                            </button>

                            <button type="submit" name="add_team" class="fc-btn fc-btn-primary" style="width:100%;">
                                <i class="bi bi-save-fill"></i> Save Team
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TEAMS LIST -->
                <div class="col-lg-8">
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-people-fill" style="color:var(--fc-primary)"></i>
                                All Teams (<?= $teams->num_rows ?>)
                            </div>
                        </div>
                        <?php if ($teams->num_rows === 0): ?>
                        <div class="fc-empty"><i class="bi bi-people"></i><h6>No Teams Yet</h6></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="fc-table">
                                <thead>
                                    <tr>
                                        <th>Team Name</th><th>Type</th><th>Lead</th>
                                        <th>Members</th><th>Status</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($t = $teams->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($t['team_name']) ?></strong></td>
                                        <td><span class="fc-pill"><?= htmlspecialchars($t['team_type']) ?></span></td>
                                        <td><?= htmlspecialchars($t['team_lead'] ?: '—') ?></td>
                                        <td>
                                            <span style="background:#eef2ff;color:#5b7cf7;padding:3px 10px;border-radius:100px;font-size:11.5px;font-weight:600;">
                                                <?= $t['member_count'] ?> member<?= $t['member_count'] != 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $t['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"
                                                  style="border-radius:100px;padding:4px 10px;font-size:10.5px;">
                                                <?= ucfirst($t['status']) ?>
                                            </span>
                                        </td>
                                        <td style="display:flex;gap:6px;">
                                            <a href="?toggle=<?= $t['team_id'] ?>" class="fc-icon-btn" title="Toggle Status">
                                                <i class="bi bi-toggle-on"></i>
                                            </a>
                                            <a href="?delete=<?= $t['team_id'] ?>" class="fc-icon-btn del"
                                               onclick="return fcConfirm('Delete this team and all its members?')"
                                               title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>