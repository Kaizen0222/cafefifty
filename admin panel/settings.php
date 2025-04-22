<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Online Booking</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h4>DASHBOARD</h4>
                <nav>
                    <a href="http://localhost/cafe_fifty/admin%20panel/index.php"><i class="fas fa-home"></i> Home</a>
                </nav>
            </div>
            <div class="sidebar-section">
                <h4>REPORTS</h4>
                <nav>
                    <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                </nav>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1>SETTINGS</h1>
                </div>
            </header>

            <div class="content">
                <div class="settings-form">
                    <h2>Update Admin Credentials</h2>
                    <?php
                    if (isset($_SESSION['message'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
                        unset($_SESSION['message']);
                    }
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                    <form action="update.credentials.php" method="POST" id="credentialsForm">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_username">New Username</label>
                            <input type="text" id="new_username" name="new_username" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="save-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="settings.js"></script>
</body>
</html>