<?php
session_start();
include '../config/config.php';
require '../../vendor/autoload.php';

// Start output buffering at the very beginning
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password, email FROM user_auth WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $email);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Check if email exists and is valid
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Your account doesn't have a valid email address. Please contact support.";
                error_log("Login attempt with invalid email for user ID: $id, Username: $username, Email: " . ($email ?: 'empty'));
            } else {
                // First, delete any existing OTPs for this user
                $delete_stmt = $conn->prepare("DELETE FROM user_otp WHERE user_id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                
                // Generate new OTP
                $otp = rand(100000, 999999);
                $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

                // Store OTP in database
                $otp_stmt = $conn->prepare("INSERT INTO user_otp (user_id, otp, expires_at) VALUES (?, ?, ?)");
                $otp_stmt->bind_param("iss", $id, $otp, $expires_at);
                $otp_stmt->execute();

                // Send OTP Email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Server settings - NO DEBUG OUTPUT
                    $mail->SMTPDebug = 0; // Turn off debug output
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = '202212086@btech.ph.education'; // Your email
                    $mail->Password = 'nlrx zzbw ptbg slra'; // Your password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('202212086@btech.ph.education', 'Cafe Fifty');
                    $mail->addAddress($email);

                    // Email content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Login OTP Code';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                            <h2 style='color: #333; text-align: center;'>Cafe Fifty - Login Verification</h2>
                            <p>Hello $username,</p>
                            <p>Your one-time password (OTP) for login is:</p>
                            <div style='text-align: center; padding: 10px; background-color: #f5f5f5; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                                $otp
                            </div>
                            <p>This OTP will expire in 5 minutes.</p>
                            <p>If you didn't request this OTP, please ignore this email.</p>
                            <p>Thank you,<br>Cafe Fifty Team</p>
                        </div>
                    ";
                    $mail->AltBody = "Your OTP is: $otp. It will expire in 5 minutes.";

                    $mail->send();
                    
                    // Store necessary information in session
                    $_SESSION['pending_user_id'] = $id;
                    $_SESSION['username'] = $username;
                    $_SESSION['otp_email'] = substr($email, 0, 3) . '***' . substr($email, strpos($email, '@'));
                    
                    // IMPORTANT: Do NOT store OTP in session
                    // $_SESSION['debug_otp'] = $otp; // REMOVED THIS LINE
                    
                    // Clean any output buffers before redirect
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Redirect to verification page
                    header("Location: verify_otp.php");
                    exit();
                } catch (Exception $e) {
                    error_log("PHPMailer Error: " . $mail->ErrorInfo);
                    $_SESSION['error'] = "Failed to send OTP email. Please try again or contact support.";
                }
            }
        } else {
            $_SESSION['error'] = "Invalid password.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cafe Fifty</title>
    <link rel="stylesheet" href="/cafe_fifty/user_auth/css/style.css">
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
        }
        
        .error {
            color: #e74c3c;
            background-color: #fde8e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
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
        
        p {
            text-align: center;
            margin-top: 15px;
        }
        
        a {
            color: #3498db;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login to Cafe Fifty</h2>
        <?php if (isset($_SESSION['error'])) { echo "<p class='error'>" . $_SESSION['error'] . "</p>"; unset($_SESSION['error']); } ?>
        <form action="" method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Sign Up</a></p>
        <p><a href="forgot_password.php">Forgot Password?</a></p>
    </div>
</body>
</html>

<?php
// Flush and end output buffering at the end of the file
ob_end_flush();
?>