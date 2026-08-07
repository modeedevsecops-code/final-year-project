<?php
// Include necessary files and database connection
include 'inc/header.php';
include 'config/db.php';

// Admin-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dbb = new operations();

// Handle deletion of assignments
if (isset($_GET['delete_id'])) {
    global $db;
    $del_id = intval($_GET['delete_id']);
    mysqli_query($db->connection, "DELETE FROM projects WHERE project_id = $del_id");
    header('Location: assign.php?deleted=1');
    exit;
}

// Handle assignment submission
$dbb->add_project();
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Assign Hospital Officer | BloodLink Admin</title>
</head>
<body>
    <!-- Main Content -->
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5 mt-4">
            <div class="container bg-light p-4 rounded shadow-sm">
                <h3 class="mb-4 text-danger fw-bold">🤝 Assign Hospital Officer to Blood Donor</h3>
                
                <?php $dbb->display_message() ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Assignment deleted successfully.</div>
                <?php endif; ?>

                <div class="row">
                    <!-- Form Column -->
                    <div class="col-lg-5 border-end pe-lg-4 mb-4 mb-lg-0">
                        <h5 class="mb-3 text-secondary">New Assignment Form</h5>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Blood Bank / Hospital Location</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Aminu Kano Teaching Hospital, Kano" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="assigned_student" class="form-label fw-bold">Select Donor</label>
                                    <select class="form-control" id="assigned_student" name="assigned_student" required>
                                        <option value="" disabled selected>-- Choose Donor --</option>
                                        <?php
                                        // Fetch donors from the database
                                        $students = $dbb->get_students_to_assign_project();
                                        foreach ($students as $student) {
                                            echo "<option value='{$student['student_id']}'>{$student['name']} ({$student['year_of_study']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="assigned_supervisor" class="form-label fw-bold">Select Officer</label>
                                    <select class="form-control" id="assigned_supervisor" name="assigned_supervisor" required>
                                        <option value="" disabled selected>-- Choose Officer --</option>
                                        <?php
                                        // Fetch hospital officers with donor count
                                        $supervisors = $dbb->get_supervisors_with_student_count();
                                        foreach ($supervisors as $supervisor) {
                                            $count = $supervisor['student_count'];
                                            echo "<option value='{$supervisor['staff_id']}'>{$supervisor['staff_name']} ($count Assigned)</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label fw-bold">Donation Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="methodology" class="form-label fw-bold">Donation Frequency</label>
                                    <input type="text" class="form-control" id="methodology" name="methodology" placeholder="e.g. Every 3 months" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">Hospital / Center Address</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Full address of the hospital or blood bank..." required></textarea>
                            </div>
                            <button type="submit" name="btn_add_project" class="btn btn-danger w-100">Assign Officer</button>
                        </form>
                    </div>

                    <!-- Assignments Table Column -->
                    <div class="col-lg-7 ps-lg-4">
                        <h5 class="mb-3 text-secondary">Active Assignments</h5>
                        <?php
                        $projects = $dbb->get_all_projects();
                        if (empty($projects)):
                        ?>
                            <div class="alert alert-info text-center py-4">No active assignments found. Use the form to assign an officer.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle small">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Location</th>
                                            <th>Donor</th>
                                            <th>Officer</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($project['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['staff_name'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($project['status'] ?? '') === 'Completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo htmlspecialchars($project['status'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?delete_id=<?php echo $project['project_id']; ?>" 
                                                   class="btn btn-danger btn-xs py-0 px-1" 
                                                   onclick="return confirm('Delete this assignment?');">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
</body>
</html>
