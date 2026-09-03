<?php

// Set maximum idle time to 5 minutes (300 seconds)
$timeout_duration = 600;

if (isset($_SESSION['last_activity1'])) {
    if ((time() - $_SESSION['last_activity1']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: logout.php?reason=timeout");
        exit();
    }
}

$_SESSION['last_activity1'] = time();
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <i class="fa-solid fa-shield-halved text-success me-2"></i>
            Swine Guard
        </a>
        
        <!-- TIMEOUT BADGE (Hidden) -->
        <div id="session-timer-badge" class="ms-3 px-3 py-1 bg-dark text-warning rounded-pill border border-warning small align-items-center" style="display: none !important;">
            <i class="fa-solid fa-clock me-2"></i>
            <span>Session Timeout: <strong id="timer-display">05:00</strong></span>
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active fw-semibold' : ''; ?>" href="dashboard.php">
                        <i class="fa-solid fa-chart-pie me-1 small"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'analytics.php') ? 'active fw-semibold' : ''; ?>" href="analytics.php">
                        <i class="fa-solid fa-chart-line me-1 small"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'records.php') ? 'active fw-semibold' : ''; ?>" href="records.php">
                        <i class="fa-solid fa-folder-open me-1 small"></i> Records
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'profile.php') ? 'active fw-semibold' : ''; ?>" href="profile.php">
                        <i class="fa-solid fa-circle-user me-1 small"></i> Profile
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="nav-link text-danger-hover" style="cursor: pointer;" onclick="logout()">
                        <i class="fa-solid fa-right-from-bracket me-1 small"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<script src="../include/jquery.js"></script>

<script>
    function logout() {
        Swal.fire({
            title: "Confirm Logout",
            text: "Are you sure you want to logout?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#6a11cb",
            cancelButtonColor: "#666",
            confirmButtonText: "Yes, Logout"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "logout.php";
            }
        });
    }

    // Client-side 5-Minute Inactivity Timeout Logic
    (function () {
        const timeoutInSeconds = <?= $timeout_duration ?>; // 300 seconds
        let timeRemaining = timeoutInSeconds;
        let countdownInterval;

        function updateDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const formattedMinutes = String(minutes).padStart(2, '0');
            const formattedSeconds = String(seconds).padStart(2, '0');
            
            const timerElement = document.getElementById('timer-display');
            if (timerElement) {
                timerElement.textContent = `${formattedMinutes}:${formattedSeconds}`;
            }
        }

        function startCountdown() {
            clearInterval(countdownInterval);
            timeRemaining = timeoutInSeconds;
            updateDisplay();

            countdownInterval = setInterval(() => {
                timeRemaining--;
                updateDisplay();

                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    triggerTimeout();
                }
            }, 1000);
        }

        function triggerTimeout() {
            Swal.fire({
                title: "Session Expired",
                text: "You have been logged out due to 5 minutes of inactivity.",
                icon: "info",
                confirmButtonColor: "#6a11cb",
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = "logout.php?reason=timeout";
            });
        }

        // Resets timer on user interaction
        window.onload = startCountdown;
        document.onmousemove = startCountdown;
        document.onkeypress = startCountdown;
        document.onclick = startCountdown;
        document.onscroll = startCountdown;
    })();
</script>

<style>
    /* Clean navigation visual overrides */
    .navbar {
        background-color: #0f172a !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 15px 0;
    }

    .navbar-dark .navbar-nav .nav-link {
        color: #94a3b8;
        transition: color 0.2s, background-color 0.2s;
        padding: 8px 16px;
        border-radius: 6px;
    }

    .navbar-dark .navbar-nav .nav-link:hover,
    .navbar-dark .navbar-nav .nav-link.active {
        color: #ffffff !important;
    }

    .navbar-dark .navbar-nav .nav-link.active {
        background-color: #1e293b;
    }

    .text-danger-hover:hover {
        color: #ef4444 !important;
    }

    /* Hidden session timer badge */
    #session-timer-badge {
        display: none !important;
    }
</style>