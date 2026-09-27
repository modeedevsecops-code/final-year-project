<?php
// DB + session first (no output yet), then guard, THEN header (which emits HTML).
include 'config/db.php';
bl_require_role('admin');   // BL-25: this page leaked every donor name/email to anyone.

$dbb = new operations();
bl_csrf_check();        // BL-14 (covers both add and delete POSTs below)
$dbb->add_donor(); // Handles adding a new donor
$dbb->delete_donor(); // Handles deleting a donor

include 'inc/header.php';
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <body>
    <!-- Main Content -->
    <main class="main" id="top">
      <?php include 'inc/navbar.php'; ?><br><br>

      <section class="py-5">
        <div class="container bg-light p-4">
          <h2>Manage Blood Donors</h2>
          <?php $dbb->display_message() ?>

          <!-- Form to add a new donor -->
          <form action="" method="POST">
            <?php bl_csrf_field(); // BL-14 ?>

            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" name="email" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="phone">Phone</label>
                  <input type="text" class="form-control" id="phone" name="phone" maxlength="11" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="donor_code">Donor ID / Reg. Number</label>
                  <input type="text" class="form-control" id="donor_code" name="donor_code" required
                     placeholder="e.g. BLK/2024/00001">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="blood_type">Blood Type</label>
                  <select name="blood_group" id="blood_type" class="form-control">
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                  </select>
                </div>
              </div>
            </div><br>

            <div class="col-md-12">
              <div class="form-group">
                <label for="donor_address">Donor Address / Location</label>
                <input type="text" class="form-control" id="donor_address" name="donor_address" placeholder="Street, LGA, State" required>
              </div>
            </div><br>

            <div class="col-md-12">
              <div class="form-group">
                <label for="password">Password</label>
                <input type="text" class="form-control" id="password" name="password" maxlength="20" required>
              </div>
            </div>
            <br>
            <button type="submit" name="btn_add_donor" class="btn btn-danger">Add Donor</button>
          </form>

          <hr>

          <!-- Table to display donors -->
          <h3 class="mt-5">Registered Donors</h3>
          <form method="POST" action="export_donors.php">
            <button type="submit" class="btn btn-outline-success mb-3">Export to Excel</button>
          </form>

          <table class="table table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Donor ID</th>
                <th>Blood Type</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $counter = 1;
              $donors = $dbb->get_donors(); // Fetch all donors
              foreach ($donors as $donor) {
                echo '<tr>
                        <td>' . $counter++ . '</td>
                        <td>' . $donor['name'] . '</td>
                        <td>' . $donor['email'] . '</td>
                        <td>' . $donor['phone'] . '</td>
                        <td>' . $donor['donor_code'] . '</td>
                        <td><span class="badge bg-danger">' . htmlspecialchars($donor['blood_group'] ?? 'N/A') . '</span></td>
                        <td>
                          <a href="edit_donor.php?id=' . $donor['donor_id'] . '" class="btn btn-warning btn-sm">Edit</a>
                          <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="' . htmlspecialchars(bl_csrf_token()) . '">
                            <input type="hidden" name="donor_id" value="' . $donor['donor_id'] . '">
                            <button type="submit" name="btn_delete_donor" class="btn btn-danger btn-sm">Delete</button>
                          </form>
                        </td>
                      </tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
  </body>
</html>
