<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auth_user_id = $_SESSION['user_id'];

// First, get the username from user_auth table
$get_username_sql = "SELECT username FROM user_auth WHERE id = ?";
$stmt = $conn->prepare($get_username_sql);
$stmt->bind_param("i", $auth_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: User not found in authentication system.");
}

$auth_user = $result->fetch_assoc();
$username = $auth_user['username'];
$stmt->close();

// Now, find the corresponding user in the users table
$find_user_sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($find_user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // User doesn't exist in users table, so create one
    $create_user_sql = "INSERT INTO users (username, password, full_name) VALUES (?, '', ?)";
    $stmt = $conn->prepare($create_user_sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt->close();
} else {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $stmt->close();
}

// Now use the correct user_id from the users table to fetch bookings
$sql = "SELECT b.id, c.name as court_name, b.booking_time, b.start_time, b.end_time, b.total_price, s.sport_name, b.status, b.payment_status 
        FROM bookings b 
        JOIN courts c ON b.court_id = c.id
        JOIN sports s ON b.sport = s.id
        WHERE b.user_id = ?
        ORDER BY b.booking_time DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Error preparing SQL statement: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/480785570_556000940801074_2294344429084287578_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHzQolwyaDlStJu0t49Zz2KTT270hEWzUVNPbvSERbNRQOZ0RpaZLO4JtqSp0RZTlp89GLJ11EYkkLLW1PGdLj4&_nc_ohc=3RI3RXK2WycQ7kNvwHKYg_Y&_nc_oc=AdmPikTLkbU-RGusL279g37IcJePxJO4Z6XExWMqVjLj9tm0hSPvGyPAy0Qh6R1Td_8&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=WLaYHHalcoMWtU7F2n46vQ&oh=00_AfFVBQXGBT0saxXsuyJc89HtZ2uOtdlTszN7MPklUV-apg&oe=680B60BC') no-repeat center center fixed;
            background-size: cover;
            padding: 20px;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1000px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: center;
            font-size: 14px;
            word-wrap: break-word;
        }

        th {
            background: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background:rgb(233, 29, 29);
            color: white;
            border: none;
            border-radius: 5px; 
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-home {
            background: #2d5af0 ;
            margin-right: 10px;
        }

        .btn-home:hover {
            background: #218838;
        }

        .status-paid {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                table-layout: auto;
            }
            
            th, td {
                font-size: 12px;
                padding: 8px 6px;
            }
            
            .container {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Your bookings:</p>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Sport</th>
                    <th>Court</th>
                    <th>Booking Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Total Price</th>
                    <th>Remaining Balance</th>
                    <th>Status</th>
                    <th>Half Payment</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): 
                    $remaining_balance = $row['total_price'] / 2; // Calculate half of the total price
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['sport_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['booking_time'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($row['start_time'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($row['end_time'])); ?></td>
                        <td>₱<?php echo number_format($row['total_price'], 2); ?></td>
                        <td>₱<?php echo number_format($remaining_balance, 2); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td class="<?php echo $row['payment_status'] == 'Paid' ? 'status-paid' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($row['payment_status']); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No bookings found.</p>
        <?php endif; ?>

        <a href="/cafe_fifty/user_auth/pages/user_home.php" class="btn btn-home">Back to Home</a>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>