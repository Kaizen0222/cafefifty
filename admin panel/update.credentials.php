<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/db.php';

try {
    // Get form data
    $current_password = $_POST['current_password'] ?? '';
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Check if user_id is set in the session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User ID not set in session");
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        // Update credentials
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_username, $hashed_password, $_SESSION['user_id']);
        $stmt->execute();

        // Update session username
        $_SESSION['username'] = $new_username;

        $_SESSION['message'] = "Credentials updated successfully!";
    } else {
        $_SESSION['error'] = "Current password is incorrect!";
    }

} catch(Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

$conn->close();
header("Location: settings.php");
exit();
?>