<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = 'Team deleted.';
}

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
                        $ms = $conn->prepare("INSERT INTO team_members (team_id, full_name, role) VALUES (?, ?, ?)");
                        if (!$ms) {
                            die("team_members prepare failed: " . $conn->error);
                        }
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
    $id = (int) $_GET['toggle'];
    $conn->query("UPDATE teams SET status = IF(status='active','inactive','active') WHERE team_id=$id");
    header("Location: manage_teams.php?msg=status_updated");
    exit;
}

// Delete team
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM team_members WHERE team_id=$id");
    $conn->query("DELETE FROM teams WHERE team_id=$id");
    logActivity($_SESSION['user_id'], "Deleted team #$id");
    header("Location: manage_teams.php?msg=deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated') {
    $msg = 'Team status updated.';
}

$teams = $conn->query("
    SELECT t.*, COUNT(tm.team_mem_id) AS member_count
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    GROUP BY t.team_id
    ORDER BY t.created_at DESC
");
if (!$teams)
    die("Teams query failed: " . $conn->error);

$teamsData = [];
while ($t = $teams->fetch_assoc()) {
    $teamsData[] = $t;
}
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()" style="display:block;"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Response Teams</div>
                    <div class="fc-breadcrumb">Admin / Manage Teams</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
                <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 style="font-weight: 800; color: #1a1a1a; margin: 0;">Response Teams</h2>
                            <p style="color: #666; margin: 0;">Manage and monitor active response units in real-time.</p>
                        </div>
                        <button type="button" class="btn"
                            style="background: #ff2d2d; color: white; font-weight: 600; border-radius: 8px; padding: 10px 20px;"
                            data-bs-toggle="modal" data-bs-target="#addTeamModal">
                            <i class="bi bi-plus-lg"></i> Add New Team
                        </button>
                    </div>

                    <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #eee;">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" id="tab-all" onclick="filterTeams('all'); return false;"
                                style="color: #ff2d2d; border-bottom: 2px solid #ff2d2d; font-weight: 600;">
                                All Teams (<?= count($teamsData) ?>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="tab-active" onclick="filterTeams('active'); return false;"
                                style="color: #666;">Active</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="tab-inactive" onclick="filterTeams('inactive'); return false;"
                                style="color: #666;">Inactive</a>
                        </li>
                    </ul>

                    <div class="row g-4" id="teamsContainer">
                        <?php foreach ($teamsData as $t): ?>
                            <?php
                                $isActive = $t['status'] === 'active';
                                $badgeBg = $isActive ? 'rgba(12,166,120,0.15)' : 'rgba(150,150,150,0.15)';
                                $badgeColor = $isActive ? '#0ca678' : '#888';
                                $badgeText = $isActive ? '● AVAILABLE' : '● INACTIVE';
                                $typeColors = [
                                    'Medical'  => ['bg' => '#fff0f0', 'accent' => '#ff2d2d', 'icon' => 'bi-heart-pulse-fill'],
                                    'Fire'     => ['bg' => '#fff4e6', 'accent' => '#fd7e14', 'icon' => 'bi-fire'],
                                    'Police'   => ['bg' => '#e8f4fd', 'accent' => '#0d6efd', 'icon' => 'bi-shield-fill'],
                                ];
                                $tc = $typeColors[$t['team_type']] ?? ['bg' => '#f0f0f0', 'accent' => '#555', 'icon' => 'bi-people-fill'];
                            ?>
                            <div class="col-md-6 col-xl-3 team-card" data-status="<?= $t['status'] ?>">
                                <div class="card h-100 shadow-sm" style="border: none; border-radius: 15px; overflow: hidden;">
                                    <!-- Card Header Visual -->
                                    <div style="height: 140px; background: <?= $tc['bg'] ?>; display: flex; align-items: center; justify-content: center; position: relative;">
                                        <i class="bi <?= $tc['icon'] ?>" style="font-size: 48px; color: <?= $tc['accent'] ?>; opacity: 0.3;"></i>
                                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi <?= $tc['icon'] ?>" style="font-size: 52px; color: <?= $tc['accent'] ?>; opacity: 0.85;"></i>
                                        </div>
                                        <!-- Status Badge -->
                                        <span class="badge" style="position: absolute; top: 12px; right: 12px; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-weight: 700; border-radius: 20px; padding: 5px 10px; font-size: 11px;">
                                            <?= $badgeText ?>
                                        </span>
                                        <!-- Type Badge -->
                                        <span class="badge" style="position: absolute; top: 12px; left: 12px; background: <?= $tc['accent'] ?>22; color: <?= $tc['accent'] ?>; font-weight: 700; border-radius: 20px; padding: 5px 10px; font-size: 11px;">
                                            <?= htmlspecialchars($t['team_type']) ?>
                                        </span>
                                    </div>

                                    <div class="card-body">
                                        <h5 style="font-weight: 700; margin-bottom: 10px;"><?= htmlspecialchars($t['team_name']) ?></h5>
                                        <div style="font-size: 14px; color: #666; margin-bottom: 15px;">
                                            <div class="mb-1">
                                                <i class="bi bi-person-badge me-2" style="color: <?= $tc['accent'] ?>;"></i>
                                                <?= htmlspecialchars($t['team_lead'] ?: 'Unassigned') ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-people me-2" style="color: <?= $tc['accent'] ?>;"></i>
                                                <?= (int)$t['member_count'] ?> Member<?= $t['member_count'] != 1 ? 's' : '' ?>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-light w-100"
                                                style="font-weight: 600; border-radius: 8px; font-size: 13px;"
                                                onclick="viewTeamDetails(<?= $t['team_id'] ?>, '<?= htmlspecialchars(addslashes($t['team_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_type']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_lead'] ?: 'Unassigned'), ENT_QUOTES) ?>', <?= (int)$t['member_count'] ?>, '<?= $t['status'] ?>')">
                                                <i class="bi bi-eye me-1"></i> Details
                                            </button>
                                            <!-- Dropdown for actions -->
                                            <div class="dropdown">
                                                <button class="btn btn-light dropdown-toggle" style="border-radius: 8px; padding: 6px 12px;" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 10px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1); font-size: 14px;">
                                                    <li>
                                                        <a class="dropdown-item" href="manage_teams.php?toggle=<?= $t['team_id'] ?>" style="padding: 10px 16px;">
                                                            <?php if ($isActive): ?>
                                                                <i class="bi bi-pause-circle me-2 text-warning"></i> Deactivate
                                                            <?php else: ?>
                                                                <i class="bi bi-play-circle me-2 text-success"></i> Activate
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider" style="margin: 4px 0;"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                            style="padding: 10px 16px;"
                                                            onclick="confirmDelete(<?= $t['team_id'] ?>, '<?= htmlspecialchars(addslashes($t['team_name']), ENT_QUOTES) ?>'); return false;">
                                                            <i class="bi bi-trash me-2"></i> Delete Team
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($teamsData)): ?>
                            <div class="col-12 text-center py-5" style="color: #aaa;">
                                <i class="bi bi-people" style="font-size: 48px;"></i>
                                <p class="mt-3">No teams found. Add your first response team.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php include '../includes/footer.php'; ?>

            <!-- ===== Modal: Add Team ===== -->
            <div class="modal fade" id="addTeamModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="border: none; padding: 25px 25px 10px 25px;">
                            <h5 style="font-weight: 800; display: flex; align-items: center; gap: 10px; margin: 0;">
                                <span style="background: #ff2d2d; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">+</span>
                                Add New Team
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form method="POST" action="manage_teams.php">
                            <div class="modal-body" style="padding: 10px 25px 25px 25px;">
                                <hr style="opacity: 0.1; margin-bottom: 20px;">

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Name <span style="color: #ff2d2d;">*</span></label>
                                    <input type="text" name="team_name" class="form-control" placeholder="e.g. Alpha Medical Unit" required style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Type <span style="color: #ff2d2d;">*</span></label>
                                    <select name="team_type" class="form-select" required style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                        <option value="" disabled selected>Select type</option>
                                        <option value="Medical">Medical Response</option>
                                        <option value="Fire">Fire & Rescue</option>
                                        <option value="Police">Security/Patrol</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Lead</label>
                                    <input type="text" name="team_lead" class="form-control" placeholder="Lead's full name" style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Members</label>
                                    <div id="fcMemberRows"></div>
                                    <button type="button" class="btn btn-outline-secondary w-100 mt-2"
                                        style="border-radius: 10px; border: 1.5px dashed #ccc; padding: 10px; font-weight: 600; background: #fafafa; color: #555;"
                                        onclick="fcAddMember()">
                                        <i class="bi bi-plus-circle me-2"></i> Add Member
                                    </button>
                                </div>

                                <button type="submit" name="add_team" class="btn w-100"
                                    style="background: #ff2d2d; color: white; border-radius: 12px; padding: 14px; font-weight: 700; font-size: 15px; margin-top: 10px;">
                                    <i class="bi bi-save-fill me-2"></i> Save Team
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ===== Modal: Team Details ===== -->
            <div class="modal fade" id="teamDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="border: none; padding: 25px 25px 10px 25px;">
                            <h5 id="detailsModalTitle" style="font-weight: 800; margin: 0;">Team Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 10px 25px 25px 25px;">
                            <hr style="opacity: 0.1; margin-bottom: 20px;">
                            <div id="teamDetailsContent"></div>
                        </div>
                        <div class="modal-footer" style="border: none; padding: 0 25px 25px 25px;">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Close</button>
                            <a id="detailsToggleBtn" href="#" class="btn" style="border-radius: 10px; font-weight: 600;"></a>
                            <a id="detailsDeleteBtn" href="#" class="btn btn-danger" style="border-radius: 10px; font-weight: 600;"
                                onclick="return confirm('Are you sure you want to delete this team?');">
                                <i class="bi bi-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Modal: Confirm Delete ===== -->
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                        <div class="modal-body text-center" style="padding: 30px 25px;">
                            <div style="width: 56px; height: 56px; background: #fff0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                                <i class="bi bi-trash" style="font-size: 24px; color: #ff2d2d;"></i>
                            </div>
                            <h6 style="font-weight: 800; margin-bottom: 8px;">Delete Team?</h6>
                            <p id="deleteConfirmText" style="color: #666; font-size: 14px; margin-bottom: 20px;"></p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light w-100" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancel</button>
                                <a id="deleteConfirmBtn" href="#" class="btn btn-danger w-100" style="border-radius: 10px; font-weight: 600;">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /fc-content -->
    </div><!-- /fc-main -->
