<?php
session_start();

// Check if includes/db.php exists and include it
$db_file = '../includes/db.php';
if (!file_exists($db_file)) {
    die("Error: Database configuration file not found.");
}

require $db_file;

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? "Database connection not established"));
}

require '../includes/sms_api.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize messages
$error_message = '';
$success_message = '';

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking_id'])) {
    $booking_id = (int)$_POST['confirm_booking_id'];
    
    // Update booking status to 'Booked'
    $update_sql = "UPDATE bookings SET status = 'Booked' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("i", $booking_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Fetch user mobile number and details using prepared statement
    $sql = "SELECT users.mobile_number, users.full_name, bookings.start_time, bookings.end_time, courts.name AS court_name 
            FROM bookings 
            JOIN users ON bookings.user_id = users.id 
            JOIN courts ON bookings.court_id = courts.id 
            WHERE bookings.id = ?";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            $sms_message = "Payment confirmed. Booking successful! Court: {$booking['court_name']}, Time: {$booking['start_time']} - {$booking['end_time']}. Thank you!";
            
            try {
                send_sms($booking['mobile_number'], $sms_message);
                $success_message = "Booking confirmed and SMS sent successfully.";
            } catch (Exception $e) {
                $error_message = "Booking confirmed but SMS failed to send: " . $e->getMessage();
            }
        } else {
            $error_message = "Error: Booking not found.";
        }
        $stmt->close();
    } else {
        $error_message = "Database error: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cafe Fifty Sports Center</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            background-image: url('../images/Court.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
        }

        .dashboard-container {
            width: 90%;
            font-family: 'Times New Roman', Times, serif;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            
        }

        .title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .title-section h2 {
            font-size: 24px;
        }

        .title-buttons {
            display: flex;
            gap: 20px;
        }

        .upload-button, .Home-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .upload-button:hover, .Home-button:hover {
            background-color: #0056b3;
        }

        .section-title {
            margin-top: 30px;
            font-size: 20px;
            font-weight: bold;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .bookings-table th, .bookings-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .bookings-table th {
            background-color: #007bff;
            color: white;
        }

        .bookings-table td {
            background-color: #f9f9f9;
        }

        .status-booked {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .action-buttons a, .action-buttons button {
            padding: 6px 12px;
            background-color: #ff3333;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }

        .action-buttons a:hover, .action-buttons button:hover {
            background-color: #ff0000;
        }

        .screenshot-img {
            width: 100px;
            height: 100px;
            cursor: pointer;
            object-fit: cover;
        }

        .screenshot-img:hover {
            opacity: 0.7;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .close {
            color: white;
            font-size: 40px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 25px;
            background: none;
            border: none;
        }

        .close:hover {
            color: #ff3333;
        }
    

        .confirm-button, .delete-button {
            padding: 8px 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .confirm-button {
            background-color: #28a745;
            color: white;
        }

        .confirm-button:hover {
             background-color: #218838;
        }

        .delete-button {
            background-color: #dc3545;
            color: white;
        }

        .delete-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="title-section">
            <h2>Admin Confirmation Page</h2>
            <div class="title-buttons">
                <a href="upload_images.php" class="upload-button">Upload Payment Images</a>
                <form method="post" style="display: inline;">
                <a href="http://localhost/cafe_fifty/admin%20panel/index.php" class="Home-button">
                       Home
                       </a>
                </form>
            </div>
        </div>

        <h3 class="section-title">Bookings</h3>
        
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Mobile Number</th>
                    <th>Full Name</th>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Payment Method</th>
                    <th>Payment Screenshot</th>
                    <th>Total Price</th>
                    <th>Remaining Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT b.id, u.mobile_number, u.full_name, c.name AS court_name, b.booking_time, b.start_time, b.end_time, 
                        b.payment_method, b.payment_screenshot, b.total_price, IFNULL(b.status, 'Pending') as status
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN courts c ON b.court_id = c.id 
                ORDER BY b.booking_time DESC";

                $result = $conn->query($sql);

                if (!$result) {
                    echo "<tr><td colspan='12'>Error executing query: " . htmlspecialchars($conn->error) . "</td></tr>";
                } elseif ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Format start and end times
                        $start_time = date('h:i A', strtotime($row['start_time']));
                        $end_time = date('h:i A', strtotime($row['end_time']));

                        // Calculate remaining balance
                        $remaining_balance = $row['total_price'] / 2;

                        $payment_screenshot_path = str_replace(__DIR__ . '/../', '', $row['payment_screenshot']);
                        $status = $row['status'] == 'Booked' ? 'Booked' : 'Pending';
                        $status_class = $status == 'Booked' ? 'status-booked' : 'status-pending';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['booking_time'])); ?></td>
                            <td><?php echo $start_time; ?></td>
                            <td><?php echo $end_time; ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td>
                                <?php if (!empty($row['payment_screenshot'])): ?>
                                    <img src="../<?php echo htmlspecialchars($payment_screenshot_path); ?>" 
                                         alt="Payment Screenshot" 
                                         class="screenshot-img"
                                         onclick="openModal('../<?php echo htmlspecialchars($payment_screenshot_path); ?>')">
                                <?php else: ?>
                                    No screenshot available
                                <?php endif; ?>
                            </td>
                            <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                            <td>₱<?php echo number_format($remaining_balance, 2); ?></td>
                            <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                            <td class="action-buttons">
                                <?php if ($status != 'Booked'): ?>
                                    <form action="" method="post" style="display: inline;">
                                        <input type="hidden" name="confirm_booking_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="confirm-button">Confirm</button>
                                    </form>
                                <?php endif; ?>
                                <a href="delete_booking.php?id=<?php echo $row['id']; ?>" 
                                   class="delete-button" 
                                   onclick="return confirm('Are you sure you want to delete this booking?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='12' style='text-align: center; padding: 2rem;'>No bookings found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for viewing images -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');

        function openModal(imgSrc) {
            modal.style.display = "flex";
            modalImg.src = imgSrc;
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

