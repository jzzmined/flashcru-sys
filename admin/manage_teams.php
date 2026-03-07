<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = 'Team archived successfully.';
}

// Add team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = sanitize($_POST['team_name'] ?? '');
    $type = trim($_POST['team_type'] ?? '');
    $lead = sanitize($_POST['team_lead'] ?? '');

    if (!$name || !$type) {
        $err = 'Team name and type are required.';
    } else {
        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['team_image']['name'])) {
            $uploadDir = '../uploads/teams/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['team_image']['tmp_name']);
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($fileType, $allowedTypes)) {
                $err = 'Invalid image type. Only JPG, PNG, GIF, WEBP allowed.';
            } elseif ($_FILES['team_image']['size'] > $maxSize) {
                $err = 'Image must be under 2MB.';
            } else {
                $ext = pathinfo($_FILES['team_image']['name'], PATHINFO_EXTENSION);
                $filename = 'team_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['team_image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/teams/' . $filename;
                } else {
                    $err = 'Failed to upload image.';
                }
            }
        }

        if (!$err) {
            $stmt = $conn->prepare("INSERT INTO teams (team_name, team_type, team_lead, team_image, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->bind_param("ssss", $name, $type, $lead, $imagePath);
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
}

// Edit team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_team'])) {
    $id = (int) ($_POST['edit_team_id'] ?? 0);
    $name = sanitize($_POST['edit_team_name'] ?? '');
    $type = trim($_POST['edit_team_type'] ?? '');
    $lead = sanitize($_POST['edit_team_lead'] ?? '');

    if (!$id || !$name || !$type) {
        $err = 'Team name and type are required.';
    } else {
        // Fetch existing image to keep it by default
        $existingRow = $conn->query("SELECT team_image FROM teams WHERE team_id=$id")->fetch_assoc();
        $imagePath = $existingRow['team_image'] ?? null;

        // Remove image if requested
        if (isset($_POST['remove_team_image']) && $_POST['remove_team_image'] === '1') {
            if ($imagePath && file_exists('../' . $imagePath))
                unlink('../' . $imagePath);
            $imagePath = null;
        }
        // Handle new image upload
        elseif (!empty($_FILES['edit_team_image']['name'])) {
            $uploadDir = '../uploads/teams/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['edit_team_image']['tmp_name']);
            $maxSize = 2 * 1024 * 1024;
            if (!in_array($fileType, $allowedTypes)) {
                $err = 'Invalid image type.';
            } elseif ($_FILES['edit_team_image']['size'] > $maxSize) {
                $err = 'Image must be under 2MB.';
            } else {
                $ext = pathinfo($_FILES['edit_team_image']['name'], PATHINFO_EXTENSION);
                $filename = 'team_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['edit_team_image']['tmp_name'], $uploadDir . $filename)) {
                    if ($imagePath && file_exists('../' . $imagePath))
                        unlink('../' . $imagePath);
                    $imagePath = 'uploads/teams/' . $filename;
                } else {
                    $err = 'Failed to upload image.';
                }
            }
        }
        // No new image and not removing = keep existing $imagePath as is

        if (!$err) {
            $stmt = $conn->prepare("UPDATE teams SET team_name=?, team_type=?, team_lead=?, team_image=? WHERE team_id=?");
            $stmt->bind_param("ssssi", $name, $type, $lead, $imagePath, $id);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], "Updated team #$id: $name");
                $msg = "Team \"$name\" updated successfully.";
            } else {
                $err = 'Failed to update team. ' . $stmt->error;
            }
        }
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $result = $conn->query("SELECT status FROM teams WHERE team_id=$id");
    if ($result && $row = $result->fetch_assoc()) {
        // Treat NULL or anything not 'active' as inactive
        $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE teams SET status = ? WHERE team_id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();
    }
    header("Location: manage_teams.php?msg=status_updated");
    exit;
}

// Soft delete team — marks as 'deleted' so data is preserved
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("UPDATE teams SET status = 'deleted' WHERE team_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], "Soft-deleted team #$id");
        header("Location: manage_teams.php?msg=deleted");
        exit;
    } else {
        $err = 'Failed to delete team.';
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated')
        $msg = 'Team status updated.';
    if ($_GET['msg'] === 'deleted')
        $msg = 'Team archived successfully.';
}

