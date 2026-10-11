<?php
require_once('config/db.php');
if (!empty($_SESSION['role'])) { header('Location: ' . bl_home_for_role($_SESSION['role'])); exit; }
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
                        Login as a registered Member (donor &amp; recipient) or Hospital Officer to access your BloodLink dashboard.
                    </p>

                    <div class="card-body">
                        <form action="" method="post">
                            <div class="form-group mb-3">
                                <?php $dbb->display_message(); ?>
                                <label class="form-label">Select Role</label>
                                <select name="role" id="roleSelect" class="form-select" required onchange="toggleFields()">
                                    <option value="">-- Choose Role --</option>
                                    <?php $selRole = $_POST['role'] ?? ($_GET['role'] ?? ''); ?>
                                    <option value="member" <?= $selRole === 'member' ? 'selected' : '' ?>>Member (Donor / Recipient)</option>
                                    <option value="officer" <?= $selRole === 'officer' ? 'selected' : '' ?>>Hospital Officer</option>
                                </select>
                            </div>

                            <!-- Member Fields -->
                            <div id="memberField" style="display:none;">
                                <div class="form-group mb-3">
                                    <input type="text" name="member_login" id="member_login_field" class="form-control" placeholder="Email or Member ID" value="<?= htmlspecialchars($_POST['member_login'] ?? '') ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" name="member_password" id="member_pass_field" class="form-control" placeholder="Password">
                                </div>
                            </div>

                            <!-- Hospital Officer Fields -->
                            <div id="officerFields" style="display:none;">
                                <div class="form-group mb-3">
                                    <input type="email" name="email" id="email_field" class="form-control" placeholder="Officer Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" name="password" id="pass_field" class="form-control" placeholder="Password">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 mb-3" name="btn_user_login">Login</button>
                            <p class="text-center small mb-1">New to BloodLink? <a href="register.php" class="text-danger fw-bold">Create a member account</a></p>
                            <p class="text-center small"><a href="index.php" class="text-muted">&larr; Back to home</a></p>
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
    const memberDiv = document.getElementById('memberField');
    const officerDiv = document.getElementById('officerFields');

    const memberLogin = document.getElementById('member_login_field');
    const memberPass = document.getElementById('member_pass_field');
    const emailField = document.getElementById('email_field');
    const passField = document.getElementById('pass_field');

    // Hide all first
    memberDiv.style.display = 'none';
    officerDiv.style.display = 'none';

    memberLogin.required = false;
    memberPass.required = false;
    emailField.required = false;
    passField.required = false;

    if (role === 'member') {
        memberDiv.style.display = 'block';
        memberLogin.required = true;
        memberPass.required = true;
    } else if (role === 'officer') {
        officerDiv.style.display = 'block';
        emailField.required = true;
        passField.required = true;
    }
}
toggleFields();
</script>

<?php include 'inc/main_js.php'; ?>
</body>
</html>