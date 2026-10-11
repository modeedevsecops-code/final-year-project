<?php
include 'config/db.php';
$dbb = new operations();

// Contact form: saved to contact_messages, read by admins in Messages.
$contact_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_send_message'])) {
    bl_csrf_check();
    $c_name    = trim($_POST['name'] ?? '');
    $c_email   = strtolower(trim($_POST['email'] ?? ''));
    $c_subject = trim($_POST['subject'] ?? '');
    $c_message = trim($_POST['message'] ?? '');
    if (mb_strlen($c_name) < 2 || mb_strlen($c_name) > 100) $contact_errors[] = 'Please enter your name.';
    if (!filter_var($c_email, FILTER_VALIDATE_EMAIL)) $contact_errors[] = 'Please enter a valid email address.';
    if ($c_subject === '' || mb_strlen($c_subject) > 150) $contact_errors[] = 'Please enter a subject (up to 150 characters).';
    if (mb_strlen($c_message) < 10 || mb_strlen($c_message) > 5000) $contact_errors[] = 'Your message should be between 10 and 5,000 characters.';
    if (!$contact_errors) {
        $stmt = mysqli_prepare($db->connection, "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $c_name, $c_email, $c_subject, $c_message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        bl_notify($db->connection, 'admin', bl_admin_ids($db->connection), 'contact', 'New message: ' . mb_substr($c_subject, 0, 150),
                  "From $c_name ($c_email).", 'messages.php');
        header('Location: contact.php?sent=1');
        exit;
    }
}
$cv = function ($k) use ($contact_errors) { return $contact_errors ? htmlspecialchars((string)($_POST[$k] ?? '')) : ''; };
?>

<?php include 'inc/header.php'; ?>
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
                <?php if (isset($_GET['sent'])): ?>
                    <div class="alert alert-success">Thank you — your message has been sent. The BloodLink team will reply to your email.</div>
                <?php endif; ?>
                <?php if ($contact_errors) echo bl_errors_html($contact_errors); ?>
                <form action="contact.php" method="POST">
                    <?php bl_csrf_field(); ?>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= $cv('name') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $cv('email') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?= $cv('subject') ?>" placeholder="e.g. Partnership Inquiry, Donor Registration, Technical Support" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required minlength="10" maxlength="5000"><?= $cv('message') ?></textarea>
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
