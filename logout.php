<?php
// 1. Show errors if something goes wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Start session to access it
session_start();

// 3. Clear all session variables
$_SESSION = array();

// 4. Destroy the session entirely
session_destroy();

// 5. Redirect back to your login page
header("Location: login.php");
exit();
?>