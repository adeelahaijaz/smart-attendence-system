<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db_config.php';

// 1. Security: Only Teachers allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit();
}

// 2. Handle Leave Approvals/Rejections (Updated Table Name)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = ($_GET['action'] == 'approve') ? 'Approved' : 'Rejected';
    $conn->query("UPDATE leave_requests SET Status = '$status' WHERE LeaveID = $id");
    header("Location: teacher_dashboard.php?msg=Request $status Successfully");
    exit();
}

// 3. Get Selected Date (Defaults to Today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 4. Fetch Attendance (Updated Table Names)
$attendance_query = $conn->query("
    SELECT users.Name, users.Department, attendance.LogTime, attendance.Status 
    FROM attendance 
    JOIN users ON attendance.UserID = users.UserID 
    WHERE DATE(attendance.LogTime) = '$selected_date'
");

// 5. Fetch Pending Leaves (Updated Table Names)
$pending_leaves = $conn->query("
    SELECT leave_requests.*, users.Name 
    FROM leave_requests 
    JOIN users ON leave_requests.UserID = users.UserID 
    WHERE leave_requests.Status = 'Pending'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Command Center | Smart Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050810;
            --card: rgba(255, 255, 255, 0.03);
            --accent: #00d2ff;
            --neon-green: #39ff14;
            --neon-red: #ff3131;
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: radial-gradient(circle at top right, #0d1b2a, #050810);
            color: #fff; font-family: 'Poppins', sans-serif; margin: 0; padding: 20px;
        }

        .container { max-width: 1200px; margin: auto; animation: fadeIn 1s ease; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-family: 'Orbitron', sans-serif; color: var(--accent); font-size: 24px; text-transform: uppercase; }

        /* --- DATE SLIDER --- */
        .date-slider {
            display: flex; overflow-x: auto; gap: 15px; padding: 15px 0;
            scrollbar-width: thin; scrollbar-color: var(--accent) transparent;
        }
        .date-card {
            min-width: 100px; padding: 15px; background: var(--card);
            border: 1px solid var(--border); border-radius: 12px;
            text-align: center; cursor: pointer; transition: 0.3s;
        }
        .date-card:hover, .date-card.active {
            background: var(--accent); color: #000; border-color: var(--accent);
            transform: translateY(-5px); box-shadow: 0 0 15px var(--accent);
        }

        .notification-btn {
            position: relative; background: var(--card); border: 1px solid var(--border);
            padding: 10px 20px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 10px;
        }
        .badge {
            background: var(--neon-red); color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px;
        }

        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; }

        .glass-card {
            background: var(--card); backdrop-filter: blur(10px);
            padding: 25px; border-radius: 20px; border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--accent); border-bottom: 2px solid var(--border); text-transform: uppercase; font-size: 12px; }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 14px; }

        .status-present { color: var(--neon-green); font-weight: bold; text-shadow: 0 0 5px var(--neon-green); }
        
        .action-btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 11px; font-weight: bold; transition: 0.2s; }
        .approve { border: 1px solid var(--neon-green); color: var(--neon-green); margin-right: 5px; }
        .approve:hover { background: var(--neon-green); color: black; }
        .reject { border: 1px solid var(--neon-red); color: var(--neon-red); }
        .reject:hover { background: var(--neon-red); color: white; }

        .logout-link { color: var(--neon-red); text-decoration: none; font-family: 'Orbitron'; font-size: 12px; border: 1px solid var(--neon-red); padding: 8px 20px; border-radius: 50px; }
        .logout-link:hover { background: var(--neon-red); color: white; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

<div class="container">
    <?php if(isset($_GET['msg'])): ?>
        <div style="background: rgba(0, 210, 255, 0.1); border: 1px solid var(--accent); color: var(--accent); padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
            MODIFICATION SUCCESSFUL: <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <h1>TEACHER CONTROL PANEL</h1>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="notification-btn">
                <span>🔔 PENDING LEAVES</span>
                <span class="badge"><?php echo $pending_leaves->num_rows; ?></span>
            </div>
            <a href="logout.php" class="logout-link">TERMINATE SESSION</a>
        </div>
    </div>

    <div class="date-slider">
        <?php 
        for($i = -7; $i <= 0; $i++) {
            $date_val = date('Y-m-d', strtotime("$i days"));
            $is_active = ($date_val == $selected_date) ? 'active' : '';
            echo "<div class='date-card $is_active' onclick=\"window.location='?date=$date_val'\">
                    <span style='font-size:10px;'>".date('D', strtotime($date_val))."</span>
                    <strong style='display:block; font-size:20px;'>".date('d', strtotime($date_val))."</strong>
                    <span style='font-size:10px;'>".date('M', strtotime($date_val))."</span>
                  </div>";
        }
        ?>
    </div>

    <div class="grid">
        <div class="glass-card">
            <h2 style="margin-top:0; font-family: 'Orbitron'; font-size: 18px;">Log: <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Dept</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($attendance_query->num_rows > 0): ?>
                        <?php while($row = $attendance_query->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Department']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['LogTime'])); ?></td>
                            <td class="status-present"><?php echo strtoupper($row['Status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; opacity:0.5; padding:40px;">System reports no biometric logs for this cycle.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="glass-card">
            <h2 style="margin-top:0; font-family: 'Orbitron'; font-size: 18px;">Inbound Requests</h2>
            <?php if($pending_leaves->num_rows > 0): ?>
                <?php while($leave = $pending_leaves->fetch_assoc()): ?>
                    <div style="padding: 20px; background: rgba(255,255,255,0.05); border-radius: 15px; margin-bottom: 20px; border-left: 4px solid var(--accent);">
                        <strong style="color: var(--accent);"><?php echo htmlspecialchars($leave['Name']); ?></strong>
                        <p style="font-size: 13px; margin: 10px 0; line-height:1.5;"><?php echo htmlspecialchars($leave['Reason']); ?></p>
                        <div style="margin-top:15px;">
                            <a href="?action=approve&id=<?php echo $leave['LeaveID']; ?>" class="action-btn approve">APPROVE</a>
                            <a href="?action=reject&id=<?php echo $leave['LeaveID']; ?>" class="action-btn reject">REJECT</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="opacity: 0.5; text-align:center; padding:20px;">Buffer clear. All requests processed.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>