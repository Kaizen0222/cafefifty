<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../user_auth/pages/login.php");
    exit();
}

include '../../config/db.php'; // Updated path

$username = $_SESSION['username'];

$sql = "SELECT * FROM bookings WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings</title>
</head>
<body>
    <h1>Your Bookings</h1>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Booking ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Facility</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['time']; ?></td>
                    <td><?php echo $row['facility']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>
</body>
</html>
