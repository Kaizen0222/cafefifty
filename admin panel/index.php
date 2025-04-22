<?php
session_start();
require_once "includes/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Modified SQL query to not automatically mark Gcash/PayMaya as Paid
$sql = "SELECT bookings.id, users.full_name, users.mobile_number, 
               DATE_FORMAT(bookings.booking_time, '%Y-%m-%d') AS booking_date, 
               DATE_FORMAT(bookings.start_time, '%h:%i %p') AS start_time, 
               DATE_FORMAT(bookings.end_time, '%h:%i %p') AS end_time,
               bookings.payment_method, 
               bookings.payment_status, /* Use the actual payment_status from the database */
               sports.sport_name, 
               bookings.total_price,
               bookings.status
        FROM bookings
        JOIN users ON bookings.user_id = users.id
        JOIN sports ON bookings.court_id = sports.id
        WHERE bookings.status IN ('Booked', 'Paid')";
$query = $conn->query($sql);

if (!$query) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Booking Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">

    <style>
        table {
            border: 1px solid #ddd;
            border-collapse: collapse;
            width: 100%;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background-color: #f4f4f4;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        /* Confirm button styling */
        .confirm-btn {
            padding: 6px 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .confirm-btn:hover {
            background-color: #218838;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="profile">
            <img src="<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'https://scontent.fmnl17-8.fna.fbcdn.net/v/t39.30808-1/340644325_658527149415834_6709513319615251904_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=104&ccb=1-7&_nc_sid=2d3e12&_nc_eui2=AeFjKsqX8myswg7a18AKqAQrEcsXw6ytRpARyxfDrK1GkCDknRG-gYw4YN6PQisMcrO3pIK1trN89fIK3116rQB5&_nc_ohc=tEW3aPQyt4oQ7kNvwFN9oo5&_nc_oc=AdkB17J7Y9bJ8_gqm5w3ENgPY6jpp4VSHwMQ-ksL-IK3a2Y05kP07JQhtR1EnDvBZUE&_nc_zt=24&_nc_ht=scontent.fmnl17-8.fna&_nc_gid=uLDdXKtCx06pJJxb6dWiEA&oh=00_AfEBU-z3qYXSfSikd8aIe8OHYutTjTpMnbKD_pWYTIl4Dg&oe=680B79F3'; ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <span>Welcome,</span>
                <h3><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?></h3>
            </div>
            <div class="profile-actions">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="sidebar-section">
            <h4>DASHBOARD</h4>
            <nav>
                <a href="#" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="http://localhost/cafe_fifty/admin/dashboard.php"><i class="fas fa-calendar"></i> Booking</a>
            </nav>
        </div>
        <div class="sidebar-section">
            <h4>REPORTS</h4>
            <nav>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </div>
    </aside>
    <main class="main-content">
        <header>
            <div class="header-title">
                <h1>LIST OF BOOKED</h1>
            </div>
            <div class="search-bar">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
        <div class="content">
            <div class="booked-section">
                <div class="section-header">
                    <h2>BOOKED</h2>
                    <div class="controls">
                        <button class="sort-btn"><i class="fas fa-sort"></i></button>
                        <button class="filter-btn"><i class="fas fa-filter"></i></button>
                        <button class="close-btn"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <table id="datatable-responsive" class="table table-bordered table-striped dt-responsive nowrap" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Mobile Number</th>
                        <th>Booking Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Payment Method</th>
                        <th>Sports</th>
                        <th>Price</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    while ($row = $query->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['full_name']}</td>
                                <td>{$row['mobile_number']}</td>
                                <td>{$row['booking_date']}</td>
                                <td>{$row['start_time']}</td>
                                <td>{$row['end_time']}</td>
                                <td>{$row['payment_method']}</td>
                                <td>{$row['sport_name']}</td>
                                <td>₱{$row['total_price']}</td>
                                <td>";
                        // Show Confirm button for all payment methods when status is Pending
                        if ($row['payment_status'] == 'Pending') {
                            echo "<button class='confirm-btn' data-booking-id='{$row['id']}'>Confirm</button>";
                        } else {
                            echo $row['payment_status'];
                        }
                        echo "</td>
                                <td>{$row['status']}</td>
                              </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Include DataTables JS -->
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#datatable-responsive').DataTable({
            responsive: true
        });

        // Handle confirm button click
        $('.confirm-btn').on('click', function() {
            if (confirm('Are you sure you want to confirm this payment?')) {
                var bookingId = $(this).data('booking-id');
                var button = $(this);
                
                // Show loading spinner
                button.html('<div class="spinner"></div>');
                button.prop('disabled', true);

                $.ajax({
                    url: 'update_payment_status.php',
                    type: 'POST',
                    data: { booking_id: bookingId },
                    success: function(response) {
                        console.log("Response:", response); // Log the response
                        if (response.trim() === 'success') {
                            button.replaceWith('<span style="color: green;">Paid</span>');
                        } else {
                            alert('Error updating payment status: ' + response);
                            button.html('Confirm');
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert("An error occurred: " + error);
                        button.html('Confirm');
                        button.prop('disabled', false);
                    }
                });
            }
        });
    });
</script>

</body>
</html>