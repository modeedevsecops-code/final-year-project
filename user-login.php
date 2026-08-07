<?php
require_once('config/db.php');
$dbb = new operations();
$dbb->user_login();
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>BloodLink | Login Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/dashboard_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<main class="main" id="top">
    <section class="pt-5 pt-md-6">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-5 col-lg-7 text-lg-center">
                    <img class="img-fluid mb-5 mb-md-0" src="assets/img/illustrations/2.png" alt="BloodLink Login" />
                </div>
                
                <div class="col-md-7 col-lg-5 text-center text-md-start mt-4 pt-3">
                    <h2 class="mb-3 text-center">Login Portal</h2>
                    <p class="text-center">
                        Login as a registered Blood Donor, Hospital Officer, or Recipient to access your BloodLink dashboard.
                    </p>

                    <div class="card-body">
                        <form action="" method="post">
                            <div class="form-group mb-3">
                                <?php $dbb->display_message(); ?>
                                <label class="form-label">Select Role</label>
                                <select name="role" id="roleSelect" class="form-select" required onchange="toggleFields()">
                                    <option value="">-- Choose Role --</option>
                                    <option value="student">Blood Donor</option>
                                    <option value="supervisor">Hospital Officer</option>
                                    <option value="recipient">Recipient</option>
                                </select>
                            </div>

                            <!-- Donor Fields -->
                            <div id="studentField" style="display:none;">
                                <div class="form-group mb-3">
                                    <input type="text" name="reg_no" id="reg_no_field" class="form-control" placeholder="Enter Donor ID / Registration Number">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" name="student_password" id="student_pass_field" class="form-control" placeholder="Password">
                                </div>
                            </div>

                            <!-- Recipient Fields -->
                            <div id="recipientField" style="display:none;">
                                <div class="form-group mb-3">
                                    <input type="email" name="recipient_email" id="recipient_email_field" class="form-control" placeholder="Enter Recipient Email">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" name="recipient_password" id="recipient_pass_field" class="form-control" placeholder="Password">
                                </div>
                            </div>

                            <!-- Hospital Officer Fields -->
                            <div id="supervisorFields" style="display:none;">
                                <div class="form-group mb-3">
                                    <input type="email" name="email" id="email_field" class="form-control" placeholder="Officer Email">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" name="password" id="pass_field" class="form-control" placeholder="Password">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 mb-4" name="btn_user_login">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
function toggleFields() {
    const role = document.getElementById('roleSelect').value;
    const studentDiv = document.getElementById('studentField');
    const supervisorDiv = document.getElementById('supervisorFields');
    const recipientDiv = document.getElementById('recipientField');
    
    const regNo = document.getElementById('reg_no_field');
    const studentPass = document.getElementById('student_pass_field');
    const recipientEmail = document.getElementById('recipient_email_field');
    const recipientPass = document.getElementById('recipient_pass_field');
    const emailField = document.getElementById('email_field');
    const passField = document.getElementById('pass_field');

    // Hide all first
    studentDiv.style.display = 'none';
    supervisorDiv.style.display = 'none';
    recipientDiv.style.display = 'none';

    regNo.required = false;
    studentPass.required = false;
    recipientEmail.required = false;
    recipientPass.required = false;
    emailField.required = false;
    passField.required = false;

    if (role === 'student') {
        studentDiv.style.display = 'block';
        regNo.required = true;
        studentPass.required = true;
    } else if (role === 'supervisor') {
        supervisorDiv.style.display = 'block';
        emailField.required = true;
        passField.required = true;
    } else if (role === 'recipient') {
        recipientDiv.style.display = 'block';
        recipientEmail.required = true;
        recipientPass.required = true;
    }
}
</script>

<?php include 'inc/main_js.php'; ?>
</body>
</html>