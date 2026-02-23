<?php
/**
 * FlashCru Emergency Response System
 * Shared Sidebar — Red/White/Blue Theme v3.0
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside style="
  width:256px; min-height:100vh; height:100%;
  background:#7D1D2F;
  position:fixed; top:0; left:0; z-index:200;
  display:flex; flex-direction:column;
  box-shadow:4px 0 24px rgba(93,16,32,0.30);
  overflow:hidden;
  font-family:'Sora',-apple-system,sans-serif;
">
  <!-- Decorative circles -->
  <div style="position:absolute;top:-80px;right:-80px;width:220px;height:220px;background:rgba(255,255,255,0.04);border-radius:50%;pointer-events:none;"></div>
  <div style="position:absolute;bottom:60px;left:-60px;width:180px;height:180px;background:rgba(255,255,255,0.03);border-radius:50%;pointer-events:none;"></div>

  <!-- Logo -->
  <div style="padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.10);display:flex;align-items:center;gap:12px;flex-shrink:0;position:relative;z-index:1;">
    <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
      <img src="fc-logo.jpg" alt="FC" style="width:100%;height:100%;object-fit:contain;"
           onerror="this.style.display='none';this.parentElement.innerHTML='<span style=&quot;font-size:22px&quot;>⚡</span>'">
    </div>
    <div>
      <div style="font-weight:800;font-size:17px;color:#fff;letter-spacing:-0.3px;">FlashCru</div>
      <div style="font-size:10px;color:rgba(255,255,255,0.42);letter-spacing:0.07em;margin-top:1px;">Emergency Response</div>
    </div>
  </div>

  <!-- Nav -->
  <nav style="padding:14px 12px;flex:1;overflow-y:auto;position:relative;z-index:1;">
    <?php
    $nav_sections = [
      'Main' => [
        ['dashboard.php', '🏠', 'Dashboard',  null],
        ['incidents.php', '🚨', 'Incidents',  '6'],
        ['teams.php',     '👥', 'Teams',      null],
      ],
      'Analytics' => [
        ['reports.php',   '📊', 'Reports',    null],
      ],
      'System' => [
        ['settings.php',  '⚙️', 'Settings',   null],
      ],
    ];
    foreach ($nav_sections as $section => $items):
    ?>
    <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.28);padding:0 10px;margin:14px 0 5px;"><?php echo $section; ?></div>
    <?php foreach ($items as [$page, $icon, $label, $badge]):
      $active = ($current_page === $page);
      $style = $active
        ? 'background:rgba(255,255,255,0.16);color:#fff;font-weight:600;'
        : 'color:rgba(255,255,255,0.58);';
    ?>
    <a href="<?php echo $page; ?>"
       style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13.5px;font-weight:500;text-decoration:none;transition:all 0.18s;margin-bottom:2px;position:relative;<?php echo $style; ?>">
      <?php if ($active): ?>
      <span style="position:absolute;left:0;top:22%;bottom:22%;width:3px;background:#F2C4C8;border-radius:0 3px 3px 0;"></span>
      <?php endif; ?>
      <span style="font-size:15px;width:20px;text-align:center;flex-shrink:0;"><?php echo $icon; ?></span>
      <?php echo htmlspecialchars($label); ?>
      <?php if ($badge): ?>
      <span style="margin-left:auto;background:#fff;color:#7D1D2F;font-size:10px;font-weight:800;padding:2px 7px;border-radius:99px;animation:pulse-badge 2s ease-in-out infinite;"><?php echo $badge; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <!-- User + Logout -->
  <div style="padding:12px;border-top:1px solid rgba(255,255,255,0.10);flex-shrink:0;position:relative;z-index:1;">
    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;margin-bottom:3px;">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.20);border:2px solid rgba(255,255,255,0.30);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;font-family:'JetBrains Mono',monospace;">
        <?php echo strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2)); ?>
      </div>
      <div style="min-width:0;flex:1;">
        <div style="font-size:12.5px;font-weight:600;color:rgba(255,255,255,0.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,0.38);margin-top:1px;">
          <?php echo ucfirst($_SESSION['role'] ?? 'Administrator'); ?>
        </div>
      </div>
    </div>
    <a href="logout.php" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,180,180,0.80);font-size:13px;font-weight:600;text-decoration:none;transition:all 0.18s;font-family:'Sora',sans-serif;"
       onmouseover="this.style.background='rgba(255,255,255,0.10)';this.style.color='#fff';"
       onmouseout="this.style.background='';this.style.color='rgba(255,180,180,0.80)';">
      <span style="font-size:15px;">🚪</span> Log Out
    </a>
  </div>
</aside>

<style>
@keyframes pulse-badge {
  0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.5); }
  50%      { box-shadow: 0 0 0 5px rgba(255,255,255,0); }
}
body { margin:0; background:#FEF8F8; font-family:'Sora',-apple-system,sans-serif; font-size:14px; color:#0F172A; }
.dashboard-wrapper { display:flex; min-height:100vh; }
.main-content { margin-left:256px; flex:1; min-height:100vh; display:flex; flex-direction:column; }
.page-content { padding:28px 32px; flex:1; }
</style>