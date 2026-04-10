<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['username'];
    $role = $_POST['role'];
    $imgData = $_POST['image_data']; // This is the captured face

    // 1. Create the folder if it doesn't exist
    $folderPath = "labels/" . $name;
    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    // 2. Save the image file as 1.jpg
    $imgData = str_replace('data:image/jpeg;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $fileData = base64_decode($imgData);
    $fileName = $folderPath . "/1.jpg";
    file_put_contents($fileName, $fileData);

    // 3. Save to Database
    $sql = "INSERT INTO users (Name, Role) VALUES ('$name', '$role')";
    if ($conn->query($sql)) {
        echo "User Registered with Biometrics!";
        header("Location: register.php?status=success");
    }
}
?>