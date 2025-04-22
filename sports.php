<?php
require_once "includes/conn.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create the courts table
$sql_create_table = "CREATE TABLE IF NOT EXISTS `courts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sport_name` varchar(255) NOT NULL,
  `price_per_hour` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql_create_table) === TRUE) {
    echo "Table 'courts' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to insert sports data
$sql_insert_data = "INSERT INTO courts (sport_name, price_per_hour) VALUES
        ('Basketball', 300),
        ('Volleyball', 250),
        ('Badminton', 200)";

if ($conn->query($sql_insert_data) === TRUE) {
    echo "New records created successfully";
} else {
    echo "Error: " . $sql_insert_data . "<br>" . $conn->error;
}

$conn->close();
?>
