<?php
// DB + session first, then guard, THEN header. add_recipient() is now
// implemented in the operations class (it was previously undefined — a fatal
// for any admin who opened this page). Form fields match the recipients table;
// the request-specific fields the old form collected (units/hospital/reason)
// belong on a blood request, not a recipient record.
include 'config/db.php';
bl_require_role('admin');

$dbb = new operations();
$dbb->add_recipient(); // handles the POST (CSRF-checked inside)

include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Add Blood Recipient | BloodLink Admin</title>
</head>
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?><br><br>

    <section class="py-5">
        <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
            <h3 class="mb-4 text-danger fw-bold">🩸 Add New Blood Recipient</h3>
            <?php $dbb->display_message(); ?>

            <form action="add_recipient.php" method="POST">
                <?php bl_csrf_field(); // BL-14 ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Blood Group</label>
                    <select name="blood_group" class="form-control form-select">
                        <option value="">Select Blood Group (optional)</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Address / Location</label>
                    <input type="text" name="address" class="form-control" placeholder="e.g. Barnawa, Kaduna">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Initial Password</label>
                    <input type="text" name="password" class="form-control" required
                           placeholder="Recipient uses this to log in">
                </div>

                <button type="submit" name="btn_add_recipient" class="btn btn-danger w-100">Save Recipient</button>
            </form>
        </div>
    </section>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