</div><!-- /fc-app -->

<script>
// ── Add Member Rows ──────────────────────────────────────────────
let memberCount = 0;
function fcAddMember() {
    memberCount++;
    const container = document.getElementById('fcMemberRows');
    const row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2 align-items-center';
    row.id = 'member-row-' + memberCount;
    row.innerHTML = `
        <input type="text" name="member_name[]" class="form-control"
               placeholder="Full name" style="border-radius: 8px; border: 1.5px solid #eee; font-size: 14px;">
        <input type="text" name="member_role[]" class="form-control"
               placeholder="Role" style="border-radius: 8px; border: 1.5px solid #eee; font-size: 14px; max-width: 120px;">
        <button type="button" class="btn btn-light"
                style="border-radius: 8px; padding: 6px 10px; color: #ff2d2d;"
                onclick="document.getElementById('member-row-${memberCount}').remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    container.appendChild(row);
}

// ── Filter Tabs ───────────────────────────────────────────────────
function filterTeams(filter) {
    const cards = document.querySelectorAll('.team-card');
    const tabs = document.querySelectorAll('.nav-link');

    tabs.forEach(t => {
        t.classList.remove('active');
        t.style.color = '#666';
        t.style.borderBottom = 'none';
    });

    const activeTab = document.getElementById('tab-' + filter);
    if (activeTab) {
        activeTab.classList.add('active');
        activeTab.style.color = '#ff2d2d';
        activeTab.style.borderBottom = '2px solid #ff2d2d';
    }

    cards.forEach(card => {
        const status = card.getAttribute('data-status');
        if (filter === 'all' || status === filter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ── View Team Details ─────────────────────────────────────────────
function viewTeamDetails(id, name, type, lead, memberCount, status) {
    document.getElementById('detailsModalTitle').textContent = name;

    const isActive = status === 'active';
    const badgeColor = isActive ? '#0ca678' : '#888';
    const badgeText = isActive ? '● Active' : '● Inactive';

    document.getElementById('teamDetailsContent').innerHTML = `
        <div class="mb-3 d-flex align-items-center gap-2">
            <span style="background: ${isActive ? 'rgba(12,166,120,0.1)' : 'rgba(150,150,150,0.1)'}; color: ${badgeColor};
                  border-radius: 20px; padding: 4px 12px; font-size: 13px; font-weight: 700;">${badgeText}</span>
            <span style="background: #f0f0f0; color: #555; border-radius: 20px; padding: 4px 12px; font-size: 13px; font-weight: 600;">${type}</span>
        </div>
        <div style="background: #f8f8f8; border-radius: 12px; padding: 16px;">
            <div class="mb-3">
                <div style="font-size: 12px; color: #aaa; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Team Lead</div>
                <div style="font-weight: 600; color: #1a1a1a;">${lead}</div>
            </div>
            <div class="mb-3">
                <div style="font-size: 12px; color: #aaa; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Team Type</div>
                <div style="font-weight: 600; color: #1a1a1a;">${type}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #aaa; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Members</div>
                <div style="font-weight: 600; color: #1a1a1a;">${memberCount} member${memberCount !== 1 ? 's' : ''}</div>
            </div>
        </div>
    `;

    const toggleBtn = document.getElementById('detailsToggleBtn');
    const deleteBtn = document.getElementById('detailsDeleteBtn');

    if (isActive) {
        toggleBtn.textContent = 'Deactivate';
        toggleBtn.style.background = '#fff3cd';
        toggleBtn.style.color = '#856404';
    } else {
        toggleBtn.textContent = 'Activate';
        toggleBtn.style.background = '#d1fae5';
        toggleBtn.style.color = '#065f46';
    }
    toggleBtn.href = `manage_teams.php?toggle=${id}`;
    deleteBtn.href = `manage_teams.php?delete=${id}`;
    deleteBtn.onclick = function() { return confirm(`Delete team "${name}"? This action cannot be undone.`); };

    new bootstrap.Modal(document.getElementById('teamDetailsModal')).show();
}

// ── Confirm Delete ────────────────────────────────────────────────
function confirmDelete(id, name) {
    document.getElementById('deleteConfirmText').textContent = `Delete "${name}"? All members will also be removed. This cannot be undone.`;
    document.getElementById('deleteConfirmBtn').href = `manage_teams.php?delete=${id}`;
    new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
}

// ── Reset member rows when modal opens ───────────────────────────
document.getElementById('addTeamModal').addEventListener('show.bs.modal', function () {
    document.getElementById('fcMemberRows').innerHTML = '';
    memberCount = 0;
});
</script>