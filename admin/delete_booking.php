<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // Delete booking
    $sql = "DELETE FROM bookings WHERE id = $booking_id";
    if ($conn->query($sql) === TRUE) {
        echo "Booking deleted successfully.";
    } else {
        echo "Error deleting booking: " . $conn->error;
    }

    header("Location: dashboard.php");
} else {
    echo "Invalid booking ID.";
}
?>
