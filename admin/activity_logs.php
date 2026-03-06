<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Clear logs (admin only)
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $conn->query("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    logActivity($_SESSION['user_id'], "Cleared activity logs older than 30 days");
    header("Location: activity_logs.php?msg=cleared");
    exit;
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'cleared') {
    $msg = 'Logs older than 30 days have been cleared.';
}

// Filters
$search    = sanitize($_GET['search']  ?? '');
$user_f    = sanitize($_GET['user_id'] ?? '');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to   = sanitize($_GET['date_to']   ?? '');

$where = "WHERE 1=1";
if ($search)    $where .= " AND (al.action LIKE '%$search%')";
if ($user_f)    $where .= " AND al.user_id = " . (int)$user_f;
if ($date_from) $where .= " AND DATE(al.created_at) >= '$date_from'";
if ($date_to)   $where .= " AND DATE(al.created_at) <= '$date_to'";

// Pagination
$per_page    = 20;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;
$total_count = (int)$conn->query("SELECT COUNT(*) c FROM activity_log al $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));

$logs = $conn->query("
    SELECT al.*, u.full_name, u.role AS user_role
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    $where
    ORDER BY al.created_at DESC
    LIMIT $per_page OFFSET $offset
");
if (!$logs) die("Query failed: " . $conn->error);

// Get all users for filter dropdown
$users_dd = $conn->query("SELECT user_id, full_name, role FROM users ORDER BY full_name");

// Log category icon/color helper
function logStyle($action) {
    $a = strtolower($action);
    if (str_contains($a,'login'))    return ['icon'=>'bi-box-arrow-in-right','color'=>'#3b82f6','bg'=>'rgba(59,130,246,.1)'];
    if (str_contains($a,'delete') || str_contains($a,'deleted')) return ['icon'=>'bi-trash-fill','color'=>'#ef4444','bg'=>'rgba(239,68,68,.1)'];
    if (str_contains($a,'created') || str_contains($a,'added')) return ['icon'=>'bi-plus-circle-fill','color'=>'#10b981','bg'=>'rgba(16,185,129,.1)'];
    if (str_contains($a,'updated') || str_contains($a,'update')) return ['icon'=>'bi-pencil-fill','color'=>'#f59e0b','bg'=>'rgba(245,158,11,.1)'];
    if (str_contains($a,'register')) return ['icon'=>'bi-person-plus-fill','color'=>'#8b5cf6','bg'=>'rgba(139,92,246,.1)'];
    if (str_contains($a,'cancel'))  return ['icon'=>'bi-x-circle-fill','color'=>'#94a3b8','bg'=>'rgba(148,163,184,.1)'];
    if (str_contains($a,'submit') || str_contains($a,'report')) return ['icon'=>'bi-file-earmark-text-fill','color'=>'#e61e1e','bg'=>'rgba(230,30,30,.1)'];
    return ['icon'=>'bi-activity','color'=>'#94a3b8','bg'=>'rgba(148,163,184,.1)'];
}
?>
<?php include '../includes/header.php'; ?>

<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()"><i class="bi bi-list"></i></button>
                <div>
                    <div class="fc-page-title">Activity Logs</div>
                    <div class="fc-breadcrumb">Admin / System Activity</div>
                </div>
            </div>
            <div class="fc-topbar-right no-print">
                <button onclick="window.print()" class="fc-btn" style="background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text-2);font-size:13px;padding:8px 16px;">
                    <i class="bi bi-printer-fill"></i> Print
                </button>
                <a href="?clear=1" class="fc-btn fc-btn-danger" style="font-size:13px;padding:8px 16px;"
                   onclick="return confirm('Clear logs older than 30 days?')">
                    <i class="bi bi-trash-fill"></i> Clear Old Logs
                </a>
            </div>
        </div>

        <div class="fc-content">
            <?php if ($msg): ?><div class="fc-alert fc-alert-success"><i class="bi bi-check-circle-fill"></i> <?= $msg ?></div><?php endif; ?>

            <!-- Filter bar -->
            <div class="fc-card" style="margin-bottom:20px;">
                <div style="padding:16px 20px;">
                    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                        <div style="flex:1;min-width:200px;">
                            <label class="fc-form-label">Search Action</label>
                            <div class="fc-search-wrapper" style="width:100%;margin:0;">
                                <i class="bi bi-search fc-search-icon"></i>
                                <input type="text" name="search" class="fc-search-input"
                                       placeholder="Search log actions..."
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div style="min-width:180px;">
                            <label class="fc-form-label">Filter by User</label>
                            <select name="user_id" class="fc-form-control">
                                <option value="">All Users</option>
                                <?php while ($u = $users_dd->fetch_assoc()): ?>
                                <option value="<?= $u['user_id'] ?>" <?= $user_f==$u['user_id']?'selected':'' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?> (<?= ucfirst($u['role']) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="fc-form-label">Date From</label>
                            <input type="date" name="date_from" class="fc-form-control"
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div>
                            <label class="fc-form-label">Date To</label>
                            <input type="date" name="date_to" class="fc-form-control"
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div style="display:flex;gap:8px;align-self:flex-end;">
                            <button type="submit" class="fc-btn fc-btn-primary" style="padding:10px 18px;font-size:13px;">
                                <i class="bi bi-funnel-fill"></i> Filter
                            </button>
                            <a href="activity_logs.php" class="fc-btn" style="padding:10px 14px;font-size:13px;background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text);">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Log table -->
            <div class="fc-card">
                <div class="fc-card-header">
                    <div class="fc-card-title">
                        <i class="bi bi-journal-text" style="color:var(--fc-primary)"></i>
                        Activity Log
                        <span style="background:var(--fc-primary-lt);color:var(--fc-primary);padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;margin-left:6px;">
                            <?= $total_count ?>
                        </span>
                    </div>
                    <span style="font-size:12px;color:var(--fc-muted);">
                        Page <?= $page ?> of <?= $total_pages ?>
                    </span>
                </div>

                <?php if ($logs->num_rows === 0): ?>
                <div class="fc-empty"><i class="bi bi-journal-x"></i><h6>No logs found</h6></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Action</th>
                                <th>Performed By</th>
                                <th>Role</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($l = $logs->fetch_assoc()):
                                $s = logStyle($l['action']);
                            ?>
                            <tr>
                                <td style="color:var(--fc-muted);font-size:11.5px;">#<?= $l['log_id'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:30px;height:30px;border-radius:8px;background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">
                                            <i class="bi <?= $s['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13px;color:var(--fc-dark);"><?= htmlspecialchars($l['action']) ?></div>
                                            <?php if (!empty($l['description'])): ?>
                                            <div style="font-size:11.5px;color:var(--fc-muted);margin-top:2px;"><?= htmlspecialchars($l['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($l['full_name']): ?>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="ir-avatar" style="width:26px;height:26px;font-size:10px;">
                                            <?= strtoupper(substr($l['full_name'],0,1)) ?>
                                        </div>
                                        <span style="font-size:13px;font-weight:500;"><?= htmlspecialchars($l['full_name']) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span style="color:var(--fc-muted);">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($l['user_role']): ?>
                                    <span style="background:<?= $l['user_role']==='admin'?'rgba(230,30,30,.1)':'rgba(59,130,246,.1)' ?>;color:<?= $l['user_role']==='admin'?'var(--fc-primary)':'var(--fc-info)' ?>;padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;">
                                        <?= ucfirst($l['user_role']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="color:var(--fc-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px;color:var(--fc-muted);"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></td>
                                <td style="font-size:12px;color:var(--fc-muted);white-space:nowrap;">
                                    <div><?= date('M d, Y', strtotime($l['created_at'])) ?></div>
                                    <div style="color:var(--fc-muted);opacity:.7;"><?= date('g:i A', strtotime($l['created_at'])) ?></div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="fc-pagination no-print" style="padding:14px 20px;border-top:1px solid var(--fc-border);">
                    <?php
                    $qstr = http_build_query(array_filter(['search'=>$search,'user_id'=>$user_f,'date_from'=>$date_from,'date_to'=>$date_to]));
                    ?>
                    <a href="?page=<?= $page-1 ?>&<?= $qstr ?>" class="fc-page-btn <?= $page<=1?'disabled':'' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php for ($p=1; $p<=$total_pages; $p++): ?>
                    <a href="?page=<?= $p ?>&<?= $qstr ?>" class="fc-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= $page+1 ?>&<?= $qstr ?>" class="fc-page-btn <?= $page>=$total_pages?'disabled':'' ?>"><i class="bi bi-chevron-right"></i></a>
                    <span class="fc-page-info">Showing <?= min($offset+1,$total_count) ?>–<?= min($offset+$per_page,$total_count) ?> of <?= $total_count ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>