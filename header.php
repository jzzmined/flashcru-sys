<?php
/**
 * FlashCru — Shared Header/Topbar v4.0
 */
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
  'dashboard.php'       => ['Dashboard',       'FlashCru / Dashboard'],
  'incidents.php'       => ['Incidents',        'FlashCru / Incidents'],
  'teams.php'           => ['Teams',            'FlashCru / Teams'],
  'reports.php'         => ['Analytics',        'FlashCru / Analytics'],
  'settings.php'        => ['Settings',         'FlashCru / Settings'],
  'report_incident.php' => ['Report Incident',  'FlashCru / Report Incident'],
  'my_reports.php'      => ['My Reports',       'FlashCru / My Reports'],
];
$title_info   = $page_titles[$current_page] ?? ['Dashboard', 'FlashCru'];
$user_initials = strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2));
?>
<header class="topbar">
  <!-- Left -->
  <div class="topbar-left">
    <div class="page-title"><?php echo $title_info[0]; ?></div>
    <div class="breadcrumb"><?php echo $title_info[1]; ?></div>
  </div>

  <!-- Right -->
  <div class="topbar-right">
    <!-- Clock -->
    <span id="fcClock" class="header-clock"></span>

    <!-- Notification -->
    <div style="position:relative;">
      <button id="fcNotifToggle"
              class="notif-btn"
              onclick="toggleNotifPanel()"
              title="Notifications">
        🔔
        <span id="fcNotifDot" class="notif-dot"></span>
      </button>

      <div id="fcNotifPanel" class="notif-panel">
        <div class="notif-header">
          <span class="notif-title">🔔 Notifications</span>
          <button class="notif-clear" onclick="clearNotifications()">Clear all</button>
        </div>
        <div id="fcNotifList">
          <div class="notif-row" onclick="location.href='incidents.php'">
            <span class="notif-dot-sm" style="background:var(--primary);"></span>
            <div>
              <div class="notif-text">🚨 Critical: Building Fire — J.P. Laurel Ave</div>
              <div class="notif-time">2 minutes ago</div>
            </div>
          </div>
          <div class="notif-row" onclick="location.href='incidents.php'">
            <span class="notif-dot-sm" style="background:var(--primary);"></span>
            <div>
              <div class="notif-text">🚨 Critical: Cardiac Arrest — Roxas Ave</div>
              <div class="notif-time">5 minutes ago</div>
            </div>
          </div>
          <div class="notif-row" onclick="location.href='incidents.php'">
            <span class="notif-dot-sm" style="background:var(--pending);"></span>
            <div>
              <div class="notif-text">⚡ Active: Multi-Vehicle Collision — CM Recto</div>
              <div class="notif-time">12 minutes ago</div>
            </div>
          </div>
          <div class="notif-row" onclick="location.href='incidents.php'">
            <span class="notif-dot-sm" style="background:var(--dispatched);"></span>
            <div>
              <div class="notif-text">🏥 Active: Child Respiratory — Quirino Ave</div>
              <div class="notif-time">18 minutes ago</div>
            </div>
          </div>
          <div class="notif-row" onclick="location.href='incidents.php'" style="border-bottom:none;">
            <span class="notif-dot-sm" style="background:var(--green);"></span>
            <div>
              <div class="notif-text">✅ Resolved: Kitchen Fire — Bajada Area</div>
              <div class="notif-time">1 hour ago</div>
            </div>
          </div>
        </div>
        <div class="notif-footer">
          <a href="incidents.php">View all incidents →</a>
        </div>
      </div>
    </div>

    <!-- Avatar -->
    <div class="header-avatar" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>">
      <?php echo $user_initials; ?>
    </div>
  </div>
</header>