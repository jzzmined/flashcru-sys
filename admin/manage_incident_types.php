<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';

// Add type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $name = sanitize($_POST['type_name']    ?? '');
    $desc = sanitize($_POST['description']  ?? '');

    if (!$name) {
        $err = 'Type name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO incident_types (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $msg = "Incident type \"$name\" added.";
        } else {
            $err = 'Failed to add type.';
        }
    }
}

// Edit type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id   = (int)$_POST['edit_id'];
    $name = sanitize($_POST['edit_name'] ?? '');
    $desc = sanitize($_POST['edit_desc'] ?? '');
    $conn->query("UPDATE incident_types SET name='$name' WHERE id=$id");
    $msg = "Incident type updated.";
}

// Delete type
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $used = $conn->query("SELECT COUNT(*) c FROM incidents WHERE incident_type_id=$id")->fetch_assoc()['c'];
    if ($used > 0) {
        $err = 'Cannot delete — this type is used in existing incidents.';
    } else {
        $conn->query("DELETE FROM incident_types WHERE id=$id");
        $msg = 'Incident type deleted.';
    }
}

$types = $conn->query("
    SELECT it.*, COUNT(i.id) AS usage_count
    FROM incident_types it
    LEFT JOIN incidents i ON it.id = i.incident_type_id
    GROUP BY it.id
    ORDER BY it.name
");
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()" style="display:block;"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Incident Types</div>
                    <div class="fc-breadcrumb">Admin / Manage Incident Types</div>
                </div>
            </div>
            <div class="fc-topbar-right"></div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?><div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div><?php endif; ?>
            <?php if ($err): ?><div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= $err ?></div><?php endif; ?>

            <div class="row g-4">
                <!-- ADD FORM -->
                <div class="col-lg-4">
                    <form method="POST">
                        <div class="fc-form-section">
                            <div class="fc-form-section-title">
                                <i class="bi bi-plus-circle-fill"></i> Add Incident Type
                            </div>
                            <div class="mb-3">
                                <label class="fc-form-label">Type Name <span style="color:var(--fc-primary)">*</span></label>
                                <input type="text" name="type_name" class="fc-form-control"
                                       placeholder="e.g. Road Accident" required>
                            </div>
                            <div class="mb-4">
                                <label class="fc-form-label">Description</label>
                                <textarea name="description" class="fc-form-control" rows="3"
                                          placeholder="Brief description of this incident type..."></textarea>
                            </div>
                            <button type="submit" name="add_type" class="fc-btn fc-btn-primary" style="width:100%;">
                                <i class="bi bi-plus-circle-fill"></i> Add Type
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TYPES TABLE -->
                <div class="col-lg-8">
                    <div class="fc-card">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-tags-fill" style="color:var(--fc-primary)"></i>
                                All Incident Types (<?= $types->num_rows ?>)
                            </div>
                        </div>
                        <?php if ($types->num_rows === 0): ?>
                        <div class="fc-empty"><i class="bi bi-tags"></i><h6>No Types Yet</h6></div>
                        <?php else: ?>
                        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                            <table class="fc-table">
                                <thead>
                                    <tr>
                                        <th>Type Name</th><th>Description</th>
                                        <th>Usage</th><th>Added</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($it = $types->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="fc-pill">
                                                <i class="bi bi-tag-fill"></i>
                                                <?= htmlspecialchars($it['name']) ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--fc-muted);max-width:220px;font-size:12.5px;">
                                            <?= htmlspecialchars($it['description'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <span style="background:#eef2ff;color:#5b7cf7;padding:3px 10px;border-radius:100px;font-size:11.5px;font-weight:600;">
                                                <?= $it['usage_count'] ?> incident<?= $it['usage_count'] != 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--fc-muted);font-size:12px;white-space:nowrap;">
                                            <?= $it['created_at'] ? date('M d, Y', strtotime($it['created_at'])) : '—' ?>
                                        </td>
                                        <td style="display:flex;gap:6px;">
                                            <!-- Edit -->
                                            <button class="fc-icon-btn" data-bs-toggle="modal"
                                                    data-bs-target="#editType<?= $it['id'] ?>"
                                                    title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <!-- Delete (only if unused) -->
                                            <?php if ($it['usage_count'] == 0): ?>
                                            <a href="?delete=<?= $it['id'] ?>" class="fc-icon-btn del"
                                               onclick="return fcConfirm('Delete this incident type?')"
                                               title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            <?php else: ?>
                                            <span class="fc-icon-btn" style="cursor:default;opacity:.4;" title="In use — cannot delete">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editType<?= $it['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Incident Type</h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_id" value="<?= $it['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="fc-form-label">Type Name</label>
                                                            <input type="text" name="edit_name" class="fc-form-control"
                                                                   value="<?= htmlspecialchars($it['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="fc-form-label">Description</label>
                                                            <textarea name="edit_desc" class="fc-form-control" rows="3"><?= htmlspecialchars($it['description'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="fc-btn" style="background:#fff;color:var(--fc-text);border:1.5px solid var(--fc-border);" data-bs-dismiss="modal">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="fc-btn fc-btn-primary">
                                                            <i class="bi bi-save-fill"></i> Save
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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