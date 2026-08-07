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
                <?php 
                /* ================= ADMIN NAVBAR ================= */
                if (!empty($_SESSION['role']) && $_SESSION['role'] == 'admin' ) { 
                ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_donors.php">Manage Donors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_officers.php">Hospital Officers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assigned_donors.php">Assigned Donors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blood_requests.php">Blood Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notices.php">Emergency Alerts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="geo_map.php">Geo-Map</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php 
                /* ================= HOSPITAL OFFICER NAVBAR ================= */
                } elseif (!empty($_SESSION['role']) && $_SESSION['role'] == 'supervisor' ) { 
                ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="officer_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assigned_donors.php">Assigned Donors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="review_donations.php">Review Donations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notices.php">Emergency Alerts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="officer_comments.php">Comments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php 
                /* ================= DONOR NAVBAR ================= */
                } elseif (!empty($_SESSION['role']) && $_SESSION['role'] == 'student' ) { 
                ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="donor_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="donation_form.php">Donate Blood</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_officer.php">My Officer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="geo_map.php">Nearby Banks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php 
                /* ================= RECIPIENT NAVBAR ================= */
                } elseif (!empty($_SESSION['role']) && $_SESSION['role'] == 'recipient' ) { 
                ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="recipient_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="donation_form.php">Recieve Blood</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_officer.php">My Officer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="geo_map.php">Nearby Banks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php 
                /* ================= VISITOR NAVBAR ================= */
                } else { 
                ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="register.php">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user-login.php">Login</a>
                    </li>
                <?php } ?>
            </ul>
            
            <!-- User Avatar / Role Badge -->
            <?php if (empty($_SESSION['role'])) { ?>
                <a href="login.php" title="Administrator Login">
                    <img src="assets/img/user-icon.jpg" alt="Admin Login" width="45" height="45" class="rounded-circle border border-2 border-danger ms-lg-3" />
                </a>
            <?php } else {
                $role_labels = [
                    'admin'      => 'Admin',
                    'supervisor' => 'Officer',
                    'student'    => 'Donor',
                    'recipient'  => 'Recipient',
                ];
                $role_links = [
                    'admin'      => 'dashboard.php',
                    'supervisor' => 'officer_dashboard.php',
                    'student'    => 'donor_dashboard.php',
                    'recipient'  => 'recipient_dashboard.php',
                ];
                $current_role = $_SESSION['role'];
                $badge_label  = $role_labels[$current_role] ?? 'Account';
                $badge_link   = $role_links[$current_role] ?? 'index.php';
            ?>
                <a href="<?php echo $badge_link; ?>" class="btn btn-outline-danger rounded-pill order-0"><?php echo htmlspecialchars($badge_label); ?></a>
            <?php } ?>
        </div>
    </div>
</nav>