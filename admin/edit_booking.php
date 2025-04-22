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

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Fetch the booking details
if (isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];

    // Get the current booking details
    $sql = "SELECT b.id, u.mobile_number, u.full_name, b.booking_time, 
            c.id AS court_id, c.name AS court_name, b.start_time, b.end_time,
            b.sport, s.price_per_hour, b.total_price
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN courts c ON b.court_id = c.id
            LEFT JOIN sports s ON b.sport = s.sport_name
            WHERE b.id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $booking_id);
    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error_message = "Booking not found. SQL: $sql, Booking ID: $booking_id";
    }
    $stmt->close();
} else {
    $error_message = "No booking ID provided.";
}

// Handle form submission to update the booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $court_id = (int)$_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $sport = $_POST['sport'];

    // Combine date and time for start_time and end_time
    $start_datetime = date('Y-m-d H:i:s', strtotime("$booking_date $start_time"));
    $end_datetime = date('Y-m-d H:i:s', strtotime("$booking_date $end_time"));

    // Calculate new total price
    $start = new DateTime($start_datetime);
    $end = new DateTime($end_datetime);
    $duration = $end->diff($start);
    $hours = $duration->h + ($duration->days * 24);
    $minutes = $duration->i;
    $total_hours = $hours + ($minutes / 60);

    // Fetch the price per hour for the selected sport
    $price_query = "SELECT price_per_hour FROM sports WHERE sport_name = ?";
    $price_stmt = $conn->prepare($price_query);
    $price_stmt->bind_param("s", $sport);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    $price_row = $price_result->fetch_assoc();
    $price_per_hour = $price_row['price_per_hour'];
    $price_stmt->close();

    $new_total_price = $total_hours * $price_per_hour;

    // Update the booking details in the database
    $update_sql = "UPDATE bookings 
                   SET court_id = ?, booking_time = ?, start_time = ?, end_time = ?, 
                       sport = ?, total_price = ?
                   WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("issssdi", $court_id, $start_datetime, $start_datetime, $end_datetime, $sport, $new_total_price, $booking_id);

    if ($update_stmt->execute()) {
        $success_message = "Booking updated successfully.";
        // Refresh booking data after update
        $result = $conn->query("SELECT b.*, s.price_per_hour FROM bookings b LEFT JOIN sports s ON b.sport = s.sport_name WHERE b.id = $booking_id");
        $booking = $result->fetch_assoc();
    } else {
        $error_message = "Error updating booking: " . $conn->error;
    }

    $update_stmt->close();
}

// Fetch available sports
$sports_sql = "SELECT * FROM sports";
$sports_result = $conn->query($sports_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - Cafe Fifty Sports Center</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            color: var(--primary-blue);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group button {
            background-color:#004494;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .form-group button:hover {
            background-color:#003377;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border: 1px solid #f5c6cb;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border: 1px solid #c3e6cb;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .back-button {
            background-color:#004494;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #004494;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Booking</h2>

    <?php if ($error_message): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($booking)): ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="court_id">Select Court</label>
                <select name="court_id" id="court_id" required>
                    <option value="">Select a Court</option>
                    <?php
                    // Fetch available courts
                    $courts_sql = "SELECT * FROM courts";
                    $courts_result = $conn->query($courts_sql);
                    while ($court = $courts_result->fetch_assoc()) {
                        echo "<option value=\"{$court['id']}\" " . ($court['id'] == $booking['court_id'] ? 'selected' : '') . ">{$court['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sport">Select Sport</label>
                <select name="sport" id="sport" required>
                    <option value="">Select a Sport</option>
                    <?php
                    while ($sport = $sports_result->fetch_assoc()) {
                        echo "<option value=\"{$sport['sport_name']}\" " . ($sport['sport_name'] == $booking['sport'] ? 'selected' : '') . ">{$sport['sport_name']} - ₱{$sport['price_per_hour']}/hour</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="booking_date">Date</label>
                <input type="date" name="booking_date" id="booking_date" value="<?php echo date('Y-m-d', strtotime($booking['booking_time'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" id="start_time" value="<?php echo date('H:i', strtotime($booking['start_time'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" id="end_time" value="<?php echo date('H:i', strtotime($booking['end_time'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="total_price">Total Price</label>
                <input type="text" id="total_price" value="₱<?php echo number_format($booking['total_price'], 2); ?>" readonly>
            </div>

            <div class="form-group">
                <button type="submit" name="update_booking">Update Booking</button>
            </div>
        </form>
    <?php else: ?>
        <p>No booking data available.</p>
    <?php endif; ?>

    <a href="http://localhost/cafe_fifty/admin/dashboard.php" class="back-button">Back to Dashboard</a>
</div>

</body>
</html>

