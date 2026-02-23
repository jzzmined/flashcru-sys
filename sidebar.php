<?php
/**
 * FlashCru Emergency Response System
 * Shared Sidebar — v2 Redesign
 * Replace your existing: includes/sidebar.php
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside style="
  width:248px;min-height:100vh;height:100%;
  background:#1A2340;position:fixed;top:0;left:0;z-index:200;
  display:flex;flex-direction:column;
  box-shadow:4px 0 24px rgba(26,35,64,0.20);
  border-right:1px solid rgba(255,255,255,0.04);
  font-family:'DM Sans','Inter',-apple-system,sans-serif;
">

  <!-- Logo -->
  <div style="padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;gap:12px;flex-shrink:0;">
    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#E53935,#C62828);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 4px 12px rgba(229,57,53,0.40);">
      <img src="fc-logo.jpg" alt="FC" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=&quot;font-size:20px&quot;>⚡</span>'">
    </div>
    <div>
      <div style="font-weight:800;font-size:17px;color:#fff;letter-spacing:-0.3px;">FlashCru</div>
      <div style="font-size:10px;color:rgba(255,255,255,0.38);letter-spacing:0.07em;margin-top:1px;">Emergency Response</div>
    </div>
  </div>

  <!-- Nav Links -->
  <nav style="padding:14px 12px;flex:1;overflow-y:auto;">
    <?php
    $nav = [
      ['dashboard.php', '⬛', 'Dashboard',  null],
      ['incidents.php', '🔔', 'Incidents',  '6'],
      ['teams.php',     '👥', 'Teams',      null],
      ['reports.php',   '📊', 'Reports',    null],
      ['settings.php',  '⚙️', 'Settings',   null],
    ];
    foreach ($nav as [$page, $icon, $label, $badge]):
      $active = ($current_page === $page);
    ?>
    <a href="<?php echo $page; ?>"
       class="fc-nav-item<?php echo $active ? ' fc-nav-active' : ''; ?>"
       style="display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;font-size:13.5px;font-weight:500;text-decoration:none;transition:background 0.2s,color 0.2s;margin-bottom:2px;position:relative;color:<?php echo $active ? '#fff' : 'rgba(255,255,255,0.50)'; ?>;background:<?php echo $active ? 'linear-gradient(135deg,rgba(61,90,241,0.30),rgba(61,90,241,0.18))' : 'transparent'; ?>;<?php echo $active ? 'box-shadow:inset 0 0 0 1px rgba(61,90,241,0.30);font-weight:600;' : ''; ?>">
      <?php if ($active): ?>
      <span style="position:absolute;left:0;top:25%;bottom:25%;width:3px;background:#3D5AF1;border-radius:0 3px 3px 0;"></span>
      <?php endif; ?>
      <span style="font-size:15px;width:20px;text-align:center;flex-shrink:0;"><?php echo $icon; ?></span>
      <?php echo htmlspecialchars($label); ?>
      <?php if ($badge): ?>
      <span style="margin-left:auto;background:#E53935;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;"><?php echo $badge; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- User Card + Logout -->
  <div style="padding:12px;border-top:1px solid rgba(255,255,255,0.07);flex-shrink:0;">
    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;margin-bottom:4px;">
      <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3D5AF1,#6C4FDB);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 3px 8px rgba(61,90,241,0.35);">
        <?php echo strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2)); ?>
      </div>
      <div style="min-width:0;flex:1;">
        <div style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,0.38);margin-top:1px;">
          <?php echo ucfirst($_SESSION['role'] ?? 'Administrator'); ?>
        </div>
      </div>
    </div>
    <a href="logout.php" class="fc-logout-btn" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:rgba(229,57,53,0.80);font-size:13px;font-weight:600;text-decoration:none;transition:background 0.2s,color 0.2s;">
      <span style="font-size:15px;">🚪</span> Log Out
    </a>
  </div>
</aside>

<style>
  /* Sidebar hover states & layout helpers */
  .fc-nav-item:hover { background:rgba(255,255,255,0.07) !important; color:rgba(255,255,255,0.85) !important; }
  .fc-nav-active:hover { background:linear-gradient(135deg,rgba(61,90,241,0.35),rgba(61,90,241,0.22)) !important; color:#fff !important; }
  .fc-logout-btn:hover { background:rgba(229,57,53,0.15) !important; color:#ff6b6b !important; }

  /* Page layout */
  body { margin:0; background:#EEF1F7; font-family:'DM Sans','Inter',-apple-system,sans-serif; font-size:14px; color:#1A2340; }
  .dashboard-wrapper { display:flex; min-height:100vh; }
  .main-content      { margin-left:248px; flex:1; min-height:100vh; display:flex; flex-direction:column; background:#EEF1F7; }
  .page-content      { padding:28px 32px; flex:1; }
</style>