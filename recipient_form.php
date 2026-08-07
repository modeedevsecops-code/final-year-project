<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($db->connection, $_POST['email']);
    $password = $_POST['password'];

    // Query your recipients/patients table (adjust table name if different)
    $query = "SELECT * FROM recipients WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($db->connection, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password (or direct match depending on how you stored it)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['recipient_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = 'recipient';
            
            header("Location: recipient_dashboard.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>BloodLink | Recipient Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/dashboard_layout.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f6f9;
        }
        .auth-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
        }
        .auth-card h2 { color: #d9534f; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .btn-submit { width: 100%; background: #d9534f; color: white; padding: 10px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #c93b3b; }
        .error-msg { color: #d9534f; font-size: 14px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h2>Recipient Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="recipient_login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
    </div>
</div>

</body>
</html>