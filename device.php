<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Biometric Scanner | Smart Attendance</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background: #050810; 
            color: white; 
            font-family: 'Orbitron', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            overflow: hidden;
        }
        .scanner-card { 
            width: 500px; 
            padding: 40px; 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(20px); 
            border-radius: 30px; 
            border: 1px solid #00d2ff; 
            text-align: center; 
            box-shadow: 0 0 30px rgba(0, 210, 255, 0.2); 
        }
        .video-container { 
            position: relative; 
            width: 100%; 
            height: 350px; 
            background: #000; 
            border-radius: 20px; 
            overflow: hidden; 
            border: 2px solid #00d2ff; 
            margin-bottom: 20px; 
        }
        video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            transform: scaleX(-1); 
        }
        .scan-line { 
            position: absolute; 
            width: 100%; 
            height: 4px; 
            background: #00d2ff; 
            box-shadow: 0 0 20px #00d2ff; 
            top: 0; 
            animation: move 3s linear infinite; 
            display: none; 
            z-index: 10; 
        }
        @keyframes move { 
            0% { top: 0%; } 
            100% { top: 100%; } 
        }
        #status { 
            color: #00d2ff; 
            font-weight: bold; 
            font-size: 14px; 
            text-transform: uppercase; 
            margin-bottom: 20px; 
            letter-spacing: 1px;
        }
        .btn { 
            padding: 15px 40px; 
            border-radius: 50px; 
            border: none; 
            background: linear-gradient(45deg, #00d2ff, #3a7bd5); 
            color: white; 
            cursor: pointer; 
            font-weight: bold; 
            letter-spacing: 2px; 
            width: 100%; 
            transition: 0.3s;
        }
        .btn:hover {
            box-shadow: 0 0 20px rgba(0, 210, 255, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="scanner-card">
        <h2 style="letter-spacing:5px; margin-bottom: 30px;">BIOMETRIC AUTH</h2>
        <div class="video-container">
            <div class="scan-line" id="line"></div>
            <video id="video" autoplay muted playsinline></video>
        </div>
        <p id="status">WAKING UP SYSTEM...</p>
        <button class="btn" id="scanBtn" onclick="processAttendance()">VERIFY IDENTITY</button>
    </div>

    <script>
        const video = document.getElementById('video');
        const status = document.getElementById('status');
        const line = document.getElementById('line');
        let dbUsers = [];

        // Main Initialization Function
        async function startSystem() {
            try {
                // 1. Start Camera
                status.innerText = "ACCESSING CAMERA...";
                const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;

                // 2. Load AI Models (Matched to your 'models' folder filenames)
                status.innerText = "LOADING AI MODELS...";
                await faceapi.nets.tinyFaceDetector.loadFromUri('models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('models');
                
                // 3. Sync Database
                status.innerText = "SYNCING DATABASE...";
                const response = await fetch('get_all_faces.php');
                if (!response.ok) throw new Error("Could not find get_all_faces.php");
                dbUsers = await response.json();
                
                status.innerText = "SYSTEM ONLINE - READY";
            } catch (err) {
                console.error("Initialization Failed:", err);
                status.innerText = "INIT FAILED: " + err.message;
                status.style.color = "#ff4d4d";
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: err.message,
                    background: '#050810',
                    color: '#fff'
                });
            }
        }

        async function processAttendance() {
            if (dbUsers.length === 0) {
                Swal.fire('Please Wait', 'System is still initializing...', 'info');
                return;
            }

            line.style.display = "block";
            status.innerText = "ANALYZING BIOMETRICS...";

            try {
                // Use TinyFaceDetectorOptions to match your loaded files
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                               .withFaceLandmarks()
                                               .withFaceDescriptor();

                if (!detection) {
                    throw new Error("Face not captured. Please stand in better light and stay still.");
                }

                let matchedID = null;
                let minDistance = 0.65; // Threshold for recognition

                dbUsers.forEach(user => {
                    try {
                        let raw = typeof user.face_data === 'string' ? JSON.parse(user.face_data) : user.face_data;
                        const savedDescriptor = new Float32Array(Object.values(raw));
                        
                        const distance = faceapi.euclideanDistance(detection.descriptor, savedDescriptor);
                        console.log(`User ID: ${user.UserID} | Distance: ${distance.toFixed(4)}`);

                        if (distance < minDistance) {
                            minDistance = distance;
                            matchedID = user.UserID;
                        }
                    } catch (e) {
                        console.error("Error parsing user data:", user.UserID);
                    }
                });

                if (matchedID) {
                    status.innerText = "MATCH FOUND!";
                    status.style.color = "#00ff00";
                    setTimeout(() => {
                        window.location.href = `mark.php?user_id=${matchedID}&status=success`;
                    }, 1000);
                } else {
                    throw new Error("Identity not recognized. Please ensure you are registered.");
                }

            } catch (err) {
                console.error("Scan Error:", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: err.message,
                    background: '#050810',
                    color: '#fff'
                });
                line.style.display = "none";
                status.innerText = "READY FOR SCAN";
            }
        }

        // Start the logic on page load
        window.onload = startSystem;
    </script>
</body>
</html>