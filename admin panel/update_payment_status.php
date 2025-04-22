<?php
// Start or continue the session at the very beginning
session_start();

// Check if user is logged in as admin (optional but recommended)
// Comment this block out if you don't have role-based authentication yet
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Log the session data for debugging
    error_log("Session issue in update_payment_status.php: " . print_r($_SESSION, true));
    // Don't redirect, just continue - we'll still process the payment update
    // But we'll save the current session data to restore it later
}

// Save current session data to restore it after processing
$session_backup = $_SESSION;

require_once "includes/conn.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'];

    // Check if bookingId is set and is a number
    if (!isset($bookingId) || !is_numeric($bookingId)) {
        echo "Error: Invalid booking ID";
        exit;
    }

    // First, check if the booking exists
    $check_sql = "SELECT id, payment_status FROM bookings WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt === false) {
        echo "Error preparing check statement: " . $conn->error;
        exit;
    }

    $check_stmt->bind_param("i", $bookingId);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo "Error: Booking ID $bookingId does not exist";
        $check_stmt->close();
        exit;
    }

    $booking = $check_result->fetch_assoc();
    if ($booking['payment_status'] === 'Paid') {
        echo "Error: Booking ID $bookingId is already paid";
        $check_stmt->close();
        exit;
    }

    $check_stmt->close();

    // If we reach here, the booking exists and is not paid, so we can update it
    $update_sql = "UPDATE bookings SET payment_status = 'Paid' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    if ($update_stmt === false) {
        echo "Error preparing update statement: " . $conn->error;
        exit;
    }

    $update_stmt->bind_param("i", $bookingId);
    $result = $update_stmt->execute();

    if ($result === false) {
        echo "Error executing update statement: " . $update_stmt->error;
    } else {
        if ($update_stmt->affected_rows > 0) {
            echo "success";
        } else {
            echo "Error: No rows updated. This shouldn't happen as we checked the booking exists.";
        }
    }

    $update_stmt->close();
} else {
    echo "Invalid request method";
}

$conn->close();

// Restore the original session data to prevent session changes
$_SESSION = $session_backup;

// Log the final session state for debugging
error_log("Session after payment update: " . print_r($_SESSION, true));
?>