<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$depth   = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$base    = str_repeat('../', max(0, $depth));
$role    = $_SESSION['role']   ?? 'user';
$uname   = $_SESSION['name']   ?? 'User';
$current = basename($_SERVER['PHP_SELF']);
$folder  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- SIDEBAR -->
<aside class="fc-sidebar" id="fcSidebar">
    <!-- Brand -->
    <div class="fc-sidebar-brand">
        <div class="fc-brand-icon">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <div>
            <div class="fc-brand-name">FlashCru</div>
            <div class="fc-brand-sub">Response System</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="fc-sidebar-nav">

        <?php if ($role === 'admin'): ?>
        <!-- ADMIN NAV -->
        <div class="fc-nav-label">MAIN</div>
        <a href="<?= $base ?>admin/dashboard.php"
           class="fc-nav-item <?= ($current === 'dashboard.php' && $folder === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="fc-nav-label">MANAGEMENT</div>
        <a href="<?= $base ?>admin/manage_reports.php"
           class="fc-nav-item <?= $current === 'manage_reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text-fill"></i> Incident Reports
        </a>
        <a href="<?= $base ?>admin/manage_teams.php"
           class="fc-nav-item <?= $current === 'manage_teams.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Response Teams
        </a>
        <a href="<?= $base ?>admin/manage_incident_types.php"
           class="fc-nav-item <?= $current === 'manage_incident_types.php' ? 'active' : '' ?>">
            <i class="bi bi-tags-fill"></i> Incident Types
        </a>

        <?php else: ?>
        <!-- USER NAV -->
        <div class="fc-nav-label">MAIN</div>
        <a href="<?= $base ?>user/dashboard.php"
           class="fc-nav-item <?= ($current === 'dashboard.php' && $folder === 'user') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="fc-nav-label">REPORTS</div>
        <a href="<?= $base ?>user/report_incident.php"
           class="fc-nav-item <?= $current === 'report_incident.php' ? 'active' : '' ?>">
            <i class="bi bi-exclamation-triangle-fill"></i> Report Incident
        </a>
        <a href="<?= $base ?>user/my_reports.php"
           class="fc-nav-item <?= $current === 'my_reports.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> My Reports
        </a>
        <?php endif; ?>

        <div class="fc-nav-label">ACCOUNT</div>
        <a href="<?= $base ?>logout.php" class="fc-nav-item fc-nav-danger">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </nav>

    <!-- Sidebar footer: current user -->
    <div class="fc-sidebar-user">
        <div class="fc-user-avatar"><?= strtoupper(substr($uname, 0, 1)) ?></div>
        <div class="fc-user-info">
            <div class="fc-user-name"><?= htmlspecialchars($uname) ?></div>
            <div class="fc-user-role"><?= ucfirst($role) ?></div>
        </div>
    </div>
</aside>

<!-- Mobile overlay -->
<div class="fc-overlay" id="fcOverlay" onclick="fcCloseSidebar()"></div>