<?php
session_start();
include "../config/db_connect.php"; // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all fields exist and are not empty
    if (empty($_POST["username"]) || empty($_POST["password"]) || empty($_POST["confirm_password"]) || empty($_POST["email"])) {
        $_SESSION["error"] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $email = trim($_POST["email"]); // Get email from form

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["error"] = "Please enter a valid email address.";
        header("Location: register.php");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION["error"] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    // Check if username already exists
    $check_sql = "SELECT id FROM user_auth WHERE username = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION["error"] = "Username already exists. Please choose a different one.";
            $stmt->close();
            header("Location: register.php");
            exit();
        }
        $stmt->close();
    }

    // Check if email already exists
    $check_email_sql = "SELECT id FROM user_auth WHERE email = ?";
    if ($stmt = $conn->prepare($check_email_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION["error"] = "Email already in use. Please use a different email or try to recover your account.";
            $stmt->close();
            header("Location: register.php");
            exit();
        }
        $stmt->close();
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into `user_auth` with email
    $sql = "INSERT INTO user_auth (username, password, email) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        if ($stmt->execute()) {
            $_SESSION["success"] = "Registration successful! Please login.";
            $stmt->close();
            $conn->close();
            header("Location: login.php");
            exit();
        } else {
            $_SESSION["error"] = "Error: Could not register user. " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION["error"] = "Error: SQL query failed! " . $conn->error;
    }

    $conn->close();
    header("Location: register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - Cafe Fifty</title>
    <link rel="stylesheet" href="/cafe_fifty/user_auth/css/style.css">
    <style>
        body {
            background-image: url('../../images/Cafefiftycenter.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .container {
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
            margin: 5vh auto;
        }

        .register-box h2 {
            margin-bottom: 20px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        p {
            margin-top: 10px;
            font-size: 14px;
        }

        p a {
            color: #007BFF;
            text-decoration: none;
        }

        p a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="register-box">
        <h2>Register</h2>

        <!-- Display success or error messages -->
        <?php
        if (isset($_SESSION["error"])) {
            echo "<p class='error'>" . $_SESSION["error"] . "</p>";
            unset($_SESSION["error"]);
        }

        if (isset($_SESSION["success"])) {
            echo "<p class='success'>" . $_SESSION["success"] . "</p>";
            unset($_SESSION["success"]);
        }
        ?>

        <form action="register.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

</body>
</html>