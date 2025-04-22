<?php
$servername = "localhost";  // Change this if needed
$username = "root";         // Default XAMPP MySQL username
$password = "";             // Default XAMPP password is empty
$dbname = "cafe_fiftyy";     // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
