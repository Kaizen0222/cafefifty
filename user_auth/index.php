<?php
session_start();

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: cafe_fifty/user_auth/pages/dashboard.php");
    exit();
} else {
    header("Location: cafe_fifty/user_auth/pages/login.php.php");
    exit();
}
?>
