<?php
$timeout_duration = 300;
if (isset($_SESSION['last_activity3'])) {
    if ((time() - $_SESSION['last_activity3']) > $timeout_duration) {
        session_unset(); session_destroy();
        header("Location: logout.php?reason=timeout"); exit();
    }
}
$_SESSION['last_activity3'] = time();
$current_page = basename($_SERVER['PHP_SELF']);
$user_initials = 'CL';
if (isset($_SESSION['user_name'])) {
    $parts = explode(' ', trim($_SESSION['user_name']));
    $user_initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>

<header class="sg-topbar">
    <a href="dashboard.php" class="sg-brand">
        <i class="fa-solid fa-shield-halved"></i>
        Swine Guard
    </a>
    <div id="session-timer-badge"></div>
    <div class="sg-topbar-actions">
        <span class="sg-live-badge"><span class="dot"></span> Live</span>
        <a href="profile.php" class="sg-topbar-btn" title="Profile"><i class="fa-solid fa-circle-user"></i></a>
        <div class="sg-avatar" title="Client"><?= $user_initials ?></div>
        <button class="sg-topbar-btn sg-sidebar-toggle" id="sidebarToggle" title="Menu"><i class="fa-solid fa-bars"></i></button>
    </div>
</header>

<div class="sg-sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sg-sidebar" id="sgSidebar">
    <nav class="sg-nav">
        <div class="sg-nav-section">Monitor</div>
        <a href="dashboard.php" class="sg-nav-item <?= ($current_page=='dashboard.php')?'active':'' ?>">
            <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <a href="analytics.php" class="sg-nav-item <?= ($current_page=='analytics.php')?'active':'' ?>">
            <i class="fa-solid fa-chart-line"></i><span>Analytics</span>
        </a>
        <a href="records.php" class="sg-nav-item <?= ($current_page=='records.php')?'active':'' ?>">
            <i class="fa-solid fa-folder-open"></i><span>Records</span>
        </a>
        <div class="sg-nav-section">Account</div>
        <a href="profile.php" class="sg-nav-item <?= ($current_page=='profile.php')?'active':'' ?>">
            <i class="fa-solid fa-circle-user"></i><span>Profile</span>
        </a>
    </nav>
    <div class="sg-sidebar-footer">
        <div class="sg-nav-item danger" style="cursor:pointer;" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </div>
    </div>
</aside>

<script src="../include/jquery.js"></script>
<script>
    function logout(){Swal.fire({title:"Confirm Logout",text:"Are you sure?",icon:"warning",showCancelButton:true,confirmButtonColor:"#7c3aed",cancelButtonColor:"#1c1c30",confirmButtonText:"Yes, Logout"}).then((r)=>{if(r.isConfirmed)window.location.href="logout.php";});}
    document.getElementById('sidebarToggle')?.addEventListener('click',function(){document.getElementById('sgSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');});
    document.getElementById('sidebarOverlay')?.addEventListener('click',function(){document.getElementById('sgSidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');});
    (function(){
        const t=<?= $timeout_duration ?>;let r=t;let i;
        function s(){clearInterval(i);r=t;i=setInterval(()=>{r--;if(r<=0){clearInterval(i);Swal.fire({title:"Session Expired",text:"Logged out due to inactivity.",icon:"info",confirmButtonColor:"#7c3aed",confirmButtonText:"OK",allowOutsideClick:false,allowEscapeKey:false}).then(()=>{window.location.href="logout.php?reason=timeout";});}},1000);}
        window.onload=s;document.onmousemove=s;document.onkeypress=s;document.onclick=s;document.onscroll=s;
    })();
</script>