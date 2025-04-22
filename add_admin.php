<?php
require 'includes/db.php';

// Define admin credentials
$username = "admin123";
$password = "admin123"; // Change this to a strong password

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert admin user into the database
$sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";

if ($conn->query($sql) === TRUE) {
    echo "Admin user created successfully.";
} else {
    echo "Error creating admin user: " . $conn->error;
}

$conn->close();
?>
