<?php
session_start();
include '../config/config.php';

// Redirect if not coming from login page
if (!isset($_SESSION['pending_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$user_id = $_SESSION['pending_user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);
    
    // Check if the OTP matches without checking expiration
    $check_stmt = $conn->prepare("SELECT id FROM user_otp WHERE user_id = ? AND otp = ? ORDER BY id DESC LIMIT 1");
    $check_stmt->bind_param("is", $user_id, $entered_otp);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // OTP is valid - no expiration check
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['pending_user_id']);
        
        // Delete used OTP
        $delete_stmt = $conn->prepare("DELETE FROM user_otp WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Cafe Fifty</title>
    <style>
        body {
            background-image: url('../../images/Cafefiftycenter.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .form-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .error {
            color: #e74c3c;
            background-color: #fde8e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .success {
            color: #2ecc71;
            background-color: #e8f8f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        p {
            margin-bottom: 20px;
            color: #555;
        }
        
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            text-align: center;
            letter-spacing: 5px;
            font-size: 20px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        .resend {
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        
        .resend:hover {
            text-decoration: underline;
        }
        
        .timer {
            font-size: 14px;
            color: #777;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Verify Your Identity</h2>
        
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        
        <p>We've sent a verification code to your email address <?php echo isset($_SESSION['otp_email']) ? $_SESSION['otp_email'] : ''; ?>.</p>
        
        <form action="" method="post">
            <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required autofocus>
            <button type="submit">Verify</button>
        </form>
        
        <div class="timer" id="timer">Resend OTP in <span id="countdown">60</span> seconds</div>
        <a href="login.php" class="resend" id="resendLink" style="display: none;">Resend OTP</a>
    </div>
    
    <script>
        // Countdown timer for resend button
        let timeLeft = 60;
        const countdownElement = document.getElementById('countdown');
        const timerElement = document.getElementById('timer');
        const resendLink = document.getElementById('resendLink');
        
        function updateCountdown() {
            countdownElement.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                timerElement.style.display = 'none';
                resendLink.style.display = 'inline-block';
            }
            timeLeft -= 1;
        }
        
        let timer = setInterval(updateCountdown, 1000);
    </script>
</body>
</html>