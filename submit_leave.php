<?php
// 1. Enable Error Reporting to stop the white screen
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db_config.php';

// 2. Check if the form was actually submitted
if (isset($_POST['submit_leave'])) {
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Session expired. Please login again.");
    }

    $sid = $_SESSION['user_id'];
    
    // 3. Clean the input to prevent SQL errors
    $reason = $conn->real_escape_string($_POST['reason']);
    
    // 4. Insert into the correct table (lowercase 'leave_requests')
    $sql = "INSERT INTO leave_requests (UserID, Reason, Status) VALUES ('$sid', '$reason', 'Pending')";
    
    if ($conn->query($sql)) {
        // 5. Redirect back to dashboard with a success message
        header("Location: student_dashboard.php?msg=RequestSent");
        exit();
    } else {
        // If the database has an error, show it here
        die("Database Error: " . $conn->error);
    }
} else {
    // If someone tries to access this file directly without clicking the button
    header("Location: student_dashboard.php");
    exit();
}
?>