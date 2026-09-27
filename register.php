<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>BloodLink | Register</title>
</head>

<body>
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?>

        <section class="py-6">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7 mt-5">
                        <div class="card shadow-sm border-0 p-4">
                            <div class="card-body">
                                <h2 class="fw-bold text-center mb-3" style="color:#cc0000;">Create an Account</h2>
                                <p class="text-muted text-center mb-4">
                                    Register as a Member — one account to both donate and request blood.
                                    <br><small>Hospital Officer accounts are created by system administrators only.</small>
                                </p>

                                <?php include 'register_member.php'; ?>
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