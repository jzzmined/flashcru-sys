<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$msg = $err = '';

// Toggle user status
if (isset($_GET['toggle'])) {
    $uid    = (int)$_GET['toggle'];
    $newst  = $_GET['to'] === 'active' ? 'active' : 'inactive';
    $conn->query("UPDATE users SET status='$newst' WHERE user_id=$uid AND role='user'");
    logActivity($_SESSION['user_id'], "Set user #$uid status to $newst");
    $msg = "User status updated to " . ucfirst($newst) . ".";
}

// Delete user (only if no incidents)
if (isset($_GET['delete'])) {
    $uid  = (int)$_GET['delete'];
    $used = (int)$conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid")->fetch_assoc()['c'];
    if ($used > 0) {
        $err = "Cannot delete — this user has $used incident report(s). Deactivate them instead.";
    } else {
        $conn->query("DELETE FROM users WHERE user_id=$uid AND role=''");
        logActivity($_SESSION['user_id'], "Deleted user #$uid");
        $msg = "User deleted.";
    }
}



// Search & filter
$search  = sanitize($_GET['search'] ?? '');
$status_f = sanitize($_GET['status'] ?? '');

$where = "WHERE u.role = ''";
if ($search)   $where .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.contact_number LIKE '%$search%')";
if ($status_f) $where .= " AND u.status = '$status_f'";

