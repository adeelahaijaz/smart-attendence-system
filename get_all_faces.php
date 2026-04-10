<?php
// get_all_faces.php
include 'db_config.php';

// We only want users who actually have face data registered
$sql = "SELECT UserID, Name, face_data FROM Users WHERE face_data IS NOT NULL AND face_data != ''";
$result = $conn->query($sql);

$users = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Set header to JSON so the Javascript in device.php can read it easily
header('Content-Type: application/json');
echo json_encode($users);
?>