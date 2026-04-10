<?php
session_start();
include 'db_config.php';

// 1. Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // header("Location: login.php");
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM Users WHERE UserID = $id");
    header("Location: register.php?msg=User Deleted");
    exit();
}

// HANDLE REGISTRATION
$message = "";
if (isset($_POST['face_data']) && !empty($_POST['face_data'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']); 
    $role = $_POST['role'];
    $dept = $conn->real_escape_string($_POST['dept']);
    $faceData = $_POST['face_data']; // This is the AI Descriptor (numbers)

    // Save to Database including the face_data
    $sql = "INSERT INTO Users (Name, Email, Password, Role, Department, face_data) 
            VALUES ('$name', '$email', '$password', '$role', '$dept', '$faceData')";

    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert success'>✨ Biometric Identity Created for $name!</div>";
    } else {
        $message = "<div class='alert error'>❌ Error: " . $conn->error . "</div>";
    }
}
$result = $conn->query("SELECT * FROM Users ORDER BY UserID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Console | Smart Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #050810; --accent: #00d2ff; --glass: rgba(255, 255, 255, 0.05); --red: #ff3131; }
        body { background: radial-gradient(circle at top right, #16213e, #050810); color: #fff; font-family: 'Poppins', sans-serif; margin: 0; padding: 40px; min-height: 100vh; }
        .container { max-width: 1100px; margin: auto; }
        .glass-card { background: var(--glass); backdrop-filter: blur(15px); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
        h2 { font-family: 'Orbitron', sans-serif; color: var(--accent); letter-spacing: 2px; margin-top: 0; font-size: 18px; }
        .registration-flex { display: flex; gap: 30px; align-items: flex-start; margin-top: 20px; }
        .camera-box { position: relative; width: 400px; height: 300px; background: #000; border-radius: 15px; overflow: hidden; border: 2px solid rgba(255,255,255,0.1); }
        video { width: 100%; height: 100%; object-fit: cover; }
        canvas { position: absolute; top: 0; left: 0; }
        .grid-form { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input, select { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); padding: 14px; border-radius: 10px; color: white; outline: none; }
        #submitBtn { 
            grid-column: span 2; padding: 15px; background: linear-gradient(90deg, #3a7bd5, #00d2ff); 
            border: none; border-radius: 10px; color: white; font-weight: bold; cursor: pointer; 
            font-family: 'Orbitron'; transition: 0.3s; opacity: 0.4; pointer-events: none;
        }
        #submitBtn.active { opacity: 1; pointer-events: auto; transform: scale(1.02); box-shadow: 0 0 20px rgba(0, 210, 255, 0.4); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--accent); border-bottom: 2px solid var(--glass); font-family: 'Orbitron'; font-size: 11px; }
        td { padding: 15px; border-bottom: 1px solid var(--glass); font-size: 14px; }
        .btn-delete { color: var(--red); border: 1px solid var(--red); padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 11px; }
        .alert { padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .success { border: 1px solid #39ff14; color: #39ff14; background: rgba(57, 255, 20, 0.1); }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 24px;">ADMIN CONSOLE</h2>
        <a href="logout.php" style="color: #888; text-decoration: none; font-size: 12px;">EXIT SYSTEM</a>
    </div>

    <?php echo $message; ?>

    <div class="glass-card">
        <h2>ENROLL NEW BIOMETRIC IDENTITY</h2>
        <div class="registration-flex">
            <div class="camera-box">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="overlay"></canvas>
                <div id="status-tag" style="position: absolute; bottom: 10px; width: 100%; text-align: center; font-size: 12px; color: var(--accent); background: rgba(0,0,0,0.5); padding: 5px 0;">
                    Initializing AI Models...
                </div>
            </div>

            <form id="regForm" method="POST" class="grid-form">
                <input type="text" name="name" id="userName" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Institutional Email" required>
                <input type="password" name="password" placeholder="Set Password" required>
                <input type="text" name="dept" placeholder="Department" required>
                <select name="role" style="grid-column: span 2;">
                    <option value="Student">Student</option>
                    <option value="Teacher">Teacher</option>
                </select>
                <input type="hidden" name="face_data" id="face_data">
                <button type="submit" id="submitBtn">FACE REQUIRED TO REGISTER</button>
            </form>
        </div>
    </div>

    <div class="glass-card">
        <h2>SECURE DATABASE</h2>
        <table>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Dept</th><th>Action</th></tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo $row['Name']; ?></strong></td>
                <td style="opacity: 0.6;"><?php echo $row['Email']; ?></td>
                <td><span style="color: var(--accent)"><?php echo $row['Role']; ?></span></td>
                <td><?php echo $row['Department']; ?></td>
                <td><a href="?delete=<?php echo $row['UserID']; ?>" class="btn-delete" onclick="return confirm('Erase Identity?')">DELETE</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const submitBtn = document.getElementById('submitBtn');
    const statusTag = document.getElementById('status-tag');
    const faceDataInput = document.getElementById('face_data');

    async function initAI() {
        try {
            // Loading all 3 models required for RECOGNITION
            await faceapi.nets.tinyFaceDetector.loadFromUri('models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('models');
            
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;
            
            statusTag.innerText = "AI ONLINE: POSITION FACE";
            startDetection();
        } catch (err) {
            statusTag.innerText = "Error loading models from 'models' folder";
            console.error(err);
        }
    }

    function startDetection() {
        const canvas = document.getElementById('overlay');
        const displaySize = { width: 400, height: 300 };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            faceapi.draw.drawDetections(canvas, resizedDetections);

            if (detections.length > 0) {
                statusTag.innerText = "FACE VERIFIED - READY TO ENROLL";
                statusTag.style.color = "#39ff14";
                submitBtn.innerText = "GENERATE BIOMETRIC ACCOUNT";
                submitBtn.classList.add('active');
                
                // Store the descriptor as a JSON string
                const descriptor = JSON.stringify(Array.from(detections[0].descriptor));
                faceDataInput.value = descriptor;
            } else {
                statusTag.innerText = "POSITION FACE IN FRAME";
                statusTag.style.color = "#00d2ff";
                submitBtn.innerText = "FACE REQUIRED TO REGISTER";
                submitBtn.classList.remove('active');
                faceDataInput.value = "";
            }
        }, 500);
    }

    initAI();
</script>
</body>
</html>