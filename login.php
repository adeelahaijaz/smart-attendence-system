<?php
session_start();
include 'db_config.php';

$error = "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Queries the 'users' table exactly as seen in phpMyAdmin
    $stmt = $conn->prepare("SELECT * FROM users WHERE Email = ? AND Password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['name'] = $user['Name'];

        // Role-based redirection logic
        if ($user['Role'] == 'Admin') {
            header("Location: register.php");
        } elseif ($user['Role'] == 'Teacher') {
            header("Location: teacher_dashboard.php");
        } elseif ($user['Role'] == 'Student') {
            header("Location: student_dashboard.php");
        } else {
            $error = "Role not recognized: " . htmlspecialchars($user['Role']);
        }
        exit();
    } else {
        // This triggers if no match is found for email/password
        $error = "Invalid Institutional Email or Access Key!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Portal | Login</title>
    <style>
        body { margin:0; background:#050810; height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; color:white; overflow:hidden; }
        .circle { position:absolute; width:400px; height:400px; background:radial-gradient(#00d2ff, transparent 70%); filter:blur(60px); opacity:0.1; z-index:-1; }
        .login-box { background:rgba(255,255,255,0.05); padding:50px; border-radius:25px; border:1px solid rgba(0,210,255,0.3); backdrop-filter:blur(15px); width:350px; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,0.5); }
        h2 { color:#00d2ff; letter-spacing:4px; text-transform:uppercase; margin-bottom:30px; }
        input { width:100%; padding:14px; margin:10px 0; background:rgba(0,0,0,0.5); border:1px solid #333; color:white; border-radius:10px; box-sizing:border-box; outline:none; }
        input:focus { border-color:#00d2ff; box-shadow:0 0 10px rgba(0,210,255,0.3); }
        button { width:100%; padding:15px; background:linear-gradient(45deg, #00d2ff, #3a7bd5); border:none; border-radius:10px; color:white; font-weight:bold; cursor:pointer; margin-top:15px; }
        .err { color:#ff3131; font-size:13px; margin-top:15px; padding:10px; background:rgba(255,49,49,0.1); border-radius:8px; border:1px solid #ff3131; }
    </style>
</head>
<body>
    <div class="circle"></div>
    <div class="login-box">
        <h2>PORTAL LOGIN</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Institutional Email" required>
            <input type="password" name="password" placeholder="Access Key" required>
            <button type="submit" name="login">ENTER SYSTEM</button>
        </form>
        <?php if($error) echo "<div class='err'>$error</div>"; ?>
    </div>
</body>
</html>