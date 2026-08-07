<?php
// Connect to DB
include 'config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=students_list.xls");
header("Pragma: no-cache");
header("Expires: 0");

$dbb = new operations();
$students = $dbb->get_students();

// Define 5 random venues
$venues = ["Sambisa", "HND 2 A", "TestFund Building", "Software Lab", "ND 2 B"];
$date = date("Y-m-d");

echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>Name</th>
        <th>Level</th>
        <th>Venue</th>
        <th>Date</th>
      </tr>";

$counter = 1;
foreach ($students as $student) {
    // Random venue
    $venue = $venues[array_rand($venues)];

    // Level (you already store it in `year_of_study`)
    $level = $student['year_of_study'];

    echo "<tr>
            <td>{$counter}</td>
            <td>{$student['name']}</td>
            <td>{$level}</td>
            <td>{$venue}</td>
            <td>{$date}</td>
          </tr>";
    $counter++;
}

echo "</table>";
?>
