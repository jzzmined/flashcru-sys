<?php
/**
 * FlashCru Emergency Response System
 * Header / Top Navigation Component
 */
?>

<header class="top-header">

    <!-- Left Side: Page Title + Mobile Menu Toggle -->
    <div class="header-left">

        <!-- Mobile Hamburger Menu Button -->
        <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Page Title -->
        <div class="header-title">
            <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
            <p class="header-breadcrumb">
                FlashCru /
                <span><?php echo $page_title ?? 'Dashboard'; ?></span>
            </p>
        </div>

    </div>

    <!-- Right Side: Date, Notifications, User -->
    <div class="header-right">

        <!-- Live Date and Time -->
        <div class="header-datetime" id="headerDatetime">
            <?php echo date('D, M j Y - g:i A'); ?>
        </div>

        <!-- Notification Bell -->
        <?php
        $db_notif = new Database();
        $critical_count = $db_notif->count('incidents', "status = 'critical'");
        ?>
        <div class="header-notifications">
            <button class="notif-btn" title="Critical Incidents">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <?php if ($critical_count > 0): ?>
                    <span class="notif-badge"><?php echo $critical_count; ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- User Profile -->
        <div class="header-user" id="userDropdownToggle" onclick="toggleUserDropdown()">
            <div class="user-avatar">
                <?php echo getUserInitials(); ?>
            </div>
            <div class="user-info-header">
                <p class="user-name-header">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </p>
                <p class="user-role-header">
                    <?php echo getUserRoleName(); ?>
                </p>
            </div>
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>

            <!-- User Dropdown Menu -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    <span><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="settings.php" class="dropdown-item">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    My Profile
                </a>
                <?php if (isAdmin()): ?>
                <a href="settings.php" class="dropdown-item">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="?logout=1"
                   class="dropdown-item dropdown-logout"
                   onclick="return confirm('Are you sure you want to logout?')">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>

    </div>
</header>

<!-- JavaScript for Header -->
<script>
// Live clock update
function updateClock() {
    const now = new Date();
    const options = {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const el = document.getElementById('headerDatetime');
    if (el) {
        el.textContent = now.toLocaleDateString('en-US', options);
    }
}
setInterval(updateClock, 1000);
updateClock();

// Toggle user dropdown
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const toggle = document.getElementById('userDropdownToggle');
    const dropdown = document.getElementById('userDropdown');
    if (toggle && dropdown && !toggle.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Toggle sidebar for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}
</script>