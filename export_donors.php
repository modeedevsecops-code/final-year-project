<?php
// Members export (Excel-readable HTML table).
include 'config/db.php';
bl_require_role('admin');   // BL-25: this must never be world-readable.

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=members_list.xls");
header("Pragma: no-cache");
header("Expires: 0");

$dbb = new operations();
$members = $dbb->get_donors();
$date = date("Y-m-d");

echo "<table border='1'>";
echo "<tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Member ID</th>
        <th>Blood Group</th>
        <th>Available</th>
        <th>Last Donation</th>
      </tr>";

$counter = 1;
foreach ($members as $m) {
    $available = !empty($m['is_available']) ? 'Yes' : 'No';
    $last = $m['last_donation_date'] ?: 'Never';
    echo "<tr>
            <td>{$counter}</td>
            <td>" . htmlspecialchars($m['name']) . "</td>
            <td>" . htmlspecialchars($m['email']) . "</td>
            <td>" . htmlspecialchars($m['phone']) . "</td>
            <td>" . htmlspecialchars($m['member_code']) . "</td>
            <td>" . htmlspecialchars($m['blood_group'] ?? 'N/A') . "</td>
            <td>{$available}</td>
            <td>{$last}</td>
          </tr>";
    $counter++;
}

echo "</table>";
