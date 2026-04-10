<?php
include 'db_config.php';
$result = $conn->query("SELECT * FROM Users");
?>

<h2>Registered Users</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Role</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['UserID']; ?></td>
        <td><?php echo $row['Name']; ?></td>
        <td><?php echo $row['Role']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>