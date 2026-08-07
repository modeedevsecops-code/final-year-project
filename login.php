<?php
require_once('config/db.php');
$dbb = new operations();
$dbb->admin_login();
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <?php include 'inc/header.php'; ?>
  <body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">

     <?php include 'inc/navbar.php'; ?>

      <!-- ============================================-->
      <!-- Admin Login Section -->
      <!-- ============================================-->
      <section class="pt-5 pt-md-6">

        <div class="container">
          <div class="row align-items-center">

            <!-- Illustration -->
            <div class="col-md-5 col-lg-7 text-lg-center">
              <img class="img-fluid mb-5 mb-md-0"
                   src="assets/img/illustrations/2.png"
                   alt="BloodLink Administrator Login" />
            </div>

            <!-- Login Form -->
            <div class="col-md-7 col-lg-5 text-center text-md-start mt-4 pt-3">
              <h2 class="mb-3 text-center">Administrator Login</h2>
              <p class="text-muted text-center">
                This portal provides authorized blood bank administrators with secure access
                to manage donors, hospital officers, blood requests, emergency alerts,
                and geo-location data across the BloodLink Smart Blood Bank Management System.
              </p>

              <div class="d-flex">
                <div class="card-body">

                  <form action="" method="post">

                    <div class="form-group mb-4">
                      <?php $dbb->display_message(); ?>
                      <input type="text"
                             name="username"
                             class="form-control"
                             placeholder="Admin Username"
                             required
                             autofocus>
                    </div>

                    <div class="form-group mb-4">
                      <input type="password"
                             name="password"
                             class="form-control"
                             placeholder="Password"
                             required>
                    </div>

                    <div class="form-group mb-3">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" value="remember-me">
                          Remember Password
                        </label>
                      </div>
                    </div>

                    <button type="submit"
                            class="btn btn-primary btn-block mb-4"
                            name="btn_admin_login">
                      Login
                    </button>

                  </form>

                </div>
              </div>

            </div>
          </div>
        </div>

      </section>

    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->

    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
   <?php include 'inc/main_js.php'; ?>
  </body>

</html>
