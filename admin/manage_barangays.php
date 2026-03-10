<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_barangay'])) {
    $name = sanitize($_POST['barangay_name'] ?? '');
    if (!$name) {
        $err = 'Barangay name is required.';
    } else {
        $chk = $conn->query("SELECT id FROM barangays WHERE name='$name'");
        if ($chk && $chk->num_rows > 0) {
            $err = "\"$name\" already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO barangays (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            logActivity($_SESSION['user_id'], "Added barangay: $name");
            $msg = "Barangay \"$name\" added.";
        }
    }
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int) $_POST['edit_id'];
    $name = sanitize($_POST['edit_name'] ?? '');
    if (!$name) {
        $err = 'Barangay name is required.';
    } else {
        $stmt = $conn->prepare("UPDATE barangays SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        logActivity($_SESSION['user_id'], "Updated barangay #$id: $name");
        $msg = "Barangay updated.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $used = (int) $conn->query("SELECT COUNT(*) c FROM incidents WHERE barangay_id=$id")->fetch_assoc()['c'];
    if ($used > 0) {
        $err = "Cannot delete — $used incident(s) reference this barangay.";
    } else {
        $conn->query("DELETE FROM barangays WHERE id=$id");
        logActivity($_SESSION['user_id'], "Deleted barangay #$id");
        $msg = "Barangay deleted.";
    }
}

// Search & pagination
$search = sanitize($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE '%$search%'" : '';
$per_page = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$total_count = (int) $conn->query("SELECT COUNT(*) c FROM barangays $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));

$barangays = $conn->query("
    SELECT b.*, COUNT(i.id) AS usage_count
    FROM barangays b
    LEFT JOIN incidents i ON b.id = i.barangay_id
    $where
    GROUP BY b.id
    ORDER BY b.name
    LIMIT $per_page OFFSET $offset
");
if (!$barangays)
    die("Query failed: " . $conn->error);

$all = [];
while ($r = $barangays->fetch_assoc())
    $all[] = $r;
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Barangays</div>
                    <div class="fc-breadcrumb">Admin / Manage Barangays</div>
                </div>
            </div>
            <div class="fc-topbar-right" style="display:flex;align-items:center;gap:12px;">
                <a href="dashboard.php" class="fc-bell-btn" title="Notifications" style="text-decoration:none;">
                    <i class="bi bi-bell-fill"></i>
                </a>
                <span style="font-size:13px;color:var(--fc-muted);font-family:'Lexend',sans-serif;">
                    <?= $total_count ?> total barangay<?= $total_count != 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?>
                <div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= $err ?></div><?php endif; ?>

            <div class="row g-4">

                <!-- ADD FORM -->
                <div class="col-lg-4">
                    <form method="POST">
                        <div class="fc-form-section">
                            <div class="fc-form-section-title">
                                <i class="bi bi-plus-circle-fill"></i> Add Barangay
                            </div>
                            <div class="mb-3">
                                <label class="fc-form-label">Barangay Name <span
                                        style="color:var(--fc-primary)">*</span></label>
                                <input type="text" name="barangay_name" class="fc-form-control" placeholder="e.g. Agdao"
                                    required>
                            </div>
                            <button type="submit" name="add_barangay" class="fc-btn fc-btn-primary"
                                style="width:100%;justify-content:center;">
                                <i class="bi bi-plus-circle-fill"></i> Add Barangay
                            </button>
                        </div>
                    </form>

                    <!-- Quick stats card -->
                    <div class="fc-form-section" style="margin-top:20px;">
                        <div class="fc-form-section-title"><i class="bi bi-bar-chart-fill"></i> Quick Stats</div>
                        <?php
                        $top = $conn->query("
                            SELECT b.name, COUNT(i.id) cnt
                            FROM barangays b
                            LEFT JOIN incidents i ON b.id = i.barangay_id
                            GROUP BY b.id
                            HAVING cnt > 0
                            ORDER BY cnt DESC LIMIT 5
                        ");
                        if ($top && $top->num_rows > 0):
                            $max_cnt = null;
                            $top_arr = [];
                            while ($t = $top->fetch_assoc()) {
                                $top_arr[] = $t;
                                if ($max_cnt === null)
                                    $max_cnt = $t['cnt'];
                            }
                            ?>
                            <div class="ir-section-label" style="margin-bottom:12px;">Most Reported Barangays</div>
                            <?php foreach ($top_arr as $t): ?>
                                <div style="margin-bottom:10px;">
                                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px;">
                                        <span
                                            style="font-weight:600;color:var(--fc-dark);"><?= htmlspecialchars($t['name']) ?></span>
                                        <span style="color:var(--fc-muted);"><?= $t['cnt'] ?>
                                            report<?= $t['cnt'] != 1 ? 's' : '' ?></span>
                                    </div>
                                    <div style="background:#f1f5f9;border-radius:100px;height:6px;overflow:hidden;">
                                        <div
                                            style="width:<?= round(($t['cnt'] / $max_cnt) * 100) ?>%;background:var(--fc-primary);height:100%;border-radius:100px;">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:var(--fc-muted);font-size:13px;">No incidents yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="col-lg-8">
                    <!-- Search -->
                    <form method="GET" style="margin-bottom:14px;display:flex;gap:10px;">
                        <div class="fc-search-wrapper" style="flex:1;margin:0;">
                            <i class="bi bi-search fc-search-icon"></i>
                            <input type="text" name="search" class="fc-search-input"
                                placeholder="Search barangay name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="fc-btn fc-btn-primary" style="padding:9px 16px;font-size:13px;">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if ($search): ?>
                            <a href="manage_barangays.php" class="fc-btn"
                                style="padding:9px 14px;font-size:13px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </form>

                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-geo-alt-fill" style="color:var(--fc-primary)"></i>
                                All Barangays
                                <span
                                    style="background:var(--fc-primary-lt);color:var(--fc-primary);padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;margin-left:6px;">
                                    <?= $total_count ?>
                                </span>
                            </div>
                        </div>

                        <?php if (empty($all)): ?>
                            <div class="fc-empty"><i class="bi bi-geo-alt"></i>
                                <h6>No barangays found</h6>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive" style="max-height:560px;overflow-y:auto;">
                                <table class="fc-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Barangay Name</th>
                                            <th>Incidents</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all as $b): ?>
                                            <tr>
                                                <td style="color:var(--fc-muted);font-size:11.5px;"><?= $b['id'] ?></td>
                                                <td>
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <i class="bi bi-geo-alt-fill"
                                                            style="color:var(--fc-primary);font-size:13px;"></i>
                                                        <span style="font-weight:600;color:var(--fc-dark);font-size:13px;">
                                                            <?= htmlspecialchars($b['name']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($b['usage_count'] > 0): ?>
                                                        <span
                                                            style="background:var(--fc-info-lt);color:var(--fc-info);padding:3px 10px;border-radius:100px;font-size:11.5px;font-weight:600;">
                                                            <?= $b['usage_count'] ?> incident<?= $b['usage_count'] != 1 ? 's' : '' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:var(--fc-muted);font-size:12px;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="display:flex;gap:6px;">
                                                        <button class="fc-icon-btn" title="Edit" data-bs-toggle="modal"
                                                            data-bs-target="#editBrgy<?= $b['id'] ?>">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <?php if ($b['usage_count'] == 0): ?>
                                                            <a href="?delete=<?= $b['id'] ?><?= $search ? "&search=$search" : '' ?>"
                                                                class="fc-icon-btn del" title="Delete"
                                                                onclick="return confirm('Delete barangay \"
                                                                <?= htmlspecialchars(addslashes($b['name'])) ?>\"?')">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="fc-icon-btn" style="opacity:.3;cursor:default;"
                                                                title="In use — cannot delete">
                                                                <i class="bi bi-lock-fill"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editBrgy<?= $b['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Barangay</h5>
                                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="edit_id" value="<?= $b['id'] ?>">
                                                                <label class="fc-form-label">Barangay Name</label>
                                                                <input type="text" name="edit_name" class="fc-form-control"
                                                                    value="<?= htmlspecialchars($b['name']) ?>" required>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="fc-btn"
                                                                    style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="fc-btn fc-btn-primary"><i
                                                                        class="bi bi-save-fill"></i> Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="fc-pagination" style="padding:14px 20px;border-top:1px solid var(--fc-border);">
                                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                                        class="fc-page-btn <?= $page <= 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                                            class="fc-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                                    <?php endfor; ?>
                                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                                        class="fc-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>"><i
                                            class="bi bi-chevron-right"></i></a>
                                    <span class="fc-page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>