// Pagination
$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$total_count = (int)$conn->query("SELECT COUNT(*) c FROM users u $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));

$users_res = $conn->query("
    SELECT u.*,
           COUNT(i.id) AS incident_count,
           MAX(i.created_at) AS last_report
    FROM users u
    LEFT JOIN incidents i ON u.user_id = i.user_id
    $where
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
");
if (!$users_res) die("Query failed: " . $conn->error);

$all_users = [];
while ($r = $users_res->fetch_assoc()) $all_users[] = $r;

// Counts for filter tabs
$cnt_active   = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='' AND status='active'")->fetch_assoc()['c'];
$cnt_inactive = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='' AND status='inactive'")->fetch_assoc()['c'];
$cnt_total    = $cnt_active + $cnt_inactive;
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Manage Users</div>
                    <div class="fc-breadcrumb">Admin / Registered Users</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <button onclick="window.print()" class="fc-btn no-print" style="background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text-2);font-size:13px;padding:8px 16px;">
                    <i class="bi bi-printer-fill"></i> Print
                </button>
            </div>
        </div>

        <div class="fc-content">

            <?php if ($msg): ?><div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div><?php endif; ?>
            <?php if ($err): ?><div class="fc-alert fc-alert-error"><i class="bi bi-x-circle-fill"></i> <?= $err ?></div><?php endif; ?>

            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-4">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-blu"><i class="bi bi-people-fill"></i></div>
                        <div class="fc-stat-val"><?= $cnt_total ?></div>
                        <div class="fc-stat-lbl">Total Users</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-grn"><i class="bi bi-person-check-fill"></i></div>
                        <div class="fc-stat-val"><?= $cnt_active ?></div>
                        <div class="fc-stat-lbl">Active</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-ylw"><i class="bi bi-person-dash-fill"></i></div>
                        <div class="fc-stat-val"><?= $cnt_inactive ?></div>
                        <div class="fc-stat-lbl">Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Search + filter bar -->
            <div class="fc-card" style="margin-bottom:20px;">
                <div style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <form method="GET" style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap;">
                        <div class="fc-search-wrapper" style="flex:1;min-width:200px;margin:0;">
                            <i class="bi bi-search fc-search-icon"></i>
                            <input type="text" name="search" class="fc-search-input"
                                   placeholder="Search name, email, phone..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="status" class="fc-form-control" style="width:150px;">
                            <option value="">All Status</option>
                            <option value="active"   <?= $status_f==='active'   ? 'selected':'' ?>>Active</option>
                            <option value="inactive" <?= $status_f==='inactive' ? 'selected':'' ?>>Inactive</option>
                        </select>
                        <button type="submit" class="fc-btn fc-btn-primary" style="padding:9px 18px;font-size:13px;">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <?php if ($search || $status_f): ?>
                        <a href="manage_users.php" class="fc-btn" style="padding:9px 18px;font-size:13px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Users table -->
            <div class="fc-card">
                <div class="fc-card-header">
                    <div class="fc-card-title">
                        <i class="bi bi-people-fill" style="color:var(--fc-primary)"></i>
                        Users
                        <span style="background:var(--fc-primary-lt);color:var(--fc-primary);padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;margin-left:6px;">
                            <?= $total_count ?>
                        </span>
                    </div>
                </div>

                <?php if (empty($all_users)): ?>
                <div class="fc-empty"><i class="bi bi-person-x"></i><h6>No users found</h6></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Reports</th>
                                <th>Last Report</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                            <tr>
                                <td style="color:var(--fc-muted);font-size:11.5px;">#<?= $u['user_id'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="ir-avatar" style="background:<?= $u['status']==='active' ? 'var(--fc-primary)' : '#94a3b8' ?>;">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13px;color:var(--fc-dark);"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div style="font-size:11.5px;color:var(--fc-muted);"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:12.5px;color:var(--fc-text);"><?= htmlspecialchars($u['contact_number'] ?? '—') ?></td>
                                <td>
                                    <span style="background:var(--fc-info-lt);color:var(--fc-info);padding:3px 10px;border-radius:100px;font-size:11.5px;font-weight:600;">
                                        <?= $u['incident_count'] ?> report<?= $u['incident_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--fc-muted);">
                                    <?= $u['last_report'] ? date('M d, Y', strtotime($u['last_report'])) : '—' ?>
                                </td>
                                <td style="font-size:12px;color:var(--fc-muted);white-space:nowrap;">
                                    <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge-status" style="background:var(--fc-success-lt);color:var(--fc-success);">
                                            <i class="bi bi-circle-fill" style="font-size:7px;"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status" style="background:#f1f5f9;color:#94a3b8;">
                                            <i class="bi bi-circle" style="font-size:7px;"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <?php if ($u['status'] === 'active'): ?>
                                            <a href="?toggle=<?= $u['user_id'] ?>&to=inactive<?= $search?"&search=$search":'' ?><?= $status_f?"&status=$status_f":'' ?>"
                                               class="fc-icon-btn" title="Deactivate"
                                               style="background:#fff7ed;border-color:#fed7aa;color:#f59e0b;"
                                               onclick="return confirm('Deactivate <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                                                <i class="bi bi-person-dash-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?toggle=<?= $u['user_id'] ?>&to=active<?= $search?"&search=$search":'' ?><?= $status_f?"&status=$status_f":'' ?>"
                                               class="fc-icon-btn" title="Activate"
                                               style="background:var(--fc-success-lt);border-color:#a7f3d0;color:var(--fc-success);"
                                               onclick="return confirm('Activate <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                                                <i class="bi bi-person-check-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="fc-icon-btn" title="View Details"
                                                onclick="openUserDetail(<?= $u['user_id'] ?>)"
                                                style="background:#eef2ff;border-color:#c7d2fe;color:#5b7cf7;">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <?php if ($u['incident_count'] == 0): ?>
                                        <a href="?delete=<?= $u['user_id'] ?><?= $search?"&search=$search":'' ?><?= $status_f?"&status=$status_f":'' ?>"
                                           class="fc-icon-btn del" title="Delete User"
                                           onclick="return confirm('Permanently delete <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="fc-icon-btn" style="opacity:.3;cursor:default;" title="Cannot delete — has reports">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="fc-pagination no-print" style="padding:16px 20px 14px;border-top:1px solid var(--fc-border);">
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_f ?>" class="fc-page-btn <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for ($p=1; $p<=$total_pages; $p++): ?>
                    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= $status_f ?>" class="fc-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_f ?>" class="fc-page-btn <?= $page>=$total_pages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                    <span class="fc-page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- User detail modal -->
<?php foreach ($all_users as $u):
    $inc_res = $conn->query("
        SELECT i.id, it.name AS type_name, b.name AS barangay, i.status_id, i.created_at
        FROM incidents i
        LEFT JOIN incident_types it ON i.incident_type_id = it.id
        LEFT JOIN barangays b ON i.barangay_id = b.id
        WHERE i.user_id = {$u['user_id']}
        ORDER BY i.created_at DESC LIMIT 5
    ");
    $status_labels = [1=>'Pending',2=>'Assigned',3=>'Responding',4=>'Resolved',5=>'Cancelled'];
    $status_colors = [1=>'#f59e0b',2=>'#3b82f6',3=>'#8b5cf6',4=>'#10b981',5=>'#94a3b8'];
?>
<div class="ir-modal-overlay no-print" id="userDetail<?= $u['user_id'] ?>" onclick="if(event.target===this){this.classList.remove('open');document.body.style.overflow=''}">
    <div class="ir-modal">
        <!-- Header strip -->
        <div style="background:linear-gradient(135deg,#0f172a,#1e293b);padding:28px 26px;position:relative;flex-shrink:0;">
            <button class="ir-modal-close" onclick="document.getElementById('userDetail<?= $u['user_id'] ?>').classList.remove('open');document.body.style.overflow=''">
                <i class="bi bi-x"></i>
            </button>
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:56px;height:56px;border-radius:50%;background:var(--fc-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;font-family:'Lexend',sans-serif;flex-shrink:0;">
                    <?= strtoupper(substr($u['full_name'],0,1)) ?>
                </div>
                <div>
                    <div style="font-family:'Lexend',sans-serif;font-weight:700;font-size:17px;color:#fff;"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;"><?= htmlspecialchars($u['email']) ?></div>
                    <span style="display:inline-block;margin-top:6px;padding:2px 10px;border-radius:100px;font-size:10.5px;font-weight:700;font-family:'Lexend',sans-serif;background:<?= $u['status']==='active'?'rgba(16,185,129,.2)':'rgba(148,163,184,.2)' ?>;color:<?= $u['status']==='active'?'#10b981':'#94a3b8' ?>;">
                        <?= ucfirst($u['status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="ir-modal-body">
            <div class="ir-section-label">User Information</div>
            <div class="ir-detail-row"><span>User ID</span><strong>#<?= $u['user_id'] ?></strong></div>
            <div class="ir-detail-row"><span>Full Name</span><strong><?= htmlspecialchars($u['full_name']) ?></strong></div>
            <div class="ir-detail-row"><span>Email</span><strong><?= htmlspecialchars($u['email']) ?></strong></div>
            <div class="ir-detail-row"><span>Phone</span><strong><?= htmlspecialchars($u['contact_number'] ?? '—') ?></strong></div>
            <div class="ir-detail-row"><span>Joined</span><strong><?= date('F j, Y', strtotime($u['created_at'])) ?></strong></div>
            <div class="ir-detail-row"><span>Total Reports</span>
                <strong style="color:var(--fc-primary);"><?= $u['incident_count'] ?></strong>
            </div>

            <?php if ($inc_res && $inc_res->num_rows > 0): ?>
            <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--fc-border);">
                <div class="ir-section-label">Recent Reports</div>
                <?php while ($ir = $inc_res->fetch_assoc()): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--fc-border-2);gap:10px;">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--fc-dark);"><?= htmlspecialchars($ir['type_name'] ?? '—') ?> — <?= htmlspecialchars($ir['barangay'] ?? '—') ?></div>
                        <div style="font-size:11px;color:var(--fc-muted);"><?= date('M d, Y', strtotime($ir['created_at'])) ?></div>
                    </div>
                    <span style="padding:3px 10px;border-radius:100px;font-size:10.5px;font-weight:700;background:<?= $status_colors[$ir['status_id']] ?>22;color:<?= $status_colors[$ir['status_id']] ?>;">
                        <?= $status_labels[$ir['status_id']] ?? '—' ?>
                    </span>
                </div>
                <?php endwhile; ?>
                <?php if ($u['incident_count'] > 5): ?>
                <div style="font-size:11.5px;color:var(--fc-muted);margin-top:8px;">
                    + <?= $u['incident_count'] - 5 ?> more report(s)
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--fc-border);display:flex;gap:10px;">
                <?php if ($u['status'] === 'active'): ?>
                <a href="?toggle=<?= $u['user_id'] ?>&to=inactive"
                   class="fc-btn fc-btn-danger" style="flex:1;justify-content:center;font-size:13px;"
                   onclick="return confirm('Deactivate this user?')">
                    <i class="bi bi-person-dash-fill"></i> Deactivate
                </a>
                <?php else: ?>
                <a href="?toggle=<?= $u['user_id'] ?>&to=active"
                   class="fc-btn fc-btn-success" style="flex:1;justify-content:center;font-size:13px;"
                   onclick="return confirm('Activate this user?')">
                    <i class="bi bi-person-check-fill"></i> Activate
                </a>
                <?php endif; ?>
                <button class="fc-btn" style="flex:1;justify-content:center;font-size:13px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);"
                        onclick="document.getElementById('userDetail<?= $u['user_id'] ?>').classList.remove('open');document.body.style.overflow=''">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function openUserDetail(id) {
    document.getElementById('userDetail' + id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
</script>

<?php include '../includes/footer.php'; ?>