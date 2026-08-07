<?php
// Include necessary files and database connection
include 'inc/header.php';
include 'config/db.php';

$dbb = new operations();
// $dbb->handle_contact_form(); // Uncomment when handler is ready
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Contact Us | BloodLink Blood Bank System</title>
</head>

<body>
    <!-- Main Content -->
    <main class="main" id="top">

        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5">
            <div class="container bg-light p-4">
                <h2>Contact BloodLink</h2>
                <p class="text-muted">
                  Have a question, need support, or want to register your blood bank as a partner? 
                  Send us a message and we'll get back to you as soon as possible.
                </p>
                <?php $dbb->display_message(); // Display success/error messages ?>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g. Partnership Inquiry, Donor Registration, Technical Support" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="btn_send_message" class="btn btn-danger">Send Message</button>
                </form>

                <hr class="mt-5">
                <div class="row mt-4">
                  <div class="col-md-6">
                    <h5>Our Address</h5>
                    <p>PMB 3011, Bayero University, Kano, Nigeria</p>
                  </div>
                  <div class="col-md-6">
                    <h5>Emergency Blood Hotline</h5>
                    <p><strong class="text-danger">📞 0800-BLOOD-NOW</strong><br>
                    Available 24/7 for emergency blood requests</p>
                  </div>
                </div>
            </div>
        </section>

    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
</body>
</html>
