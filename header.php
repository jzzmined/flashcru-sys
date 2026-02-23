<?php
/**
 * FlashCru — Shared Header/Topbar Include
 * Red/White/Blue Theme v3.0
 */
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
  'dashboard.php' => ['Dashboard',  'FlashCru / Dashboard'],
  'incidents.php' => ['Incidents',  'FlashCru / Incidents'],
  'teams.php'     => ['Teams',      'FlashCru / Teams'],
  'reports.php'   => ['Reports',    'FlashCru / Reports'],
  'settings.php'  => ['Settings',   'FlashCru / Settings'],
];
$title_info = $page_titles[$current_page] ?? ['Dashboard', 'FlashCru'];
$user_initials = strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2));
?>
<header style="
  height:64px;
  background:#FFFFFF;
  border-bottom:1px solid #E5E7EB;
  padding:0 32px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  position:sticky;
  top:0;
  z-index:100;
  box-shadow:0 1px 4px rgba(0,0,0,0.06);
  flex-shrink:0;
  font-family:'Sora',-apple-system,sans-serif;
">
  <!-- Left: page title -->
  <div>
    <div style="font-size:15px;font-weight:700;color:#0F172A;"><?php echo $title_info[0]; ?></div>
    <div style="font-size:11px;color:#9CA3AF;margin-top:1px;"><?php echo $title_info[1]; ?></div>
  </div>

  <!-- Right: clock + notif + avatar -->
  <div style="display:flex;align-items:center;gap:8px;position:relative;">

    <!-- Clock -->
    <span id="header-clock" style="font-size:12px;font-weight:500;color:#6B7280;padding:6px 12px;background:#F3F4F6;border-radius:8px;font-family:'JetBrains Mono',monospace;"></span>

    <!-- Notification Button -->
    <div style="position:relative;">
      <button id="notif-toggle"
        onclick="toggleNotifPanel()"
        style="width:36px;height:36px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background 0.18s;position:relative;"
        onmouseover="this.style.background='#FFE4E6';"
        onmouseout="this.style.background='#F3F4F6';"
        title="Notifications">
        🔔
        <span id="notif-dot" style="position:absolute;top:7px;right:7px;width:8px;height:8px;background:#A63244;border-radius:50%;border:2px solid #fff;"></span>
      </button>

      <!-- Notification Panel -->
      <div id="notif-panel" style="
        display:none;
        position:absolute;
        top:calc(100% + 10px);
        right:0;
        width:340px;
        background:#fff;
        border-radius:16px;
        box-shadow:0 8px 32px rgba(0,0,0,0.12),0 2px 8px rgba(0,0,0,0.06);
        border:1px solid #E5E7EB;
        z-index:999;
        animation:slideDown 0.18s ease;
      ">
        <div style="padding:15px 18px 11px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:14px;font-weight:700;color:#0F172A;">🔔 Notifications</span>
          <button onclick="clearNotifications()" style="font-size:11px;color:#A63244;cursor:pointer;font-weight:600;border:none;background:none;font-family:'Sora',sans-serif;">Clear all</button>
        </div>
        <div id="notif-list">
          <div class="notif-row" onclick="window.location='incidents.php';" style="padding:12px 18px;border-bottom:1px solid #F9FAFB;display:flex;gap:11px;cursor:pointer;transition:background 0.14s;" onmouseover="this.style.background='#FFF1F2';" onmouseout="this.style.background='';">
            <span style="width:8px;height:8px;border-radius:50%;background:#A63244;flex-shrink:0;margin-top:5px;"></span>
            <div><div style="font-size:12.5px;font-weight:600;color:#0F172A;">🚨 Critical: Building Fire — J.P. Laurel Ave</div><div style="font-size:11px;color:#9CA3AF;margin-top:2px;">2 minutes ago</div></div>
          </div>
          <div class="notif-row" onclick="window.location='incidents.php';" style="padding:12px 18px;border-bottom:1px solid #F9FAFB;display:flex;gap:11px;cursor:pointer;transition:background 0.14s;" onmouseover="this.style.background='#FFF1F2';" onmouseout="this.style.background='';">
            <span style="width:8px;height:8px;border-radius:50%;background:#A63244;flex-shrink:0;margin-top:5px;"></span>
            <div><div style="font-size:12.5px;font-weight:600;color:#0F172A;">🚨 Critical: Cardiac Arrest — Roxas Ave</div><div style="font-size:11px;color:#9CA3AF;margin-top:2px;">5 minutes ago</div></div>
          </div>
          <div class="notif-row" onclick="window.location='incidents.php';" style="padding:12px 18px;border-bottom:1px solid #F9FAFB;display:flex;gap:11px;cursor:pointer;transition:background 0.14s;" onmouseover="this.style.background='#FFF1F2';" onmouseout="this.style.background='';">
            <span style="width:8px;height:8px;border-radius:50%;background:#D97706;flex-shrink:0;margin-top:5px;"></span>
            <div><div style="font-size:12.5px;font-weight:600;color:#0F172A;">⚡ Active: Multi-Vehicle Collision — CM Recto</div><div style="font-size:11px;color:#9CA3AF;margin-top:2px;">12 minutes ago</div></div>
          </div>
          <div class="notif-row" onclick="window.location='incidents.php';" style="padding:12px 18px;border-bottom:1px solid #F9FAFB;display:flex;gap:11px;cursor:pointer;transition:background 0.14s;" onmouseover="this.style.background='#FFF1F2';" onmouseout="this.style.background='';">
            <span style="width:8px;height:8px;border-radius:50%;background:#2563EB;flex-shrink:0;margin-top:5px;"></span>
            <div><div style="font-size:12.5px;font-weight:600;color:#0F172A;">🏥 Active: Child Respiratory — Quirino Ave</div><div style="font-size:11px;color:#9CA3AF;margin-top:2px;">18 minutes ago</div></div>
          </div>
          <div class="notif-row" onclick="window.location='incidents.php';" style="padding:12px 18px;display:flex;gap:11px;cursor:pointer;transition:background 0.14s;" onmouseover="this.style.background='#FFF1F2';" onmouseout="this.style.background='';">
            <span style="width:8px;height:8px;border-radius:50%;background:#059669;flex-shrink:0;margin-top:5px;"></span>
            <div><div style="font-size:12.5px;font-weight:600;color:#0F172A;">✅ Resolved: Kitchen Fire — Bajada Area</div><div style="font-size:11px;color:#9CA3AF;margin-top:2px;">1 hour ago</div></div>
          </div>
        </div>
        <div style="padding:10px 18px;text-align:center;border-top:1px solid #F3F4F6;">
          <a href="incidents.php" style="font-size:12px;color:#2563EB;font-weight:600;text-decoration:none;">View all incidents →</a>
        </div>
      </div>
    </div>

    <!-- Avatar -->
    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#A63244,#E07B82);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:white;cursor:pointer;box-shadow:0 3px 10px rgba(124,29,52,0.28);font-family:'JetBrains Mono',monospace;">
      <?php echo $user_initials; ?>
    </div>

  </div>
</header>

<style>
@keyframes slideDown {
  from { opacity:0; transform:translateY(-6px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>

<script>
(function() {
  /* Clock */
  function tick() {
    var el = document.getElementById('header-clock');
    if (!el) return;
    var now = new Date();
    var d = now.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'});
    var t = now.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
    el.textContent = d + ' · ' + t;
  }
  tick();
  setInterval(tick, 30000);

  /* Notification panel toggle */
  window.toggleNotifPanel = function() {
    var panel = document.getElementById('notif-panel');
    panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
  };

  /* Clear notifications */
  window.clearNotifications = function() {
    document.getElementById('notif-list').innerHTML =
      '<div style="padding:28px;text-align:center;color:#9CA3AF;font-size:13px;">No new notifications</div>';
    var dot = document.getElementById('notif-dot');
    if (dot) dot.style.display = 'none';
  };

  /* Close panel on outside click */
  document.addEventListener('click', function(e) {
    var panel = document.getElementById('notif-panel');
    var toggle = document.getElementById('notif-toggle');
    if (panel && toggle && !panel.contains(e.target) && !toggle.contains(e.target)) {
      panel.style.display = 'none';
    }
  });
})();
</script>