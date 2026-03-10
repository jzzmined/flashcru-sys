<?php
require_once '../includes/auth_user.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$uid = (int) $_SESSION['user_id'];
$total    = (int) $conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid")->fetch_assoc()['c'];
$pending  = (int) $conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=1")->fetch_assoc()['c'];
$active   = (int) $conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id IN(2,3)")->fetch_assoc()['c'];
$resolved = (int) $conn->query("SELECT COUNT(*) c FROM incidents WHERE user_id=$uid AND status_id=4")->fetch_assoc()['c'];

// FIX: Added b.name AS barangay to the query so barangay column is available
$recent = $conn->query("
    SELECT i.*, it.name AS type_name, t.team_name AS team_name,
           b.name AS barangay
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    LEFT JOIN barangays       b  ON i.barangay_id = b.id
    WHERE i.user_id = $uid
    ORDER BY i.created_at DESC
    LIMIT 6
");
if (!$recent) die("Recent query failed: " . $conn->error);

// ── USER NOTIFICATIONS: Admin assigned a team OR changed status on user's reports ──
// Show up to 3 most recent updates where status is no longer Pending
$notif_q = $conn->query("
    SELECT i.id, it.name AS type_name, i.status_id,
           t.team_name, i.updated_at
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    WHERE i.user_id = $uid
      AND i.status_id IN (2, 3, 4, 5)
    ORDER BY i.updated_at DESC
    LIMIT 3
");
$notifs = [];
if ($notif_q) {
    while ($row = $notif_q->fetch_assoc()) {
        $msg   = '';
        $icon  = 'bi-bell-fill';
        $color = '#3b82f6';

        if ($row['status_id'] == 2 || $row['status_id'] == 3) {
            $team  = $row['team_name'] ? "<strong>" . htmlspecialchars($row['team_name']) . "</strong>" : "a response team";
            $label = $row['status_id'] == 2 ? 'Assigned' : 'Responding';
            $msg   = "Report <strong>#" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ")</strong> has been assigned to {$team}. Status: <strong>{$label}</strong>.";
            $icon  = 'bi-people-fill';
            $color = '#f59e0b';
        } elseif ($row['status_id'] == 4) {
            $msg   = "Report <strong>#" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ")</strong> has been marked as <strong>Resolved</strong>. Thank you for reporting!";
            $icon  = 'bi-check-circle-fill';
            $color = '#22c55e';
        } elseif ($row['status_id'] == 5) {
            $msg   = "Report <strong>#" . $row['id'] . " (" . htmlspecialchars($row['type_name']) . ")</strong> has been <strong>Cancelled</strong>.";
            $icon  = 'bi-x-circle-fill';
            $color = '#94a3b8';
        }

        if ($msg) {
            $notifs[] = [
                'msg'   => $msg,
                'icon'  => $icon,
                'color' => $color,
                'time'  => $row['updated_at'],
                'idx'   => count($notifs),
            ];
        }
    }
}

$hour      = (int) date('H');
$greeting  = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$firstname = explode(' ', $_SESSION['name'])[0];
?>
<?php include '../includes/header.php'; ?>



<div class="fc-app">
    <?php include '../includes/sidebar.php'; ?>

    <div class="fc-main">
        <!-- TOPBAR -->
        <div class="fc-topbar">
            <div class="fc-topbar-left">
                <button class="fc-menu-btn" onclick="fcToggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="fc-page-title">Dashboard</div>
                    <div class="fc-breadcrumb">User Dashboard</div>
                </div>
            </div>
            <div class="fc-topbar-right" >
                <div class="fc-bell-wrap" id="fcBellWrap">
                    <button class="fc-bell-btn" id="fcBellBtn" onclick="fcToggleBell(event)" title="Notifications">
                        <i class="bi bi-bell-fill"></i>
                        <?php if (!empty($notifs)): ?>
                        <span class="fc-bell-badge" id="fcBellBadge"><?= count($notifs) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="fc-bell-dropdown" id="fcBellDropdown">
                        <div class="fc-bell-header">
                            <span><i class="bi bi-bell-fill me-1"></i> Notifications</span>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php if (!empty($notifs)): ?><span class="fc-bell-count" id="fcBellCount"><?= count($notifs) ?> New</span><?php endif; ?>
                                <button class="fc-bell-clear-btn" id="fcClearBtn" onclick="fcClearNotifs()" title="Clear notifications">
                                    <i class="bi bi-trash3"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div class="fc-bell-body">
                            <?php if (empty($notifs)): ?>
                                <div class="fc-bell-empty"><i class="bi bi-bell-slash"></i><p>No new updates</p></div>
                            <?php else: ?>
                                <?php foreach ($notifs as $n): ?>
                                <div class="fc-bell-item">
                                    <div class="fc-bell-item-icon" style="background:<?= $n['color'] ?>22;color:<?= $n['color'] ?>">
                                        <i class="bi <?= $n['icon'] ?>"></i>
                                    </div>
                                    <div class="fc-bell-item-body">
                                        <div class="fc-bell-item-title"><?= $n['msg'] ?></div>
                                        <div class="fc-bell-item-time"><i class="bi bi-clock"></i> <?= date('M d, Y \a\t h:i A', strtotime($n['time'])) ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <a href="my_reports.php" class="fc-bell-footer">View all my reports <i class="bi bi-arrow-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /topbar -->

        <div class="fc-content">

<!-- Welcome Banner -->
            <div class="fc-welcome">
                <div class="fc-welcome-title"><?= $greeting ?>, <?= htmlspecialchars($firstname) ?>!</div>
                <div class="fc-welcome-sub">Stay safe. Report incidents. Let FlashCru handle the rest.</div>
                <a href="report_incident.php" class="fc-btn fc-btn-primary" style="font-size:13px;padding:10px 22px;">
                    <i class="bi bi-plus-circle-fill"></i> Report Incident
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-red"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="fc-stat-val" data-target="<?= $total ?>"><?= $total ?></div>
                        <div class="fc-stat-lbl">Total Reports</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-ylw"><i class="bi bi-clock-fill"></i></div>
                        <div class="fc-stat-val"><?= $pending ?></div>
                        <div class="fc-stat-lbl">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-blu"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="fc-stat-val"><?= $active ?></div>
                        <div class="fc-stat-lbl">Active Response</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-grn"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="fc-stat-val"><?= $resolved ?></div>
                        <div class="fc-stat-lbl">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports Table -->
            <div class="fc-card">
                <div class="fc-card-header">
                    <div class="fc-card-title">
                        <i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> Recent Reports
                    </div>
                    <a href="my_reports.php" class="fc-btn fc-btn-primary" style="font-size:12px;padding:7px 16px;">
                        View All
                    </a>
                </div>
                <div>
                    <?php if ($recent->num_rows === 0): ?>
                        <div class="fc-empty">
                            <i class="bi bi-file-earmark-x"></i>
                            <h6>No Reports Yet</h6>
                            <p style="font-size:13px;">You haven't submitted any incident reports.</p>
                            <a href="report_incident.php" class="fc-btn fc-btn-primary"
                                style="margin-top:8px;font-size:13px;">
                                Report Now
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="fc-log-scroll"></div>
                        <div class="table-responsive">
                            <table class="fc-table">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Type</th>
                                        <th>Barangay</th>
                                        <th>Team Assigned</th>
                                        <th>Status</th>
                                        <th>Date Filed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $recent->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                            <td><span class="fc-pill"><i class="bi bi-tag-fill"></i><?= htmlspecialchars($r['type_name']) ?></span></td>
                                            <td><?= htmlspecialchars($r['barangay'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if ($r['team_name']): ?>
                                                    <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                                <?php else: ?>
                                                    <span style="color:var(--fc-muted);font-size:12px;">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= getStatusBadge($r['status_id'] ?? 1) ?></td>
                                            <td style="color:var(--fc-muted);font-size:12.5px;white-space:nowrap;">
                                                <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /fc-content -->
</div><!-- /fc-main -->
</div><!-- /fc-app -->


<script>
(function() {
    var seenKey = 'fc_user_seen_<?= $uid ?>';
    var currentIds = '<?= implode(",", array_column($notifs, "time")) ?>';

    function hideBadge() {
        var badge = document.getElementById('fcBellBadge');
        var count = document.getElementById('fcBellCount');
        if (badge) badge.style.display = 'none';
        if (count) count.style.display = 'none';
    }

    function markSeen() {
        localStorage.setItem(seenKey, currentIds);
        hideBadge();
    }

    function closeDropdown() {
        document.getElementById('fcBellDropdown').classList.remove('open');
        var bd = document.getElementById('fcBellBackdrop');
        if (bd) bd.classList.remove('open');
    }

    window.fcToggleBell = function(e) {
        e.stopPropagation();
        var dropdown = document.getElementById('fcBellDropdown');
        var bd = document.getElementById('fcBellBackdrop');
        var isOpen = dropdown.classList.contains('open');
        if (isOpen) {
            closeDropdown();
        } else {
            dropdown.classList.add('open');
            if (bd) bd.classList.add('open');
            markSeen();
        }
    };

    window.fcClearNotifs = function() {
        localStorage.setItem('fc_user_cleared_<?= $uid ?>', currentIds);
        var body = document.querySelector('#fcBellDropdown .fc-bell-body');
        if (body) body.innerHTML = '<div class="fc-bell-empty"><i class="bi bi-bell-slash"></i><p>No new updates</p></div>';
        var badge = document.getElementById('fcBellBadge');
        var count = document.getElementById('fcBellCount');
        var clearBtn = document.getElementById('fcClearBtn');
        if (badge) badge.style.display = 'none';
        if (count) count.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem(seenKey) === currentIds) {
            hideBadge();
        }
        // Hide clear btn if already cleared
        if (localStorage.getItem('fc_user_cleared_<?= $uid ?>') === currentIds) {
            var clearBtn = document.getElementById('fcClearBtn');
            if (clearBtn) clearBtn.style.display = 'none';
        }
        var bd = document.getElementById('fcBellBackdrop');
        if (bd) bd.addEventListener('click', closeDropdown);
    });
})();
</script>


<script>
(function() {
    function pollUserNotifs() {
        fetch('get_user_notifs.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var badge = document.getElementById('fcBellBadge');
                var countEl = document.getElementById('fcBellCount');
                var body = document.querySelector('#fcBellDropdown .fc-bell-body');
                var seenKey = 'fc_user_seen_<?= $uid ?>';
                var alreadySeen = localStorage.getItem(seenKey) === data.ids;

                // Update badge
                if (data.count > 0 && !alreadySeen) {
                    if (badge) { badge.textContent = data.count; badge.style.display = 'flex'; }
                    if (countEl) { countEl.textContent = data.count + ' New'; countEl.style.display = ''; }
                } else {
                    if (badge) badge.style.display = 'none';
                    if (countEl) countEl.style.display = 'none';
                }

                // Re-render dropdown body
                if (body) {
                    if (data.items.length === 0) {
                        body.innerHTML = '<div class="fc-bell-empty"><i class="bi bi-bell-slash"></i><p>No new updates</p></div>';
                    } else {
                        var html = '';
                        data.items.forEach(function(n) {
                            html += '<div class="fc-bell-item">' +
                                '<div class="fc-bell-item-icon" style="background:' + n.color + '22;color:' + n.color + '"><i class="bi ' + n.icon + '"></i></div>' +
                                '<div class="fc-bell-item-body">' +
                                    '<div class="fc-bell-item-title">' + n.msg + '</div>' +
                                    '<div class="fc-bell-item-time"><i class="bi bi-clock"></i> ' + n.time + '</div>' +
                                '</div></div>';
                        });
                        html += '<a href="my_reports.php" class="fc-bell-footer">View all my reports <i class="bi bi-arrow-right"></i></a>';
                        // Show clear button if not cleared
                        var cb = document.getElementById('fcClearBtn');
                        if (cb && localStorage.getItem('fc_user_cleared_<?= $uid ?>') !== data.ids) cb.style.display = '';
                        body.innerHTML = html;
                    }
                }
            })
            .catch(function(){});
    }

    document.addEventListener('DOMContentLoaded', function() {
        pollUserNotifs();
        setInterval(pollUserNotifs, 30000);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>