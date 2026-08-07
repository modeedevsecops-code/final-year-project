<?php
// Include necessary files and database connection
include 'inc/header.php';
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>About BloodLink | Smart Blood Bank Management System</title>
</head>

<body>
    <!-- Main Content -->
    <main class="main" id="top">

        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5">
            <div class="container bg-light p-4 rounded shadow-sm">
                <h2>About BloodLink</h2>
                <p>
                    Welcome to <strong>BloodLink – Smart Blood Bank Management System</strong>. This platform was developed
                    to digitize and modernize blood bank operations by connecting blood donors, hospitals, and administrators
                    through a centralized digital system. BloodLink enables real-time donor registration, blood inventory
                    management, emergency blood request alerts, and geo-location based donor-to-hospital matching.
                </p>

                <h3>Our Mission</h3>
                <p>
                    Our mission is to save lives by ensuring that the right blood type reaches the right patient at the right time.
                    We achieve this by replacing manual, paper-based blood bank processes with a reliable, secure, and efficient
                    digital platform that supports real-time monitoring, emergency notifications, and accurate donor record keeping.
                </p>

                <h3>System Objectives</h3>
                <ul>
                    <li>To maintain a centralized database of registered blood donors with their blood types and locations</li>
                    <li>To enable hospital officers to post emergency blood requests and alert compatible nearby donors</li>
                    <li>To provide real-time geo-location matching between donors and partner blood banks or hospitals</li>
                    <li>To track blood donation records, inventory levels, and generate analytical reports</li>
                    <li>To reduce delays in blood procurement during emergency medical situations</li>
                </ul>

                <h3>Key Features</h3>
                <ul>
                    <li><strong>Donor Registration:</strong> Register and manage blood donors with blood type, location, and contact info</li>
                    <li><strong>Emergency Alerts:</strong> Hospital officers can broadcast urgent blood requests to all eligible donors</li>
                    <li><strong>Geo-Location Map:</strong> Interactive map showing nearby blood banks, hospitals, and donor locations</li>
                    <li><strong>Blood Request Tracking:</strong> Monitor all incoming blood requests and their fulfillment status</li>
                    <li><strong>Role-Based Access:</strong> Separate portals for Administrators, Hospital Officers, and Donors</li>
                </ul>

                <h3>Development Team</h3>
                <ul>
                    <li>
                        <strong>BloodLink Development Team</strong> – System Developers<br>
                        Responsible for the analysis, design, and implementation of the BloodLink Smart Blood Bank
                        Management System in fulfillment of academic and institutional requirements.
                    </li>
                </ul>

                <h3>Contact Us</h3>
                <p>
                    For inquiries, feedback, or technical support regarding the BloodLink Blood Bank Management System,
                    please <a href="contact.php">contact us</a>.
                </p>
            </div>
        </section>

    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
</body>
</html>
