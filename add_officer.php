<?php
include 'inc/header.php';
include 'config/db.php';

// Admin-only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

global $db;
$conn = $db->connection;
$ops  = new operations();
$banks = $ops->get_blood_banks();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_add_officer'])) {
    bl_csrf_check();      // BL-14
    $staff_name = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $position   = mysqli_real_escape_string($conn, trim($_POST['position'] ?: 'Blood Bank Officer'));
    $bank_id    = intval($_POST['blood_bank_id'] ?? 0);
    $bank_sql   = $bank_id ? "'$bank_id'" : 'NULL';
    // Hash the officer's password at creation (BL-12).
    $password   = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    if ($staff_name && $email && $phone && !empty(trim($_POST['password']))) {
        $q = "INSERT INTO staff (staff_name, phone, email, position, blood_bank_id, password)
              VALUES ('$staff_name', '$phone', '$email', '$position', $bank_sql, '$password')";
        if (mysqli_query($conn, $q)) {
            $msg = '<div class="alert alert-success text-center">Hospital Officer added successfully! <a href="manage_officers.php">View all officers</a></div>';
        } else {
            $msg = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        $msg = '<div class="alert alert-warning text-center">Please fill in all required fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Add Hospital Officer | BloodLink Admin</title>
</head>
<body>
<main class="main" id="top">
<?php include 'inc/navbar.php'; ?>
<br><br>

<section class="py-5">
    <div class="container" style="max-width:680px;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-3">
                <h4 class="mb-0 text-white">🏥 Add New Hospital Officer</h4>
            </div>
            <div class="card-body p-4">

                <?php echo $msg; ?>

                <form action="" method="POST">
                    <?php bl_csrf_field(); // BL-14 ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="staff_name"
                               placeholder="e.g. Dr. Aliyu Bello" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email"
                                   placeholder="officer@hospital.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone"
                                   placeholder="08012345678" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Role / Position</label>
                        <input type="text" class="form-control" name="position"
                               placeholder="e.g. Blood Bank Director, Chief Medical Officer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Blood Bank <span class="text-danger">*</span></label>
                        <select class="form-control form-select" name="blood_bank_id" required>
                            <option value="">-- Assign to a blood bank --</option>
                            <?php foreach ($banks as $b): ?>
                                <option value="<?= (int)$b['bank_id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Donations this officer confirms are banked here. Manage banks under <a href="manage_blood_banks.php">Blood Banks</a>.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Login Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password"
                               placeholder="Set a secure login password for this officer" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="btn_add_officer" class="btn btn-danger px-4">
                            Add Hospital Officer
                        </button>
                        <a href="manage_officers.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
