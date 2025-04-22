<?php
require '../includes/db.php';

$sql = "SELECT b.id, u.username, c.name as court_name, b.booking_time, b.payment_method, b.status 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN courts c ON b.court_id = c.id 
        WHERE b.status = 'Booked' 
        ORDER BY b.booking_time DESC";

$result = $conn->query($sql);

$bookings = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($bookings);

$conn->close();
?>