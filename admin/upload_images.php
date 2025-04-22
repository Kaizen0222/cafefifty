<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize messages
$gcash_message = '';
$paymaya_message = '';

// Handle file uploads and deletions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check and create directories if they do not exist
    if (!is_dir(__DIR__ . '/../uploads')) {
        mkdir(__DIR__ . '/../uploads', 0755, true);
    }

    // Handle GCash image upload
    if (isset($_FILES['gcash_image']) && $_FILES['gcash_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = __DIR__ . '/../uploads/';
        $target_file = $target_dir . basename($_FILES["gcash_image"]["name"]);
        if (move_uploaded_file($_FILES["gcash_image"]["tmp_name"], $target_file)) {
            $sql = "UPDATE payment_images SET image_path='$target_file' WHERE method='gcash'";
            if ($conn->query($sql) === TRUE) {
                $gcash_message = "GCash image uploaded successfully.";
            } else {
                $gcash_message = "Error updating GCash image path in database.";
            }
        } else {
            $gcash_message = "Error uploading GCash image.";
        }
    } elseif (isset($_FILES['gcash_image'])) {
        $gcash_message = "Error with GCash image upload: " . $_FILES['gcash_image']['error'];
    }

    // Handle GCash image delete
    if (isset($_POST['delete_gcash_image'])) {
        $sql = "UPDATE payment_images SET image_path='' WHERE method='gcash'";
        if ($conn->query($sql) === TRUE) {
            $gcash_message = "GCash image deleted successfully.";
        } else {
            $gcash_message = "Error deleting GCash image from database.";
        }
    }

    // Handle PayMaya image upload
    if (isset($_FILES['paymaya_image']) && $_FILES['paymaya_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = __DIR__ . '/../uploads/';
        $target_file = $target_dir . basename($_FILES["paymaya_image"]["name"]);
        if (move_uploaded_file($_FILES["paymaya_image"]["tmp_name"], $target_file)) {
            $sql = "UPDATE payment_images SET image_path='$target_file' WHERE method='paymaya'";
            if ($conn->query($sql) === TRUE) {
                $paymaya_message = "PayMaya image uploaded successfully.";
            } else {
                $paymaya_message = "Error updating PayMaya image path in database.";
            }
        } else {
            $paymaya_message = "Error uploading PayMaya image.";
        }
    } elseif (isset($_FILES['paymaya_image'])) {
        $paymaya_message = "Error with PayMaya image upload: " . $_FILES['paymaya_image']['error'];
    }

    // Handle PayMaya image delete
    if (isset($_POST['delete_paymaya_image'])) {
        $sql = "UPDATE payment_images SET image_path='' WHERE method='paymaya'";
        if ($conn->query($sql) === TRUE) {
            $paymaya_message = "PayMaya image deleted successfully.";
        } else {
            $paymaya_message = "Error deleting PayMaya image from database.";
        }
    }
}

// Fetch existing images
$sql_images = "SELECT * FROM payment_images";
$result_images = $conn->query($sql_images);
$images = [];
while($row = $result_images->fetch_assoc()) {
    $images[$row['method']] = $row['image_path'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment Images</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('../images/Cafefiftycenter.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative; /* To position the dashboard button */
        }

        h2 {
            color: #0056b3;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .upload-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .upload-section h3 {
            margin-top: 0;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        input[type="file"] {
            margin-bottom: 1rem;
        }

        button {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #003d82;
        }

        .message {
            margin-bottom: 1rem;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .image-preview {
            max-width: 200px;
            display: block;
            margin: 1rem auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .delete-button:hover {
            background-color: #bd2130;
        }

        .dashboard-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color:rgb(38, 0, 255);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
        }

        .dashboard-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin/dashboard.php" class="dashboard-button">Bookings</a>
        <h2>Upload Payment Images</h2>
        <?php if ($gcash_message): ?>
            <p class="message <?php echo strpos($gcash_message, 'successfully') !== false ? 'success' : 'error'; ?>"><?php echo $gcash_message; ?></p>
        <?php endif; ?>
        <?php if ($paymaya_message): ?>
            <p class="message <?php echo strpos($paymaya_message, 'successfully') !== false ? 'success' : 'error'; ?>"><?php echo $paymaya_message; ?></p>
        <?php endif; ?>
        
        <!-- GCash Image Upload -->
        <div class="upload-section">
            <h3>GCash QR Code</h3>
            <form action="" method="post" enctype="multipart/form-data">
                <label for="gcash_image">Upload GCash Image:</label>
                <input type="file" id="gcash_image" name="gcash_image" accept="image/*">
                <button type="submit" class="button">Upload GCash Image</button>
                <?php if (isset($images['gcash']) && $images['gcash'] != ''): ?>
                    <img src="../<?php echo str_replace(__DIR__ . '/../', '', $images['gcash']); ?>" alt="GCash Image" class="image-preview">
                    <button type="submit" name="delete_gcash_image" class="delete-button">Delete GCash Image</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- PayMaya Image Upload -->
        <div class="upload-section">
            <h3>PayMaya QR Code</h3>
            <form action="" method="post" enctype="multipart/form-data">
                <label for="paymaya_image">Upload PayMaya Image:</label>
                <input type="file" id="paymaya_image" name="paymaya_image" accept="image/*">
                <button type="submit" class="button">Upload PayMaya Image</button>
                <?php if (isset($images['paymaya']) && $images['paymaya'] != ''): ?>
                    <img src="../<?php echo str_replace(__DIR__ . '/../', '', $images['paymaya']); ?>" alt="PayMaya Image" class="image-preview">
                    <button type="submit" name="delete_paymaya_image" class="delete-button">Delete PayMaya Image</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
