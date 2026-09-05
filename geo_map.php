<?php
require_once 'inc/header.php';
include 'config/db.php';

$db_conn = $db->connection;

// ---- Real donors with coordinates (students table) ----
$donors_res = mysqli_query($db_conn, "SELECT name, email, phone, status AS blood_group, latitude, longitude
    FROM students WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$donors_arr = [];
while ($row = mysqli_fetch_assoc($donors_res)) {
    $donors_arr[] = $row;
}

// ---- Real hospitals/blood banks (unique by name, from blood_requests) ----
$hospitals_res = mysqli_query($db_conn, "SELECT DISTINCT hospital_name, location, urgency_level, latitude, longitude
    FROM blood_requests WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$hospitals_arr = [];
while ($row = mysqli_fetch_assoc($hospitals_res)) {
    $hospitals_arr[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
  <title>Geo-Location Map | BloodLink Blood Bank System</title>
  <meta name="description"
    content="View blood donors and partner hospitals on an interactive live map. Find your nearest blood bank using BloodLink geo-location matching.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />