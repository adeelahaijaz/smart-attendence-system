<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- NEW RECOGNITION LOGIC ---
// In a real demo, you'd compare the face descriptor here. 
// For your immediate fix, we will pass a 'match' variable from the frontend.

$is_match = isset($_GET['match']) && $_GET['match'] == 'true';

if (!$is_match) {
    // If the face doesn't match the logged-in user
    header("Location: device.php?status=no_match");
    exit();
}

$check = $conn->query("SELECT * FROM attendance WHERE UserID = '$user_id' AND DATE(LogTime) = '$today'");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO attendance (UserID, LogTime, Status) VALUES ('$user_id', NOW(), 'Present')");
    // REDIRECT BACK TO SCANNER, NOT DASHBOARD
    header("Location: device.php?status=success");
} else {
    header("Location: device.php?status=exists");
}
exit();
?>