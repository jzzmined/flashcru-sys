<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}
?>

<aside class="sidebar" id="sidebar">

    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <div class="brand-logo">
            <span class="brand-icon">⚡</span>
            <div class="brand-text">
                <h2>FlashCru</h2>
                <p>Emergency Response</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">

            <!-- Dashboard -->
            <li class="menu-item">
                <a href="dashboard.php"
                   class="menu-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="menu-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <!-- Incidents -->
            <li class="menu-item">
                <a href="incidents.php"
                   class="menu-link <?php echo $current_page == 'incidents.php' ? 'active' : ''; ?>">
                    <span class="menu-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </span>
                    <span class="menu-text">Incidents</span>
                    <?php
                    // Show active incident count badge
                    $db = new Database();
                    $active_count = $db->count('incidents', "status IN ('active', 'critical')");
                    if ($active_count > 0):
                    ?>
                        <span class="menu-badge"><?php echo $active_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Teams -->
            <li class="menu-item">
                <a href="teams.php"
                   class="menu-link <?php echo $current_page == 'teams.php' ? 'active' : ''; ?>">
                    <span class="menu-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <span class="menu-text">Teams</span>
                </a>
            </li>

            <!-- Reports -->
            <li class="menu-item">
                <a href="reports.php"
                   class="menu-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <span class="menu-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <span class="menu-text">Reports</span>
                </a>
            </li>

            <!-- Settings (Admin Only) -->
            <?php if (isAdmin()): ?>
            <li class="menu-item">
                <a href="settings.php"
                   class="menu-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <span class="menu-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <span class="menu-text">Settings</span>
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </nav>

    <!-- Sidebar Footer - User Info + Logout -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar-small">
                <?php echo getUserInitials(); ?>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p class="user-role"><?php echo getUserRoleName(); ?></p>
            </div>
        </div>
        <a href="?logout=1"
           class="logout-btn"
           onclick="return confirm('Are you sure you want to logout?')">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
        </a>
    </div>

</aside>