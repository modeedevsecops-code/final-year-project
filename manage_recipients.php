<?php
// Include necessary files and database connection
include 'inc/header.php';
include 'config/db.php';

// Admin-only access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dbb = new operations();
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Manage Recipients | BloodLink Admin</title>
</head>
<body>
    <!-- Main Content -->
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5">
            <div class="container bg-light p-4 rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-danger fw-bold">🩸 Manage Blood Recipients</h3>
                    <a href="add_recipient.php" class="btn btn-danger">Add New Recipient</a>
                </div>

                <?php 
                // If you have a method to display status messages in your class
                if (method_exists($dbb, 'display_message')) {
                    echo $dbb->display_message(); 
                }
                ?>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Blood Group</th>
                                <th>Units Required</th>
                                <th>Hospital</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch recipients using your database connection/class
                            // Assumes $pdo is accessible globally from config/db.php or via $dbb
                            try {
                                global $pdo;
                                $stmt = $pdo->query("SELECT * FROM recipients ORDER BY id DESC");
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td>" . $row['id'] . "</td>";
                                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                        echo "<td><span class='badge bg-danger'>" . htmlspecialchars($row['blood_group']) . "</span></td>";
                                        echo "<td>" . $row['units_required'] . "</td>";
                                        echo "<td>" . htmlspecialchars($row['hospital_name']) . "</td>";
                                        echo "<td>
                                                <a href='edit_recipient.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'>Edit</a>
                                                <a href='delete_recipient.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this recipient?\");'>Delete</a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>No recipients found.</td></tr>";
                                }
                            } catch (Exception $e) {
                                echo "<tr><td colspan='8' class='text-center text-danger'>Error loading data: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>