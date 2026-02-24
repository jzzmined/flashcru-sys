<?php
/**
 * FlashCru — Shared Sidebar v4.0
 * Collapsible, modern civic-tech style
 */
$current_page = basename($_SERVER['PHP_SELF']);

$nav_sections = [
  'Main' => [
    ['dashboard.php',       '🏠', 'Dashboard',       null],
    ['incidents.php',       '🚨', 'Incidents',        null],
    ['teams.php',           '👥', 'Teams',            null],
  ],
  'Reports' => [
    ['report_incident.php', '📝', 'Report Incident',  null],
    ['my_reports.php',      '📋', 'My Reports',       null],
    ['reports.php',         '📊', 'Analytics',        null],
  ],
  'System' => [
    ['settings.php',        '⚙️',  'Settings',        null],
  ],
];
?>
<aside class="sidebar" id="fcSidebar">
  <div class="sidebar-bg-accent"></div>

  <!-- Toggle btn -->
  <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">‹</button>

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">⚡</div>
    <div class="sidebar-logo-text">
      <div class="logo-name">FlashCru</div>
      <div class="logo-sub">Emergency Response</div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">
    <?php foreach ($nav_sections as $section => $items): ?>
    <div class="nav-section-label"><?php echo $section; ?></div>
    <?php foreach ($items as [$page, $icon, $label, $badge]):
      $active = ($current_page === $page);
    ?>
    <a href="<?php echo $page; ?>"
       class="nav-item<?php echo $active ? ' active' : ''; ?>"
       title="<?php echo htmlspecialchars($label); ?>">
      <span class="nav-icon"><?php echo $icon; ?></span>
      <span class="nav-label"><?php echo htmlspecialchars($label); ?></span>
      <?php if ($badge): ?>
      <span class="nav-badge"><?php echo $badge; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <!-- User + Logout -->
  <div class="sidebar-user">
    <div class="sidebar-user-row">
      <div class="user-avatar">
        <?php echo strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2)); ?>
      </div>
      <div class="sidebar-user-text">
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?></div>
        <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'Administrator'); ?></div>
      </div>
    </div>
    <a href="logout.php" class="logout-btn" title="Log Out">
      <span class="nav-icon">🚪</span>
      <span class="nav-label">Log Out</span>
    </a>
  </div>
  </aside>