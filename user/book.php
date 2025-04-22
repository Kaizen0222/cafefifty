<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug session information
    error_log("SESSION data: " . print_r($_SESSION, true));
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('You must be logged in to make a booking.'); window.location.href = '../login.php';</script>";
        exit();
    }
    
    // Get user ID from session - this is now from user_auth table
    $auth_user_id = $_SESSION['user_id'];
    
    // Find or create corresponding user in the users table
    try {
        // First, get the username from user_auth table
        $get_username = "SELECT username FROM user_auth WHERE id = ?";
        $stmt = $conn->prepare($get_username);
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $auth_user_id);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            throw new Exception("User not found in authentication system.");
        }
        
        $auth_user = $result->fetch_assoc();
        $username = $auth_user['username'];
        $stmt->close();
        
        // Now, find the corresponding user in the users table
        $find_user = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($find_user);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // User doesn't exist in users table, so create one
            $create_user = "INSERT INTO users (username, password, full_name) VALUES (?, '', ?)";
            $stmt = $conn->prepare($create_user);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $user_id = $conn->insert_id;
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
        }
        $stmt->close();
    } catch (Exception $e) {
        echo "<script>alert('Authentication error: " . addslashes($e->getMessage()) . "'); window.location.href = '../login.php';</script>";
        exit();
    }
    
    // Continue with the booking process using the user_id from the users table
    $full_name = $_POST['full_name'];
    $mobile_number = $_POST['mobile_number'];
    $court_name = $_POST['court'];
    $start_time = $_POST['start-time'];
    $end_time = $_POST['end-time'];
    $payment_method = $_POST['payment_method'];
    $payment_screenshot = '';
    $sport_id = $_POST['sports'];
    $total_price = $_POST['total_price'];
    $price_per_hour = $_POST['price_per_hour'];
    
    // Convert start and end times to DateTime objects and format them
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    
    // Get current date
    $current_date = date('Y-m-d');
    
    // Format with current date
    $start_time = $current_date . ' ' . $start->format('H:i:s');
    $end_time = $current_date . ' ' . $end->format('H:i:s');
    
    // Calculate the total duration in hours
    $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
    
    // Handle file upload if payment method is not 'Pay at Venue'
    if ($payment_method !== 'venue' && isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] == 0) {
        $target_dir = __DIR__ . '/../uploads/payments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . basename($_FILES["payment_screenshot"]["name"]);
        if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file)) {
            $payment_screenshot = $target_file;
        } else {
            die("Error uploading payment screenshot.");
        }
    }
    
    // Update user's mobile number if provided
    if (!empty($mobile_number)) {
        $stmt = $conn->prepare("UPDATE users SET mobile_number=? WHERE id=?");
        $stmt->bind_param("si", $mobile_number, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Check if the court exists
    $stmt = $conn->prepare("SELECT id FROM courts WHERE name=?");
    $stmt->bind_param("s", $court_name);
    $stmt->execute();
    $result_check_court = $stmt->get_result();
    
    if ($result_check_court->num_rows == 0) {
        die("Error: Selected court does not exist.");
    } else {
        $court = $result_check_court->fetch_assoc();
        $court_id = $court['id'];
    }
    $stmt->close();
    
    // Check for overlapping bookings
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE court_id=? AND 
                          (? < end_time AND ? > start_time)");
    $stmt->bind_param("iss", $court_id, $start_time, $end_time);
    $stmt->execute();
    $result_check_booking = $stmt->get_result();
    
    if ($result_check_booking->num_rows > 0) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var modal = document.createElement('div');
                    modal.style.position = 'fixed';
                    modal.style.top = '50%';
                    modal.style.left = '50%';
                    modal.style.transform = 'translate(-50%, -50%)';
                    modal.style.backgroundColor = 'white';
                    modal.style.padding = '20px';
                    modal.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)';
                    modal.style.zIndex = '1000';
                    modal.innerHTML = `
                        <p>This time slot is already booked.</p>
                        <button onclick=\"window.location.href = 'index.php';\">Go Back</button>
                    `;
                    document.body.appendChild(modal);
                    var overlay = document.createElement('div');
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';
                    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                    overlay.style.zIndex = '999';
                    document.body.appendChild(overlay);
                });
              </script>";
        exit();
    }
    $stmt->close();
    
    // Insert booking details including sport, total_price, and price_per_hour
    // FIXED: Make sure the number of ? placeholders matches the number of bind variables
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, court_id, sport, total_price, price, start_time, end_time, payment_method, payment_screenshot, booking_time, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending')");
    
    // FIXED: Make sure the type definition string matches the number of parameters
    // 'i' for integers, 'd' for doubles/decimals, 's' for strings
    $stmt->bind_param("iiiddssss", $user_id, $court_id, $sport_id, $total_price, $price_per_hour, $start_time, $end_time, $payment_method, $payment_screenshot);
    
    if ($stmt->execute()) {
        echo "<script>alert('Booking successful! Redirecting to your bookings...'); window.location.href = '/cafe_fifty/user_auth/pages/dashboard.php';</script>";
    } else {
        die("Error inserting booking: " . $stmt->error);
    }
    $stmt->close();
}
?>