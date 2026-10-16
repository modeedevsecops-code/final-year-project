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
                    case 'officer': $dashboardLink = 'officer_dashboard.php'; break;
                    case 'member': $dashboardLink = 'member_dashboard.php'; break;
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
// exists so the topbar shows the real name, not "Admin"/"Officer" (BL-20).
$displayName = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['donor_code']
    ?? ucfirst($_SESSION['role'] ?? 'User');

$avatarLetter = strtoupper(substr(trim($displayName), 0, 1));
if ($avatarLetter === '') { $avatarLetter = 'U'; }

// Where this role's Dashboard lives (used by the topbar menu).
switch ($_SESSION['role'] ?? '') {
    case 'admin':      $dashLinkForRole = 'dashboard.php'; break;
    case 'officer': $dashLinkForRole = 'officer_dashboard.php'; break;
    case 'member':    $dashLinkForRole = 'member_dashboard.php'; break;
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
        <?php
        list($nRole, $nId) = bl_current_user();
        $nUnread = bl_unread_count($db->connection, $nRole, $nId);
        $nItems  = bl_notifications($db->connection, $nRole, $nId, 8);
        $nBack   = basename($_SERVER['PHP_SELF']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        ?>
        <div class="topbar-bell" id="notifBell" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-badge" id="notifBadge"<?= $nUnread ? '' : ' hidden' ?>><?= $nUnread > 99 ? '99+' : $nUnread ?></span>
            <div class="notif-menu" id="notifMenu" role="menu">
                <div class="notif-head">
                    <strong>Notifications</strong>
                    <form method="POST" action="notifications.php" id="notifMarkAll"<?= $nUnread ? '' : ' hidden' ?>>
                        <?php bl_csrf_field(); ?>
                        <input type="hidden" name="back" value="<?= htmlspecialchars($nBack) ?>">
                        <button type="submit" name="btn_mark_all" class="notif-markall">Mark all as read</button>
                    </form>
                </div>
                <div id="notifList">
                    <?php if (empty($nItems)): ?>
                        <div class="notif-empty">No notifications yet.</div>
                    <?php else: foreach ($nItems as $n): ?>
                        <a href="notification_open.php?id=<?= (int)$n['notification_id'] ?>" class="notif-row<?= $n['is_read'] ? '' : ' unread' ?>">
                            <span class="notif-icon"><i class="fas <?= bl_notification_icon($n['type']) ?>"></i></span>
                            <span class="notif-text"><strong><?= htmlspecialchars($n['title']) ?></strong><?php if ($n['body']): ?><span><?= htmlspecialchars($n['body']) ?></span><?php endif; ?></span>
                            <span class="notif-time"><?= htmlspecialchars(bl_time_ago($n['created_at'])) ?></span>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
                <a href="notifications.php" class="notif-all">View all notifications</a>
            </div>
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
                        <div class="tm-role"><?php echo htmlspecialchars($_SESSION['role'] === 'officer' ? 'Hospital Officer' : ($_SESSION['role'] === 'member' ? 'Member' : ucfirst($_SESSION['role']))); ?></div>
                    </div>
                </div>
                <a class="topbar-menu-item" href="<?php echo $dashLinkForRole ?? '#'; ?>"><i class="fas fa-gauge"></i> Dashboard</a>
                <a class="topbar-menu-item" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <?php if (($_SESSION['role'] ?? '') === 'member'): ?>
                <a class="topbar-menu-item" href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                <?php endif; ?>
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
            <li><a class="nav-link <?php echo in_array($current_page,['manage_members.php','edit_member.php'])?'active':''; ?>" href="manage_members.php"><i class="fas fa-users"></i> Manage Members</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_officers.php'?'active':''; ?>" href="manage_officers.php"><i class="fas fa-briefcase"></i> Hospital Officers</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_blood_banks.php'?'active':''; ?>" href="manage_blood_banks.php"><i class="fas fa-hospital"></i> Blood Banks</a></li>
            <li><a class="nav-link <?php echo $current_page=='blood_requests.php'?'active':''; ?>" href="blood_requests.php"><i class="fas fa-hand-holding-medical"></i> Blood Requests</a></li>
            <li><a class="nav-link <?php echo $current_page=='emergency_board.php'?'active':''; ?>" href="emergency_board.php"><i class="fas fa-triangle-exclamation"></i> Emergency Board</a></li>
            <li><a class="nav-link <?php echo $current_page=='find_donors.php'?'active':''; ?>" href="find_donors.php"><i class="fas fa-magnifying-glass"></i> Find Donors</a></li>
            <li><a class="nav-link <?php echo $current_page=='manage_blood_stock.php'?'active':''; ?>" href="manage_blood_stock.php"><i class="fas fa-flask"></i> Blood Stock</a></li>
            <li><a class="nav-link <?php echo $current_page=='notices.php'?'active':''; ?>" href="notices.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Geo-Map</a></li>
            <li><a class="nav-link <?php echo $current_page=='reports.php'?'active':''; ?>" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
            <?php $newMsgs = (int)(mysqli_fetch_row(mysqli_query($db->connection, "SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"))[0] ?? 0); ?>
            <li><a class="nav-link <?php echo $current_page=='messages.php'?'active':''; ?>" href="messages.php"><i class="fas fa-envelope"></i> Messages<?php if ($newMsgs): ?> <span class="badge bg-danger ms-1"><?= $newMsgs ?></span><?php endif; ?></a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php
        } elseif ($_SESSION['role'] == 'officer' ) {
        ?>
            <li><a class="nav-link <?php echo $current_page=='officer_dashboard.php'?'active':''; ?>" href="officer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='assigned_donors.php'?'active':''; ?>" href="assigned_donors.php"><i class="fas fa-users"></i> Donors</a></li>
            <li><a class="nav-link <?php echo $current_page=='blood_requests.php'?'active':''; ?>" href="blood_requests.php"><i class="fas fa-hand-holding-medical"></i> Blood Requests</a></li>
            <li><a class="nav-link <?php echo $current_page=='emergency_board.php'?'active':''; ?>" href="emergency_board.php"><i class="fas fa-triangle-exclamation"></i> Emergency Board</a></li>
            <li><a class="nav-link <?php echo $current_page=='find_donors.php'?'active':''; ?>" href="find_donors.php"><i class="fas fa-magnifying-glass"></i> Find Donors</a></li>
            <li><a class="nav-link <?php echo $current_page=='screen_donor.php'?'active':''; ?>" href="screen_donor.php"><i class="fas fa-stethoscope"></i> Screen Donor</a></li>
            <li><a class="nav-link <?php echo $current_page=='record_donation.php'?'active':''; ?>" href="record_donation.php"><i class="fas fa-tint"></i> Record Donation</a></li>
            <li><a class="nav-link <?php echo $current_page=='notices.php'?'active':''; ?>" href="notices.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Geo-Map</a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php
        } elseif ($_SESSION['role'] == 'member' ) {
        ?>
            <li><a class="nav-link <?php echo $current_page=='member_dashboard.php'?'active':''; ?>" href="member_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a class="nav-link <?php echo $current_page=='donation_form.php'?'active':''; ?>" href="donation_form.php"><i class="fas fa-tint"></i> Donate Blood</a></li>
            <li><a class="nav-link <?php echo $current_page=='screening.php'?'active':''; ?>" href="screening.php"><i class="fas fa-stethoscope"></i> Health Check</a></li>
            <li><a class="nav-link <?php echo $current_page=='request_blood.php'?'active':''; ?>" href="request_blood.php"><i class="fas fa-hand-holding-medical"></i> Request Blood</a></li>
            <li><a class="nav-link <?php echo $current_page=='request_history.php'?'active':''; ?>" href="request_history.php"><i class="fas fa-history"></i> Request History</a></li>
            <li><a class="nav-link <?php echo $current_page=='my_donations.php'?'active':''; ?>" href="my_donations.php"><i class="fas fa-award"></i> My Donations</a></li>
            <li><a class="nav-link <?php echo $current_page=='emergency_alerts.php'?'active':''; ?>" href="emergency_alerts.php"><i class="fas fa-bell"></i> Emergency Alerts</a></li>
            <li><a class="nav-link <?php echo $current_page=='geo_map.php'?'active':''; ?>" href="geo_map.php"><i class="fas fa-map-marker-alt"></i> Nearby Banks</a></li>
            <li><a class="nav-link <?php echo $current_page=='profile.php'?'active':''; ?>" href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li class="sidebar-sep"></li>
            <li><a class="nav-link sidebar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php } ?>
    </ul>
</nav>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});
// Notification bell: open/close, and refresh the badge + list every 30 s.
(function () {
    var bell = document.getElementById('notifBell'), menu = document.getElementById('notifMenu');
    if (!bell || !menu) return;
    function toggle(e) { e.stopPropagation(); var open = menu.classList.toggle('open'); bell.setAttribute('aria-expanded', open); }
    bell.addEventListener('click', function (e) { if (!menu.contains(e.target)) toggle(e); });
    bell.addEventListener('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && e.target === bell) { e.preventDefault(); toggle(e); } });
    document.addEventListener('click', function () { menu.classList.remove('open'); bell.setAttribute('aria-expanded', 'false'); });
    menu.addEventListener('click', function (e) { e.stopPropagation(); });
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); };
    var icons = {request_new:'fa-hand-holding-medical', request_status:'fa-clipboard-check', donation:'fa-tint', emergency:'fa-triangle-exclamation',
                 low_stock:'fa-flask', member_new:'fa-user-plus', contact:'fa-envelope', alert:'fa-bell'};
    function refresh() {
        fetch('notifications.php?format=json', {credentials:'same-origin'}).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) {
            if (!d) return;
            var badge = document.getElementById('notifBadge');
            badge.hidden = !d.unread; badge.textContent = d.unread > 99 ? '99+' : d.unread;
            document.getElementById('notifMarkAll').hidden = !d.unread;
            document.getElementById('notifList').innerHTML = d.items.length ? d.items.map(function (n) {
                return '<a href="notification_open.php?id=' + n.id + '" class="notif-row' + (n.read ? '' : ' unread') + '">'
                     + '<span class="notif-icon"><i class="fas ' + (icons[n.type] || 'fa-bell') + '"></i></span>'
                     + '<span class="notif-text"><strong>' + esc(n.title) + '</strong>' + (n.body ? '<span>' + esc(n.body) + '</span>' : '') + '</span>'
                     + '<span class="notif-time">' + esc(n.ago) + '</span></a>';
            }).join('') : '<div class="notif-empty">No notifications yet.</div>';
        }).catch(function () {});
    }
    setInterval(refresh, 30000);
})();
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