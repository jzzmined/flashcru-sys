<?php
/**
 * FlashCru — Shared Header/Topbar Include
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
?>
<header style="
  height: 64px;
  background: #FAFCFF;
  border-bottom: 1px solid rgba(160,180,220,0.25);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(150,170,220,0.12);
  flex-shrink: 0;
">
  <div>
    <div style="font-size:15px;font-weight:700;color:#1A2340;"><?php echo $title_info[0]; ?></div>
    <div style="font-size:11px;color:#8E9BBE;margin-top:1px;"><?php echo $title_info[1]; ?></div>
  </div>
  <div style="display:flex;align-items:center;gap:10px;">
    <span id="header-clock" style="font-size:12px;font-weight:500;color:#5A6787;padding:6px 12px;background:#EEF1F7;border-radius:10px;box-shadow:inset 3px 3px 8px rgba(160,180,220,0.35),inset -3px -3px 8px rgba(255,255,255,0.75);"></span>
    <div style="width:36px;height:36px;border-radius:10px;background:#EEF1F7;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:4px 4px 12px rgba(150,170,210,0.30),-3px -3px 8px rgba(255,255,255,0.85);">🔔</div>
    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3D5AF1,#6C4FDB);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:white;cursor:pointer;box-shadow:0 3px 10px rgba(61,90,241,0.30);">
      <?php echo strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2)); ?>
    </div>
  </div>
</header>

<script>
(function() {
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
})();
</script>