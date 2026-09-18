<?php if (empty($_SESSION['role']) || !empty($forceVisitorNav)) { ?>

<!-- ================= VISITOR TOP NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" data-navbar-on-scroll="data-navbar-on-scroll" style="background-color: #fff !important;">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/blood.jpeg" alt="" width="60" />
            <span class="text-1000 fs-1 ms-2 fw-medium" style="color:#cc0000;">BloodLink</span>
        </a>
        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mx-auto border-bottom border-lg-bottom-0 pt-2 pt-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
                <?php if (empty($_SESSION['role'])) { ?>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold" href="register.php">Register</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user-login.php">Login</a>
                </li>
                <?php } ?>
            </ul>
            <?php if (!empty($_SESSION['role'])) { ?>
                <?php
                $dashboardLink = 'index.php';
                switch ($_SESSION['role']) {
                    case 'admin': $dashboardLink = 'dashboard.php'; break;
                    case 'supervisor': $dashboardLink = 'officer_dashboard.php'; break;
                    case 'student': $dashboardLink = 'donor_dashboard.php'; break;
                    case 'recipient': $dashboardLink = 'recipient_dashboard.php'; break;
                }
                ?>
                <a href="<?php echo $dashboardLink; ?>" class="btn btn-danger rounded-pill ms-lg-3">Go to Dashboard</a>
            <?php } else { ?>
                <a href="login.php" title="Administrator Login">
                    <img src="assets/img/user-icon.jpg" alt="Admin Login" width="45" height="45" class="rounded-circle border border-2 border-danger ms-lg-3" />
                </a>
            <?php } ?>
        </div>
    </div>
</nav>

<?php } else { ?>

<!-- ================= LOGGED-IN TOPBAR + SIDEBAR ================= -->
<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Login sets $_SESSION['name']; older code wrote user_name. Prefer the one that
// exists so the topbar shows the real name, not "Admin"/"Supervisor" (BL-20).
$displayName = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['reg_no']
    ?? ucfirst($_SESSION['role'] ?? 'User');

$avatarLetter = strtoupper(substr(trim($displayName), 0, 1));
if ($avatarLetter === '') { $avatarLetter = 'U'; }

// Where this role's Dashboard lives (used by the topbar menu).
switch ($_SESSION['role'] ?? '') {
    case 'admin':      $dashLinkForRole = 'dashboard.php'; break;
    case 'supervisor': $dashLinkForRole = 'officer_dashboard.php'; break;
    case 'student':    $dashLinkForRole = 'donor_dashboard.php'; break;
    case 'recipient':  $dashLinkForRole = 'recipient_dashboard.php'; break;
    default:           $dashLinkForRole = 'index.php';
}
?>

<!-- Top Header Bar -->
<div class="topbar">
    <div class="topbar-brand">
        <i class="fas fa-tint"></i>
        <span>BloodLink</span>
    </div>
    <div class="topbar-right">
        <div class="topbar-bell">
            <i class="fas fa-bell"></i>
            <span class="dot"></span>
        </div>
        <div class="topbar-user" id="topbarUser" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
            <div class="topbar-avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
            <span class="topbar-username"><?php echo htmlspecialchars($displayName); ?></span>
            <i class="fas fa-chevron-down" style="font-size:.7rem;color:#888;"></i>
            <div class="topbar-menu" id="topbarMenu" role="menu">
                <div class="topbar-menu-head">
                    <div class="topbar-avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
                    <div>
                        <div class="tm-name"><?php echo htmlspecialchars($displayName); ?></div>
                        <div class="tm-role"><?php echo htmlspecialchars(ucfirst($_SESSION['role'] === 'supervisor' ? 'Hospital Officer' : ($_SESSION['role'] === 'student' ? 'Donor' : $_SESSION['role']))); ?></div>
                    </div>
                </div>
                <a class="topbar-menu-item" href="<?php echo $dashLinkForRole ?? '#'; ?>"><i class="fas fa-gauge"></i> Dashboard</a>
                <a class="topbar-menu-item topbar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<nav class="sidebar" id="sidebar">
    <ul class="sidebar-nav">
        <?php 
        if ($_SESSION['role'] == 'admin' ) { 
        ?>
            <li><a class="nav-link <?php echo $current_page=='dashboard.php'?'active':''; ?>" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_donors.php'?'active':''; ?>" href="manage_donors.php"><i class="fas fa-tint"></i> Manage Donors</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_officers.php'?'active':''; ?>" href="manage_officers.php"><i class="fas fa-briefcase"></i> Hospital Officers</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_recipients.php'?'active':''; ?>" href="manage_recipients.php"><i class="fas fa-user-injured"></i> Recipients</a></li>
            <li><a class="nav-link <?php echo $current_page=='blood_requests.php'?'active':''; ?>" href="blood_requests.php"><i class="fas fa-hand-holding-medical"></i> Blood Requests</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_blood_stock.php'?'active':''; ?>" href="manage_blood_stock.php"><i class="fas fa-flask"></i> Blood Stock</a></li>
            <li><a class="nav-link <?php echo $current_page=='notices.php'?'active':''; ?>" href="notices.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Geo-Map</a></li>
            <li><a class="nav-link <?php echo $current_page=='reports.php'?'active':''; ?>" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php
        } elseif ($_SESSION['role'] == 'supervisor' ) {
        ?>
            <li><a class="nav-link <?php echo $current_page=='officer_dashboard.php'?'active':''; ?>" href="officer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='assigned_donors.php'?'active':''; ?>" href="assigned_donors.php"><i class="fas fa-users"></i> Donors</a></li>
            <li><a class="nav-link <?php echo $current_page=='blood_requests.php'?'active':''; ?>" href="blood_requests.php"><i class="fas fa-hand-holding-medical"></i> Blood Requests</a></li>
            <li><a class="nav-link <?php echo $current_page=='notices.php'?'active':''; ?>" href="notices.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Geo-Map</a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php
        } elseif ($_SESSION['role'] == 'student' ) {
        ?>
            <li><a class="nav-link <?php echo $current_page=='donor_dashboard.php'?'active':''; ?>" href="donor_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='donation_form.php'?'active':''; ?>" href="donation_form.php"><i class="fas fa-tint"></i> Donate Blood</a></li>
            <li><a class="nav-link <?php echo $current_page=='emergency_alerts.php'?'active':''; ?>" href="emergency_alerts.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Nearby Banks</a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php
        } elseif ($_SESSION['role'] == 'recipient' ) {
        ?>
            <li><a class="nav-link <?php echo $current_page=='recipient_dashboard.php'?'active':''; ?>" href="recipient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='request_blood.php'?'active':''; ?>" href="request_blood.php"><i class="fas fa-hand-holding-medical"></i> Request Blood</a></li>
            <li><a class="nav-link <?php echo $current_page=='request_history.php'?'active':''; ?>" href="request_history.php"><i class="fas fa-history"></i> Request History</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Nearby Banks</a></li>
            <li><a class="nav-link <?php echo $current_page=='logout.php'?'active':''; ?>" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php } ?>
    </ul>
</nav>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});
// Topbar user dropdown (profile + logout)
(function () {
    var u = document.getElementById('topbarUser');
    var m = document.getElementById('topbarMenu');
    if (!u || !m) return;
    function toggle(e) { e.stopPropagation(); var open = m.classList.toggle('open'); u.setAttribute('aria-expanded', open); }
    u.addEventListener('click', toggle);
    u.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(e); } });
    document.addEventListener('click', function () { m.classList.remove('open'); u.setAttribute('aria-expanded', 'false'); });
    m.addEventListener('click', function (e) { e.stopPropagation(); });
})();
</script>

<?php } ?>