$teams = $conn->query("
    SELECT t.*, COUNT(tm.team_mem_id) AS member_count
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    WHERE t.status != 'deleted'
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
                <button class="fc-menu-btn" onclick="fcToggleSidebar()" style="display:block;"><i
                        class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Response Teams</div>
                    <div class="fc-breadcrumb">Admin / Manage Teams</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
                <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex flex-column gap-2" style="width: 100%; max-width: 350px;">
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute"
                                    style="left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem;"></i>
                                <div class="fc-search-wrapper">
                                    <i class="fas fa-search fc-search-icon"></i>
                                    <input type="text" class="fc-search-input" placeholder="Search teams...">
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn"
                                    style="background-color: #e9ecef; color: #5c636a; border: none; border-radius: 20px; padding: 4px 16px; font-weight: 600; font-size: 13px;">
                                    FILTER <i class="bi bi-funnel ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn"
                            style="background: #ff2d2d; color: white; font-weight: 600; border-radius: 8px; padding: 10px 20px; white-space: nowrap;"
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
                            <a class="nav-link" href="#" id="tab-inactive"
                                onclick="filterTeams('inactive'); return false;" style="color: #666;">Inactive</a>
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
                                'Fire' => ['bg' => '#fff4e6', 'accent' => '#A63244', 'icon' => 'bi-fire'],
                                'Medical Emergency' => ['bg' => '#fff0f0', 'accent' => '#e74c3c', 'icon' => 'bi-heart-pulse-fill'],
                                'Crime / Security' => ['bg' => '#ede9f6', 'accent' => '#8e44ad', 'icon' => 'bi-shield-exclamation'],
                                'Vehicular Accident' => ['bg' => '#fff8e6', 'accent' => '#e67e22', 'icon' => 'bi-car-front-fill'],
                                'Flooding' => ['bg' => '#e8f4fd', 'accent' => '#2980b9', 'icon' => 'bi-droplet-fill'],
                                'Earthquake' => ['bg' => '#f0f4ee', 'accent' => '#795548', 'icon' => 'bi-exclamation-triangle-fill'],
                                'Missing Person' => ['bg' => '#fff0f5', 'accent' => '#f39c12', 'icon' => 'bi-person-x-fill'],
                                'Power Outage' => ['bg' => '#fafae6', 'accent' => '#d4ac0d', 'icon' => 'bi-lightning-fill'],
                                'Landslide' => ['bg' => '#f0ede8', 'accent' => '#6d4c41', 'icon' => 'bi-geo-alt-fill'],
                                'Other' => ['bg' => '#f0f0f0', 'accent' => '#6B7280', 'icon' => 'bi-three-dots'],
                            ];
                            $tc = $typeColors[$t['team_type']] ?? ['bg' => '#f0f0f0', 'accent' => '#555', 'icon' => 'bi-people-fill'];
                            ?>
                            <div class="col-md-6 col-xl-3 team-card" data-status="<?= $t['status'] ?>">
                                <div class="card h-100 shadow-sm"
                                    style="border: none; border-radius: 15px; overflow: hidden;">
                                    <!-- Card Header Visual -->
                                    <?php if (!empty($t['team_image']) && file_exists('../' . $t['team_image'])): ?>
                                        <div style="height: 140px; position: relative; overflow: hidden;">
                                            <img src="../<?= htmlspecialchars($t['team_image']) ?>"
                                                alt="<?= htmlspecialchars($t['team_name']) ?>"
                                                style="width:100%; height:100%; object-fit:cover; display:block;">
                                            <span class="badge"
                                                style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.45); color:<?= $badgeColor ?>; font-weight:700; border-radius:20px; padding:5px 10px; font-size:11px; backdrop-filter:blur(4px);">
                                                <?= $badgeText ?>
                                            </span>
                                            <span class="badge"
                                                style="position:absolute; top:12px; left:12px; background:<?= $tc['accent'] ?>dd; color:white; font-weight:700; border-radius:20px; padding:5px 10px; font-size:11px;">
                                                <?= htmlspecialchars($t['team_type']) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            style="height: 140px; background: <?= $tc['bg'] ?>; display: flex; align-items: center; justify-content: center; position: relative;">
                                            <i class="bi <?= $tc['icon'] ?>"
                                                style="font-size: 52px; color: <?= $tc['accent'] ?>; opacity: 0.85;"></i>
                                            <!-- Status Badge -->
                                            <span class="badge"
                                                style="position: absolute; top: 12px; right: 12px; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-weight: 700; border-radius: 20px; padding: 5px 10px; font-size: 11px;">
                                                <?= $badgeText ?>
                                            </span>
                                            <!-- Type Badge -->
                                            <span class="badge"
                                                style="position: absolute; top: 12px; left: 12px; background: <?= $tc['accent'] ?>22; color: <?= $tc['accent'] ?>; font-weight: 700; border-radius: 20px; padding: 5px 10px; font-size: 11px;">
                                                <?= htmlspecialchars($t['team_type']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <h5 style="font-weight: 700; margin-bottom: 10px;">
                                            <?= htmlspecialchars($t['team_name']) ?>
                                        </h5>
                                        <div style="font-size: 14px; color: #666; margin-bottom: 15px;">
                                            <div class="mb-1">
                                                <i class="bi bi-person-badge me-2" style="color: <?= $tc['accent'] ?>;"></i>
                                                <?= htmlspecialchars($t['team_lead'] ?: 'Unassigned') ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-people me-2" style="color: <?= $tc['accent'] ?>;"></i>
                                                <?= (int) $t['member_count'] ?>
                                                Member<?= $t['member_count'] != 1 ? 's' : '' ?>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-light w-100"
                                                style="font-weight: 600; border-radius: 8px; font-size: 13px;"
                                                onclick="viewTeamDetails(<?= $t['team_id'] ?>, '<?= htmlspecialchars(addslashes($t['team_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_type']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_lead'] ?: 'Unassigned'), ENT_QUOTES) ?>', <?= (int) $t['member_count'] ?>, '<?= $t['status'] ?>')">
                                                <i class="bi bi-eye me-1"></i> Details
                                            </button>
                                            <!-- Dropdown for actions -->
                                            <div class="dropdown">
                                                <button class="btn btn-light dropdown-toggle"
                                                    style="border-radius: 8px; padding: 6px 12px;" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end"
                                                    style="border-radius: 10px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1); font-size: 14px; min-width: 170px;">
                                                    <li>
                                                        <a class="dropdown-item" href="#" style="padding: 10px 16px;"
                                                            onclick="openEditModal(<?= $t['team_id'] ?>, '<?= htmlspecialchars(addslashes($t['team_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_type']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_lead'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($t['team_image'] ?? ''), ENT_QUOTES) ?>'); return false;">
                                                            <i class="bi bi-pencil-square me-2" style="color:#0d6efd;"></i>
                                                            Edit Team
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider" style="margin: 4px 0;">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="manage_teams.php?toggle=<?= $t['team_id'] ?>"
                                                            style="padding: 10px 16px;">
                                                            <?php if ($isActive): ?>
                                                                <i class="bi bi-pause-circle me-2 text-warning"></i> Deactivate
                                                            <?php else: ?>
                                                                <i class="bi bi-play-circle me-2 text-success"></i> Activate
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider" style="margin: 4px 0;">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                            style="padding: 10px 16px;"
                                                            onclick="confirmDelete(<?= $t['team_id'] ?>, '<?= htmlspecialchars(addslashes($t['team_name']), ENT_QUOTES) ?>'); return false;">
                                                            <i class="bi bi-archive me-2"></i> Archive Team
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

        </div><!-- /fc-content -->
    </div><!-- /fc-main -->
</div><!-- /fc-app -->

<!-- ===== Modal: Add Team ===== -->
            <div class="modal fade" id="addTeamModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content"
                        style="border-radius: 20px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="border: none; padding: 25px 25px 10px 25px;">
                            <h5 style="font-weight: 800; display: flex; align-items: center; gap: 10px; margin: 0;">
                                <span
                                    style="background: #ff2d2d; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">+</span>
                                Add New Team
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form method="POST" action="manage_teams.php" enctype="multipart/form-data">
                            <div class="modal-body" style="padding: 10px 25px 25px 25px;">
                                <hr style="opacity: 0.1; margin-bottom: 20px;">

                                <!-- Team Profile Image Upload -->
                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Profile
                                        Image</label>
                                    <div id="imageUploadArea"
                                        onclick="document.getElementById('teamImageInput').click()"
                                        style="border: 2px dashed #ddd; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; background: #fafafa; transition: all 0.2s;">
                                        <div id="imagePreviewWrapper" style="display:none; margin-bottom: 10px;">
                                            <img id="imagePreview" src="" alt="Preview"
                                                style="max-height: 120px; border-radius: 8px; object-fit: cover; max-width: 100%;">
                                        </div>
                                        <div id="imageUploadPlaceholder">
                                            <i class="bi bi-image" style="font-size: 28px; color: #ccc;"></i>
                                            <p style="margin: 8px 0 0; color: #aaa; font-size: 13px;">Click to upload
                                                team photo<br><small>JPG, PNG, WEBP · Max 2MB</small></p>
                                        </div>
                                    </div>
                                    <input type="file" id="teamImageInput" name="team_image"
                                        accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;"
                                        onchange="previewTeamImage(event)">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Name <span
                                            style="color: #ff2d2d;">*</span></label>
                                    <input type="text" name="team_name" class="form-control"
                                        placeholder="e.g. Alpha Medical Unit" required
                                        style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team Type <span
                                            style="color: #ff2d2d;">*</span></label>
                                    <select name="team_type" class="form-select" required
                                        style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                        <option value="" disabled selected>Select type</option>
                                        <option value="Fire">Fire</option>
                                        <option value="Medical Emergency">Medical Emergency</option>
                                        <option value="Crime / Security">Crime / Security</option>
                                        <option value="Vehicular Accident">Vehicular Accident</option>
                                        <option value="Flooding">Flooding</option>
                                        <option value="Earthquake">Earthquake</option>
                                        <option value="Missing Person">Missing Person</option>
                                        <option value="Power Outage">Power Outage</option>
                                        <option value="Landslide">Landslide</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team
                                        Lead</label>
                                    <input type="text" name="team_lead" class="form-control"
                                        placeholder="Lead's full name"
                                        style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
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
                    <div class="modal-content"
                        style="border-radius: 20px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="border: none; padding: 25px 25px 10px 25px;">
                            <h5 id="detailsModalTitle" style="font-weight: 800; margin: 0;">Team Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 10px 25px 25px 25px;">
                            <hr style="opacity: 0.1; margin-bottom: 20px;">
                            <div id="teamDetailsContent"></div>
                        </div>
                        <div class="modal-footer" style="border: none; padding: 0 25px 25px 25px;">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                                style="border-radius: 10px; font-weight: 600;">Close</button>
                            <a id="detailsToggleBtn" href="#" class="btn"
                                style="border-radius: 10px; font-weight: 600;"></a>
                            <a id="detailsDeleteBtn" href="#" class="btn btn-danger"
                                style="border-radius: 10px; font-weight: 600;"
                                onclick="return confirm('Archive this team? It will be hidden but data is preserved.');">
                                <i class="bi bi-archive me-1"></i> Archive
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Modal: Confirm Delete ===== -->
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content"
                        style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                        <div class="modal-body text-center" style="padding: 30px 25px;">
                            <div
                                style="width: 56px; height: 56px; background: #fff0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                                <i class="bi bi-archive" style="font-size: 24px; color: #ff2d2d;"></i>
                            </div>
                            <h6 style="font-weight: 800; margin-bottom: 8px;">Archive Team?</h6>
                            <p id="deleteConfirmText" style="color: #666; font-size: 14px; margin-bottom: 20px;"></p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light w-100" data-bs-dismiss="modal"
                                    style="border-radius: 10px; font-weight: 600;">Cancel</button>
                                <a id="deleteConfirmBtn" href="#" class="btn btn-danger w-100"
                                    style="border-radius: 10px; font-weight: 600;">Yes, Archive</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Modal: Edit Team ===== -->
            <div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content"
                        style="border-radius: 20px; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
                        <div class="modal-header" style="border: none; padding: 25px 25px 10px 25px;">
                            <h5 style="font-weight: 800; display: flex; align-items: center; gap: 10px; margin: 0;">
                                <span
                                    style="background: #0d6efd; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;"><i
                                        class="bi bi-pencil-fill"></i></span>
                                Edit Team
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form method="POST" action="manage_teams.php" enctype="multipart/form-data" id="editTeamForm">
                            <input type="hidden" name="edit_team_id" id="editTeamId">
                            <input type="hidden" name="remove_team_image" id="removeTeamImage" value="0">

                            <div class="modal-body" style="padding: 10px 25px 10px 25px;">
                                <hr style="opacity: 0.1; margin-bottom: 20px;">

                                <div class="row g-3">
                                    <!-- LEFT: Image Upload -->
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team
                                            Photo</label>
                                        <div id="editImageUploadArea"
                                            onclick="document.getElementById('editTeamImageInput').click()"
                                            style="border: 2px dashed #ddd; border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; background: #fafafa; transition: all 0.2s; position: relative; min-height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <img id="editImagePreview" src="" alt="Preview"
                                                style="display:none; width:100%; height:140px; object-fit:cover; border-radius:8px; margin-bottom:8px;">
                                            <div id="editImagePlaceholder">
                                                <i class="bi bi-camera" style="font-size: 32px; color: #ccc;"></i>
                                                <p style="margin: 6px 0 0; color: #aaa; font-size: 12px;">Click to
                                                    change photo<br><small>JPG, PNG, WEBP · Max 2MB</small></p>
                                            </div>
                                        </div>
                                        <input type="file" id="editTeamImageInput" name="edit_team_image"
                                            accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;"
                                            onchange="previewEditImage(event)">
                                        <button type="button" id="editRemoveImageBtn" onclick="removeEditImage()"
                                            style="display:none; margin-top:8px; width:100%; border-radius:8px; border:1.5px solid #eee; background:#fff; color:#ff2d2d; font-size:12px; font-weight:600; padding:6px;"
                                            class="btn">
                                            <i class="bi bi-x-circle me-1"></i> Remove Photo
                                        </button>
                                    </div>

                                    <!-- RIGHT: Fields -->
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team
                                                Name <span style="color:#ff2d2d;">*</span></label>
                                            <input type="text" name="edit_team_name" id="editTeamName"
                                                class="form-control" placeholder="e.g. Alpha Medical Unit" required
                                                style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team
                                                Type <span style="color:#ff2d2d;">*</span></label>
                                            <select name="edit_team_type" id="editTeamType" class="form-select" required
                                                style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                                <option value="" disabled>Select type</option>
                                                <option value="Fire">Fire</option>
                                                <option value="Medical Emergency">Medical Emergency</option>
                                                <option value="Crime / Security">Crime / Security</option>
                                                <option value="Vehicular Accident">Vehicular Accident</option>
                                                <option value="Flooding">Flooding</option>
                                                <option value="Earthquake">Earthquake</option>
                                                <option value="Missing Person">Missing Person</option>
                                                <option value="Power Outage">Power Outage</option>
                                                <option value="Landslide">Landslide</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight: 700; color: #1a1a1a;">Team
                                                Lead</label>
                                            <input type="text" name="edit_team_lead" id="editTeamLead"
                                                class="form-control" placeholder="Lead's full name"
                                                style="border-radius: 10px; border: 1.5px solid #eee; padding: 10px 14px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer" style="border: none; padding: 10px 25px 25px 25px; gap: 10px;">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                                    style="border-radius: 10px; font-weight: 600; padding: 10px 20px;">Cancel</button>
                                <button type="submit" name="edit_team" class="btn"
                                    style="background: #0d6efd; color: white; border-radius: 10px; font-weight: 700; padding: 10px 28px;">
                                    <i class="bi bi-save-fill me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


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
        deleteBtn.onclick = function () { return confirm(`Archive team "${name}"? The team will be hidden but can be restored later.`); };

        new bootstrap.Modal(document.getElementById('teamDetailsModal')).show();
    }

    // ── Confirm Delete ────────────────────────────────────────────────
    function confirmDelete(id, name) {
        document.getElementById('deleteConfirmText').textContent = `Archive "${name}"? The team will be hidden but data will be preserved.`;
        document.getElementById('deleteConfirmBtn').href = `manage_teams.php?delete=${id}`;
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }

    // ── Open Edit Modal ───────────────────────────────────────────────
    function openEditModal(id, name, type, lead, imagePath) {
        document.getElementById('editTeamId').value = id;
        document.getElementById('editTeamName').value = name;
        document.getElementById('editTeamLead').value = lead;
        document.getElementById('removeTeamImage').value = '0';

        // Set team type select
        const sel = document.getElementById('editTeamType');
        sel.value = type;

        // Handle existing image
        const preview = document.getElementById('editImagePreview');
        const placeholder = document.getElementById('editImagePlaceholder');
        const removeBtn = document.getElementById('editRemoveImageBtn');
        const area = document.getElementById('editImageUploadArea');

        if (imagePath) {
            preview.src = '../' + imagePath;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.style.display = 'block';
            area.style.borderColor = '#0d6efd';
            area.style.background = '#f0f5ff';
        } else {
            preview.src = '';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.style.flexDirection = 'column';
            placeholder.style.alignItems = 'center';
            removeBtn.style.display = 'none';
            area.style.borderColor = '#ddd';
            area.style.background = '#fafafa';
        }

        // Reset file input
        document.getElementById('editTeamImageInput').value = '';

        new bootstrap.Modal(document.getElementById('editTeamModal')).show();
    }

    // ── Preview new image in Edit modal ──────────────────────────────
    function previewEditImage(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('editImagePreview');
            const placeholder = document.getElementById('editImagePlaceholder');
            const removeBtn = document.getElementById('editRemoveImageBtn');
            const area = document.getElementById('editImageUploadArea');
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.style.display = 'block';
            area.style.borderColor = '#0d6efd';
            area.style.background = '#f0f5ff';
            document.getElementById('removeTeamImage').value = '0';
        };
        reader.readAsDataURL(file);
    }

    // ── Remove image in Edit modal ────────────────────────────────────
    function removeEditImage() {
        const preview = document.getElementById('editImagePreview');
        const placeholder = document.getElementById('editImagePlaceholder');
        const removeBtn = document.getElementById('editRemoveImageBtn');
        const area = document.getElementById('editImageUploadArea');
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
        placeholder.style.flexDirection = 'column';
        placeholder.style.alignItems = 'center';
        removeBtn.style.display = 'none';
        area.style.borderColor = '#ddd';
        area.style.background = '#fafafa';
        document.getElementById('editTeamImageInput').value = '';
        document.getElementById('removeTeamImage').value = '1';
    }

    // ── Image Preview (Add modal) ─────────────────────────────────────
    function previewTeamImage(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewWrapper').style.display = 'block';
            document.getElementById('imageUploadPlaceholder').style.display = 'none';
            document.getElementById('imageUploadArea').style.borderColor = '#ff2d2d';
            document.getElementById('imageUploadArea').style.background = '#fff8f8';
        };
        reader.readAsDataURL(file);
    }

    // ── Reset member rows + image when modal opens ────────────────────
    document.getElementById('addTeamModal').addEventListener('show.bs.modal', function () {
        document.getElementById('fcMemberRows').innerHTML = '';
        memberCount = 0;
        document.getElementById('teamImageInput').value = '';
        document.getElementById('imagePreview').src = '';
        document.getElementById('imagePreviewWrapper').style.display = 'none';
        document.getElementById('imageUploadPlaceholder').style.display = 'block';
        document.getElementById('imageUploadArea').style.borderColor = '#ddd';
        document.getElementById('imageUploadArea').style.background = '#fafafa';
    });

    function searchTeams() {
        let input = document.getElementById('searchTeamInput').value.toLowerCase();
        let cards = document.querySelectorAll('.team-card');

        cards.forEach(card => {
            // Find the team name inside the card (the <h5> tag text)
            let teamName = card.querySelector('.card-body h5').innerText.toLowerCase();

            if (teamName.includes(input)) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>