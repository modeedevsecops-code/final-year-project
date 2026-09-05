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
                                <p class="text-muted text-center mb-4">Please select your role to proceed with registration.</p>

                                <!-- Role Selection Dropdown -->
                                <div class="form-group mb-4">
                                    <label class="form-label fw-bold">Select Registration Role</label>
                                    <select id="registerRoleSelect" class="form-select" onchange="toggleRegisterFields()">
                                        <option value="">-- Choose Role to Register --</option>
                                        <option value="donor">Blood Donor</option>
                                        <option value="recipient">Recipient</option>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Hospital Officer accounts are created by system administrators only.
                                    </small>
                                </div>

                                <script>
                                function toggleRegisterFields() {
                                    const role = document.getElementById('registerRoleSelect').value;
                                    document.getElementById('donorRegisterDiv').style.display = (role === 'donor') ? 'block' : 'none';
                                    document.getElementById('recipientRegisterDiv').style.display = (role === 'recipient') ? 'block' : 'none';
                                }
                                </script>

                                <!-- Donor Registration Form Container -->
                                <div id="donorRegisterDiv" style="display:none;">
                                    <?php include 'register_donor.php'; ?>
                                </div>

                                <!-- Recipient Registration Form Container -->
                                <div id="recipientRegisterDiv" style="display:none;">
                                    <?php include 'register_recipient.php'; ?>
                                </div>
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