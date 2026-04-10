<?php
include 'db_config.php';
include 'menu.php'; // This adds your navigation bar at the top

// 1. Handle the Update Logic (When Admin clicks Approve or Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] == 'approve') ? 'Approved' : 'Rejected';
    
    $update_sql = "UPDATE LeaveRequests SET Status = '$new_status' WHERE LeaveID = $id";
    if ($conn->query($update_sql)) {
        echo "<script>alert('Status updated to $new_status'); window.location='manage_leaves.php';</script>";
    }
}

// 2. Fetch all leave requests and JOIN with Users table to see names
$sql = "SELECT LeaveRequests.*, Users.Name 
        FROM LeaveRequests 
        JOIN Users ON LeaveRequests.UserID = Users.UserID 
        ORDER BY LeaveID DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Leaves</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #333; color: white; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white; font-weight: bold; }
        .approve { background-color: #28a745; margin-right: 5px; }
        .reject { background-color: #dc3545; }
        .status-Approved { color: #28a745; font-weight: bold; }
        .status-Rejected { color: #dc3545; font-weight: bold; }
        .status-Pending { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Manage Student Leave Requests</h1>
    <p>Review and update the status of student leave applications.</p>

    <table>
        <tr>
            <th>Student Name</th>
            <th>Reason for Leave</th>
            <th>Current Status</th>
            <th>Actions</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['Name']; ?></td>
                <td><?php echo $row['Reason']; ?></td>
                <td><span class="status-<?php echo $row['Status']; ?>"><?php echo $row['Status']; ?></span></td>
                <td>
                    <?php if($row['Status'] == 'Pending'): ?>
                        <a href="?action=approve&id=<?php echo $row['LeaveID']; ?>" class="btn approve">Approve</a>
                        <a href="?action=reject&id=<?php echo $row['LeaveID']; ?>" class="btn reject">Reject</a>
                    <?php else: ?>
                        <em>Processed</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No leave requests found.</td></tr>
        <?php endif; ?>
    </table>

</body>
</html>