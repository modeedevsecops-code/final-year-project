<?php
// Connect to DB
include 'config/db.php';
bl_require_role('admin');   // BL-25: this handed the full donor table to any anonymous visitor.

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=donors_list.xls");
header("Pragma: no-cache");
header("Expires: 0");

$dbb = new operations();
$donors = $dbb->get_donors();

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
foreach ($donors as $donor) {
    // Random venue
    $venue = $venues[array_rand($venues)];

    // Blood group
    $level = $donor['blood_group'];

    echo "<tr>
            <td>{$counter}</td>
            <td>{$donor['name']}</td>
            <td>{$level}</td>
            <td>{$venue}</td>
            <td>{$date}</td>
          </tr>";
    $counter++;
}

echo "</table>";
?>
