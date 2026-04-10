<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db_config.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sid = $_SESSION['user_id'];

// 2. Fetch Personal Info
$user_query = $conn->query("SELECT Name, Department FROM users WHERE UserID = '$sid'");
$user_data = $user_query->fetch_assoc();

// 3. Fetch Attendance History
$history = $conn->query("SELECT * FROM attendance WHERE UserID = '$sid' ORDER BY LogTime DESC");

// 4. Calculate Attendance Percentage
$total_classes = 10; 
$present_result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE UserID = '$sid' AND Status = 'Present'");
$present_count = $present_result->fetch_assoc()['total'];
$percentage = ($total_classes > 0) ? ($present_count / $total_classes) * 100 : 0;

// 5. Fetch Leave Request History
$leaves = $conn->query("SELECT * FROM leave_requests WHERE UserID = '$sid' ORDER BY LeaveID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal | Smart Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { margin: 0; background: #050810; color: white; font-family: 'Segoe UI', sans-serif; }
        .container { padding: 40px; max-width: 1000px; margin: auto; }
        .glass-card { 
            background: rgba(255, 255, 255, 0.05); 
            padding: 25px; 
            border-radius: 20px; 
            border: 1px solid rgba(0, 210, 255, 0.2); 
            margin-bottom: 30px; 
            backdrop-filter: blur(10px);
        }
        h1, h3 { color: #00d2ff; text-transform: uppercase; letter-spacing: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #333; color: #00d2ff; }
        td { padding: 12px; border-bottom: 1px solid #222; font-size: 14px; }
        
        .status-present { color: #39ff14; font-weight: bold; }
        .status-pending { color: #00d2ff; }
        .status-approved { color: #39ff14; text-shadow: 0 0 5px #39ff14; }
        .status-rejected { color: #ff3131; }

        input, textarea { 
            width: 100%; padding: 12px; margin: 10px 0; 
            background: rgba(0,0,0,0.3); border: 1px solid #333; 
            color: white; border-radius: 8px; box-sizing: border-box;
        }
        .btn-send { 
            background: linear-gradient(45deg, #00d2ff, #3a7bd5); 
            color: white; border: none; padding: 12px 30px; 
            border-radius: 8px; cursor: pointer; font-weight: bold; 
        }
        .logout-btn { color: #ff3131; text-decoration: none; font-weight: bold; border: 1px solid #ff3131; padding: 8px 20px; border-radius: 5px; float: right; }
    </style>
</head>
<body>

<?php if(isset($_GET['status'])): ?>
<script>
    window.onload = function() {
        const status = "<?php echo $_GET['status']; ?>";
        if (status === 'success') {
            Swal.fire({
                title: 'ATTENDANCE MARKED',
                text: 'Biometric Identity Verified Successfully!',
                icon: 'success',
                background: '#050810',
                color: '#00d2ff',
                confirmButtonColor: '#3a7bd5'
            });
        } else if (status === 'exists') {
            Swal.fire({
                title: 'ALREADY MARKED',
                text: 'Your attendance is already recorded for today.',
                icon: 'info',
                background: '#050810',
                color: '#fff',
                confirmButtonColor: '#3a7bd5'
            });
        }
        // Cleans the URL so the alert doesn't repeat
        window.history.replaceState({}, document.title, "student_dashboard.php");
    }
</script>
<?php endif; ?>

<div class="container">
    <a href="logout.php" class="logout-btn">LOGOUT</a>
    <h1>WELCOME, <?php echo htmlspecialchars($user_data['Name'] ?? 'Student'); ?></h1>
    <p>Department: <?php echo htmlspecialchars($user_data['Department'] ?? 'General Science'); ?></p>

    <div class="glass-card">
        <h3>Attendance Overview</h3>
        <p>Total Classes: <?php echo $total_classes; ?> | Present: <?php echo $present_count; ?></p>
        <div style="width: 100%; background: #222; border-radius: 10px; height: 10px;">
            <div style="width: <?php echo $percentage; ?>%; background: #00d2ff; height: 100%; border-radius: 10px; box-shadow: 0 0 10px #00d2ff;"></div>
        </div>
        <p>Your Attendance Score: <strong><?php echo $percentage; ?>%</strong></p>
    </div>

    <div class="glass-card">
        <h3>Submit Leave Request</h3>
        <form action="submit_leave.php" method="POST">
            <textarea name="reason" rows="3" placeholder="Explain your reason for leave..." required></textarea>
            <button type="submit" name="submit_leave" class="btn-send">SEND TO TEACHER</button>
        </form>
    </div>

    <div class="glass-card">
        <h3>Request Status History</h3>
        <table>
            <tr>
                <th>Reason</th>
                <th>Status</th>
            </tr>
            <?php if($leaves->num_rows > 0): ?>
                <?php while($l = $leaves->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['Reason']); ?></td>
                    <td class="status-<?php echo strtolower($l['Status']); ?>">
                        <?php echo strtoupper($l['Status']); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" style="opacity:0.5;">No leave requests found.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="glass-card">
        <h3>Attendance Log</h3>
        <table>
            <tr>
                <th>Date & Time</th>
                <th>Status</th>
            </tr>
            <?php if($history->num_rows > 0): ?>
                <?php while($row = $history->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('M d, Y - h:i A', strtotime($row['LogTime'])); ?></td>
                    <td class="status-present"><?php echo $row['Status']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" style="opacity:0.5;">No attendance records found yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

</body>
</html>