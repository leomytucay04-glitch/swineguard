<?php

// ── Session Timeout ────────────────────────────────────────
$timeout_duration = 300;

if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: logout.php?reason=timeout");
        exit();
    }
}

$_SESSION['last_activity'] = time();
$current_page = basename($_SERVER['PHP_SELF']);

// Derive admin initials for avatar
$admin_initials = 'AD';
if (isset($_SESSION['admin_name'])) {
    $parts = explode(' ', trim($_SESSION['admin_name']));
    $admin_initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>

<!-- ╔════════════════════════════════════════════╗
     ║  SWINE GUARD — TOP BAR                    ║
     ╚════════════════════════════════════════════╝ -->
<header class="sg-topbar">
    <!-- Brand -->
    <a href="dashboard.php" class="sg-brand">
        <i class="fa-solid fa-shield-halved"></i>
        Swine Guard
    </a>

    <!-- Hidden session badge -->
    <div id="session-timer-badge"></div>

    <!-- Right actions -->
    <div class="sg-topbar-actions">
        <span class="sg-live-badge">
            <span class="dot"></span> Live
        </span>
        <a href="profile.php" class="sg-topbar-btn" title="Profile">
            <i class="fa-solid fa-circle-user"></i>
        </a>
        <div class="sg-avatar" title="Admin"><?= $admin_initials ?></div>
        <!-- Mobile sidebar toggle -->
        <button class="sg-topbar-btn sg-sidebar-toggle" id="sidebarToggle" title="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<!-- Mobile overlay -->
<div class="sg-sidebar-overlay" id="sidebarOverlay"></div>

<!-- ╔════════════════════════════════════════════╗
     ║  SWINE GUARD — SIDEBAR                    ║
     ╚════════════════════════════════════════════╝ -->
<aside class="sg-sidebar" id="sgSidebar">
    <nav class="sg-nav">
        <div class="sg-nav-section">Main</div>

        <a href="dashboard.php" class="sg-nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <a href="analytics.php" class="sg-nav-item <?= ($current_page == 'analytics.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Analytics</span>
        </a>

        <a href="records.php" class="sg-nav-item <?= ($current_page == 'records.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-folder-open"></i>
            <span>Records</span>
        </a>

        <div class="sg-nav-section">Administration</div>

        <a href="users.php" class="sg-nav-item <?= ($current_page == 'users.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-users-gear"></i>
            <span>Staff</span>
        </a>

        <a href="settings.php" class="sg-nav-item <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders"></i>
            <span>Settings</span>
        </a>

        <div class="sg-nav-section">Account</div>

        <a href="profile.php" class="sg-nav-item <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div class="sg-sidebar-footer">
        <div class="sg-nav-item danger" style="cursor:pointer;" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </div>
    </div>
</aside>

<script src="../include/jquery.js"></script>
<script>
    // ── Logout Confirm ────────────────────────────────────────
    function logout() {
        Swal.fire({
            title: "Confirm Logout",
            text: "Are you sure you want to logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#7c3aed",
            cancelButtonColor: "#1c1c30",
            confirmButtonText: "Yes, Logout"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "logout.php";
            }
        });
    }

    // ── Mobile Sidebar Toggle ─────────────────────────────────
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sgSidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    });

    document.getElementById('sidebarOverlay')?.addEventListener('click', function () {
        document.getElementById('sgSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });

    // ── Inactivity Timeout ────────────────────────────────────
    (function () {
        const timeoutInSeconds = <?= $timeout_duration ?>;
        let timeRemaining = timeoutInSeconds;
        let countdownInterval;

        function startCountdown() {
            clearInterval(countdownInterval);
            timeRemaining = timeoutInSeconds;

            countdownInterval = setInterval(() => {
                timeRemaining--;
                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    triggerTimeout();
                }
            }, 1000);
        }

        function triggerTimeout() {
            Swal.fire({
                title: "Session Expired",
                text: "You have been logged out due to inactivity.",
                icon: "info",
                confirmButtonColor: "#7c3aed",
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = "logout.php?reason=timeout";
            });
        }

        window.onload = startCountdown;
        document.onmousemove = startCountdown;
        document.onkeypress = startCountdown;
        document.onclick = startCountdown;
        document.onscroll = startCountdown;
    })();
</script>