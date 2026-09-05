<?php
session_start();

// Force the plain visitor navbar on this page, even if logged in
$forceVisitorNav = true;

include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>BloodLink | Smart Blood Bank Management System</title>
</head>

<body>
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?>

        <!-- Hero Section -->
        <section class="py-6">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-7 col-lg-6 mt-5 text-center text-md-start">
                        <h1 class="fw-medium">
                            Smart Blood Bank <br />
                            <span class="fw-bold" style="color:#cc0000;">Management System</span>
                        </h1>
                        <p class="mt-3 mb-4">
                            BloodLink is a smart blood bank management platform designed to digitize donor registration, blood inventory tracking, and emergency blood requests. It connects donors to nearby hospitals using geo-location matching and sends instant emergency alerts when critical blood types are needed.
                        </p>
                        <a class="btn btn-lg btn-danger hover-top btn-glow" href="register.php">Get Started</a>
                    </div>
                    <div class="col-md-5 col-lg-6 order-md-1">
                        <img class="img-fluid" src="assets/img/illustrations/2.png" alt="BloodLink" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-4">
            <div class="container">
                <div class="card py-5 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 border-end">
                                <h3 class="fw-bolder text-1000 mb-0">1,200+</h3>
                                <p class="mb-0">Registered Donors</p>
                            </div>
                            <div class="col-md-4 border-end">
                                <h3 class="fw-bolder text-1000 mb-0">850+</h3>
                                <p class="mb-0">Donations Made</p>
                            </div>
                            <div class="col-md-4">
                                <h3 class="fw-bolder text-1000 mb-0">30+</h3>
                                <p class="mb-0">Partner Hospitals</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5">
            <div class="container">
                <div class="row text-center mb-4">
                    <div class="col-12">
                        <h2 class="fw-bold">Why BloodLink?</h2>
                        <p class="text-muted">A complete system to save lives through smarter blood management</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center p-3">
                            <div class="card-body">
                                <div class="mb-3 text-danger"><i class="fas fa-bell fa-2x"></i></div>
                                <h5 class="fw-bold">Emergency Alerts</h5>
                                <p class="text-muted small">Instantly notify compatible donors when a hospital urgently needs a specific blood type.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center p-3">
                            <div class="card-body">
                                <div class="mb-3 text-danger"><i class="fas fa-map-marked-alt fa-2x"></i></div>
                                <h5 class="fw-bold">Geo-Location Matching</h5>
                                <p class="text-muted small">Locate nearby blood banks and match recipients with donors in real-time proximity.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center p-3">
                            <div class="card-body">
                                <div class="mb-3 text-danger"><i class="fas fa-shield-alt fa-2x"></i></div>
                                <h5 class="fw-bold">Secure Roles</h5>
                                <p class="text-muted small">Role-based dashboards for Donors, Recipients, and Hospital Officers to ensure data security.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'inc/main_js.php'; ?>
</body>
</html>