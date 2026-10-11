<?php
// Member's own profile: contact details, address (re-geocoded for matching)
// and password. Blood group is shown but not self-editable — officers rely on
// it for compatibility, so changes go through an officer or admin.
include 'config/db.php';
bl_require_role('member');

$conn      = $db->connection;
$ops       = new operations();
$member_id = intval($_SESSION['member_id'] ?? 0);
$member    = $ops->get_donor_by_id($member_id);
$profile_msg = $password_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_profile'])) {
    bl_csrf_check();
    // Blood group and member ID aren't on this form: validate with the stored group, skip the ID.
    $input = array_merge($_POST, ['blood_group' => $member['blood_group'], 'member_code' => '', 'password' => '']);
    list($f, $errors) = bl_validate_member_input($input, $conn, $member_id, false);
    if ($errors) {
        $profile_msg = bl_errors_html($errors);
        $member = array_merge($member, array_intersect_key($_POST, array_flip(['name', 'email', 'phone', 'address'])));
    } else {
        $lat = $member['latitude']; $lng = $member['longitude']; $geo_note = '';
        if ($f['address'] !== (string)$member['address']) {
            list($lat, $lng) = bl_geocode($f['address']);
            if ($f['address'] !== '' && $lat === null) $geo_note = ' We couldn\'t find that address on the map, so distance matching won\'t include you until it\'s corrected.';
        }
        $address = $f['address'] !== '' ? $f['address'] : null;
        $stmt = mysqli_prepare($conn, "UPDATE members SET name=?, email=?, phone=?, address=?, latitude=?, longitude=? WHERE member_id=?");
        mysqli_stmt_bind_param($stmt, "ssssddi", $f['name'], $f['email'], $f['phone'], $address, $lat, $lng, $member_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['name']  = $f['name'];
        $_SESSION['email'] = $f['email'];
        $_SESSION['flash_profile'] = 'Profile saved.' . $geo_note;
        header('Location: profile.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_change_password'])) {
    bl_csrf_check();
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!password_verify($current, $member['password'])) {
        $password_msg = bl_errors_html(['Your current password is incorrect.']);
    } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $password_msg = bl_errors_html(['New password must be at least 8 characters and include a letter and a number.']);
    } elseif ($new !== $confirm) {
        $password_msg = bl_errors_html(['New passwords do not match.']);
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE members SET password=? WHERE member_id=?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $member_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        session_regenerate_id(true);
        $_SESSION['flash_password'] = 'Password changed.';
        header('Location: profile.php');
        exit;
    }
}

$flash_profile  = $_SESSION['flash_profile'] ?? null;
$flash_password = $_SESSION['flash_password'] ?? null;
unset($_SESSION['flash_profile'], $_SESSION['flash_password']);
$v = function ($k) use ($member) { return htmlspecialchars((string)($member[$k] ?? '')); };
?>
<?php include 'inc/header.php'; ?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="welcome-banner">
                <span class="badge-pill">MY PROFILE</span>
                <h1>Your Details</h1>
                <p>Member ID <strong><?= $v('member_code') ?></strong> &nbsp;|&nbsp; Blood group <strong><?= $v('blood_group') ?></strong>. Keep your phone and address current so officers can reach you and match you to nearby emergencies.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="quick-card" style="text-align:left;">
                        <h3 style="margin-bottom:1rem;">Contact &amp; location</h3>
                        <?php if ($flash_profile): ?><div class="alert alert-success"><?= htmlspecialchars($flash_profile) ?></div><?php endif; ?>
                        <?= $profile_msg ?>
                        <form method="POST">
                            <?php bl_csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= $v('name') ?>" required maxlength="100">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold" for="email">Email <span class="text-muted fw-normal">(used to log in)</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= $v('email') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold" for="phone">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= $v('phone') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="address">Address / Location</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?= $v('address') ?>" placeholder="e.g. Barnawa, Kaduna">
                                <small class="text-muted">
                                    <?= is_numeric($member['latitude'] ?? null) ? 'Located on the map.' : 'Not located on the map yet.' ?>
                                </small>
                            </div>
                            <button type="submit" name="btn_save_profile" class="btn btn-danger">Save Details</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="quick-card" style="text-align:left;">
                        <h3 style="margin-bottom:1rem;">Change password</h3>
                        <?php if ($flash_password): ?><div class="alert alert-success"><?= htmlspecialchars($flash_password) ?></div><?php endif; ?>
                        <?= $password_msg ?>
                        <form method="POST">
                            <?php bl_csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="current_password">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
                            </div>
                            <button type="submit" name="btn_change_password" class="btn btn-outline-danger">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
