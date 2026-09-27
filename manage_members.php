<?php
// DB + session first (no output yet), then guard, THEN header.
include 'config/db.php';
bl_require_role('admin');

$dbb = new operations();
bl_csrf_check();        // BL-14 (covers both add and delete POSTs below)
$dbb->add_donor();      // Adds a member (unified donor + recipient)
$dbb->delete_donor();   // Deletes a member

include 'inc/header.php';
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <body>
    <main class="main" id="top">
      <?php include 'inc/navbar.php'; ?>

      <div class="main-content">
        <div class="container bg-light p-4">
          <h2>Manage Members</h2>
          <p class="text-muted">A member is one account that can both donate and request blood.</p>
          <?php $dbb->display_message() ?>

          <!-- Form to add a new member -->
          <form action="" method="POST">
            <?php bl_csrf_field(); // BL-14 ?>

            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="email">Email <small class="text-muted">(used to log in)</small></label>
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
                  <label for="member_code">Member ID <small class="text-muted">(optional — auto-generated if blank)</small></label>
                  <input type="text" class="form-control" id="member_code" name="member_code"
                     placeholder="e.g. BL-KD-013">
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
                <label for="member_address">Address / Location</label>
                <input type="text" class="form-control" id="member_address" name="member_address" placeholder="Street, LGA, State" required>
              </div>
            </div><br>

            <div class="col-md-12">
              <div class="form-group">
                <label for="password">Password</label>
                <input type="text" class="form-control" id="password" name="password" maxlength="72" required>
              </div>
            </div>
            <br>
            <button type="submit" name="btn_add_member" class="btn btn-danger">Add Member</button>
          </form>

          <hr>

          <h3 class="mt-5">Registered Members</h3>
          <form method="POST" action="export_donors.php">
            <button type="submit" class="btn btn-outline-success mb-3">Export to Excel</button>
          </form>

          <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Member ID</th>
                <th>Blood Type</th>
                <th>Available</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $counter = 1;
              $members = $dbb->get_donors(); // Fetch all members
              foreach ($members as $m) {
                $avail = !empty($m['is_available'])
                    ? '<span class="badge bg-success">Yes</span>'
                    : '<span class="badge bg-secondary">No</span>';
                echo '<tr>
                        <td>' . $counter++ . '</td>
                        <td>' . htmlspecialchars($m['name']) . '</td>
                        <td>' . htmlspecialchars($m['email']) . '</td>
                        <td>' . htmlspecialchars($m['phone']) . '</td>
                        <td>' . htmlspecialchars($m['member_code']) . '</td>
                        <td><span class="badge bg-danger">' . htmlspecialchars($m['blood_group'] ?? 'N/A') . '</span></td>
                        <td>' . $avail . '</td>
                        <td>
                          <a href="edit_member.php?id=' . (int)$m['member_id'] . '" class="btn btn-warning btn-sm">Edit</a>
                          <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="' . htmlspecialchars(bl_csrf_token()) . '">
                            <input type="hidden" name="member_id" value="' . (int)$m['member_id'] . '">
                            <button type="submit" name="btn_delete_member" class="btn btn-danger btn-sm">Delete</button>
                          </form>
                        </td>
                      </tr>';
              }
              ?>
            </tbody>
          </table>
          </div>
        </div>
      </div>
    </main>

    <?php include 'inc/main_js.php'; ?>
  </body>
</html>
