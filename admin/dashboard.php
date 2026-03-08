<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$total    = totalIncidents();
$pending  = countByStatus('pending');
$active_r = $conn->query("SELECT COUNT(*) AS c FROM incidents WHERE status_id IN (2, 3)");
$active   = ($active_r) ? (int)$active_r->fetch_assoc()['c'] : 0;
$resolved = countByStatus('resolved');
$users    = totalUsers();
$teams    = totalTeams();

$recent = $conn->query("
    SELECT i.*, it.name AS type_name, b.name AS barangay,
           u.full_name AS reporter, t.team_name
    FROM incidents i
    LEFT JOIN incident_types it ON i.incident_type_id = it.id
    LEFT JOIN barangays       b  ON i.barangay_id     = b.id
    LEFT JOIN users           u  ON i.user_id         = u.user_id
    LEFT JOIN teams           t  ON i.assigned_team_id = t.team_id
    ORDER BY i.created_at DESC
    LIMIT 8
");
if (!$recent) die("Recent incidents query failed: " . $conn->error);

$log = $conn->query("
    SELECT al.*, u.full_name AS uname
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
");
if (!$log) die("Log query failed: " . $conn->error);

// --- Analytics data ---
// Incidents by type
$by_type = $conn->query("
    SELECT it.name, COUNT(i.id) AS cnt
    FROM incident_types it
    LEFT JOIN incidents i ON it.id = i.incident_type_id
    GROUP BY it.id ORDER BY cnt DESC LIMIT 6
");
$type_labels = $type_data = [];
while ($r = $by_type->fetch_assoc()) { $type_labels[] = $r['name']; $type_data[] = $r['cnt']; }

// Incidents per month (last 6 months)
$by_month = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS mo, COUNT(*) AS cnt
    FROM incidents
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY MIN(created_at) ASC
");
$month_labels = $month_data = [];
while ($r = $by_month->fetch_assoc()) { $month_labels[] = $r['mo']; $month_data[] = $r['cnt']; }

// Status distribution
$by_status = $conn->query("
    SELECT status_id, COUNT(*) cnt FROM incidents GROUP BY status_id
");
$status_names  = [1=>'Pending',2=>'Assigned',3=>'Responding',4=>'Resolved',5=>'Cancelled'];
$status_colors = [1=>'#f59e0b',2=>'#3b82f6',3=>'#8b5cf6',4=>'#10b981',5=>'#94a3b8'];
$st_labels = $st_data = $st_colors = [];
while ($r = $by_status->fetch_assoc()) {
    $st_labels[] = $status_names[$r['status_id']] ?? 'Unknown';
    $st_data[]   = $r['cnt'];
    $st_colors[] = $status_colors[$r['status_id']] ?? '#ccc';
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
                    <div class="fc-page-title">Admin Dashboard</div>
                    <div class="fc-breadcrumb">FlashCru / Control Center</div>
                </div>
            </div>
            <div class="fc-topbar-right">
                <!-- Print button -->
                <button onclick="window.print()" class="fc-btn no-print" style="background:#fff;border:1.5px solid var(--fc-border);color:var(--fc-text-2);font-size:13px;padding:8px 16px;">
                    <i class="bi bi-printer-fill"></i> Print Report
                </button>
            </div>
        </div>

        <div class="fc-content">

            <!-- Hero Banner -->
            <div style="background:linear-gradient(135deg,#0d1a2e,#1a0a09);border-radius:var(--fc-radius);padding:26px 30px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;position:relative;overflow:hidden;">
                <div style="position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:90px;opacity:.05;font-family:Lexend,sans-serif;font-weight:800;color:#fff;">FC</div>
                <div style="position:relative;z-index:1;">
                    <div style="font-family:Lexend,sans-serif;font-weight:800;font-size:19px;color:#fff;margin-bottom:4px;">FlashCru Control Center</div>
                    <div style="color:rgba(255,255,255,.45);font-size:12px;"><?= date('l, F j, Y \a\t g:i A') ?></div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;position:relative;z-index:1;no-print">
                    <a href="manage_reports.php" class="fc-btn fc-btn-primary no-print" style="font-size:13px;padding:10px 20px;">
                        <i class="bi bi-file-earmark-text-fill"></i> View Reports
                    </a>
                    <a href="manage_teams.php" class="fc-btn fc-btn-outline no-print" style="font-size:13px;padding:10px 20px;">
                        <i class="bi bi-people-fill"></i> Teams
                    </a>
                </div>
            </div>

            <!-- Stat cards row 1 -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-red"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                        <div class="fc-stat-val"><?= $total ?></div>
                        <div class="fc-stat-lbl">Total Incidents</div>
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
                        <div class="fc-stat-icon c-blu"><i class="bi bi-activity"></i></div>
                        <div class="fc-stat-val"><?= $active ?></div>
                        <div class="fc-stat-lbl">Active Response</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fc-stat-card">
                        <div class="fc-stat-icon c-grn"><i class="bi bi-patch-check-fill"></i></div>
                        <div class="fc-stat-val"><?= $resolved ?></div>
                        <div class="fc-stat-lbl">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Stat cards row 2 -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="fc-stat-card" style="border-left:4px solid var(--fc-info);">
                        <div style="display:flex;align-items:center;gap:16px;">
                            <div class="fc-stat-icon c-blu"><i class="bi bi-person-vcard-fill"></i></div>
                            <div>
                                <div class="fc-stat-val"><?= $users ?></div>
                                <div class="fc-stat-lbl">Registered Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fc-stat-card" style="border-left:4px solid var(--fc-success);">
                        <div style="display:flex;align-items:center;gap:16px;">
                            <div class="fc-stat-icon c-grn"><i class="bi bi-shield-fill-exclamation"></i></div>
                            <div>
                                <div class="fc-stat-val"><?= $teams ?></div>
                                <div class="fc-stat-lbl">Response Teams</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── ANALYTICS CHARTS ── -->
            <div class="row g-4 mb-4">
                <!-- Monthly trend -->
                <div class="col-lg-8">
                    <div class="fc-chart-card" style="padding:0;overflow:hidden;">
                        <div style="padding:22px 24px 0;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:4px;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--fc-muted);">Incidents Over Time</div>
                                <span id="monthlyTrendBadge" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;"></span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:2px;">
                                <span style="font-size:32px;font-weight:800;color:var(--fc-dark);"><?= $total ?></span>
                                <span style="font-size:13px;color:var(--fc-muted);font-weight:500;">Total cases</span>
                            </div>
                        </div>
                        <div style="padding:8px 0 0;">
                            <canvas id="monthlyChart" height="95"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Status donut -->
                <div class="col-lg-4">
                    <div class="fc-chart-card" style="display:flex;flex-direction:column;">
                        <div class="fc-chart-title"><i class="bi bi-pie-chart-fill" style="color:var(--fc-primary);margin-right:6px;"></i>By Status</div>
                        <div class="fc-chart-sub">Current status distribution</div>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Incidents by type -->
                <div class="col-12">
                    <div class="fc-chart-card">
                        <div class="fc-chart-title"><i class="bi bi-tags-fill" style="color:var(--fc-primary);margin-right:6px;"></i>Incidents by Type</div>
                        <div class="fc-chart-sub">Top incident categories</div>
                        <canvas id="typeChart" height="70"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents + Activity Log -->
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-8 d-flex flex-column" style="height:430px;">
                    <div class="fc-card" style="flex:1;">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-clock-history" style="color:var(--fc-primary)"></i> Latest Incidents
                            </div>
                            <a href="manage_reports.php" class="fc-btn fc-btn-primary no-print" style="font-size:11.5px;padding:6px 14px;">View All</a>
                        </div>
                        <div class="fc-log-scroll">
                            <?php if ($recent->num_rows === 0): ?>
                                <div class="fc-empty"><i class="bi bi-inbox"></i><h6>No Incidents Yet</h6></div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="fc-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th><th>Type</th><th>Reporter</th>
                                                <th>Barangay</th><th>Team</th><th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($r = $recent->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong style="color:var(--fc-primary)">#<?= $r['id'] ?></strong></td>
                                                    <td><span class="fc-pill"><?= htmlspecialchars($r['type_name']) ?></span></td>
                                                    <td><?= htmlspecialchars($r['reporter']) ?></td>
                                                    <td><?= htmlspecialchars($r['barangay']) ?></td>
                                                    <td>
                                                        <?php if ($r['team_name']): ?>
                                                            <span style="color:var(--fc-success);font-weight:500;"><?= htmlspecialchars($r['team_name']) ?></span>
                                                        <?php else: ?>
                                                            <span style="color:var(--fc-muted);">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= getStatusBadge($r['status_id']) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex flex-column" style="height:430px;">
                    <div class="fc-card" style="flex:1;">
                        <div class="fc-card-header">
                            <div class="fc-card-title">
                                <i class="bi bi-activity" style="color:var(--fc-success)"></i> Activity Log
                            </div>
                        </div>
                        <div class="fc-log-scroll">
                            <?php while ($l = $log->fetch_assoc()): ?>
                                <div class="fc-log-item">
                                    <div class="fc-log-dot"></div>
                                    <div>
                                        <div class="fc-log-action"><?= htmlspecialchars($l['action']) ?></div>
                                        <div class="fc-log-meta">
                                            <?= htmlspecialchars($l['uname'] ?? 'System') ?>
                                            &bull; <?= date('M d, g:i A', strtotime($l['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Lexend', sans-serif";
Chart.defaults.color = '#94a3b8';

// ── Monthly line chart ──
const monthlyData = <?= json_encode($month_data) ?>;
const monthlyLabels = <?= json_encode($month_labels) ?>;

// Trend badge
const last = monthlyData[monthlyData.length - 1] ?? 0;
const prev = monthlyData[monthlyData.length - 2] ?? 0;
const badge = document.getElementById('monthlyTrendBadge');
if (badge && monthlyData.length >= 2) {
    const pct = prev === 0 ? 100 : Math.round(((last - prev) / prev) * 100);
    const up = pct >= 0;
    badge.textContent = (up ? '↑ +' : '↓ ') + pct + '%';
    badge.style.background = up ? '#ecfdf5' : '#fff0f0';
    badge.style.color = up ? '#059669' : '#e61e1e';
}

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Incidents',
            data: monthlyData,
            borderColor: '#e61e1e',
            borderWidth: 2.5,
            pointBackgroundColor: '#e61e1e',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.45,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return 'rgba(230,30,30,0.08)';
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(230,30,30,0.18)');
                gradient.addColorStop(1, 'rgba(230,30,30,0.01)');
                return gradient;
            }
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                titleFont: { family: 'Lexend', size: 11 },
                bodyFont: { family: 'Lexend', size: 13, weight: '700' },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: { label: ctx => ctx.parsed.y + ' incidents' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, precision: 0, font: { size: 11 } },
                grid: { color: 'rgba(0,0,0,.04)' },
                border: { display: false }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } },
                border: { display: false }
            }
        }
    }
});

// ── Status donut ──
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($st_labels) ?>,
        datasets: [{
            data: <?= json_encode($st_data) ?>,
            backgroundColor: <?= json_encode($st_colors) ?>,
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, boxWidth: 12, font: { size: 12 } } }
        }
    }
});

// ── Incidents by type horizontal bar ──
new Chart(document.getElementById('typeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($type_labels) ?>,
        datasets: [{
            label: 'Incidents',
            data: <?= json_encode($type_data) ?>,
            backgroundColor: [
                'rgba(230,30,30,.75)','rgba(59,130,246,.75)','rgba(16,185,129,.75)',
                'rgba(245,158,11,.75)','rgba(139,92,246,.75)','rgba(148,163,184,.75)'
            ],
            borderRadius: 5, borderWidth: 0,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.04)' } },
            y: { grid: { display: false } }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>