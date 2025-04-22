<?php
session_start();
require '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: /cafe_fifty/user_auth/pages/login.php");
    exit();
}

// Fetch courts
$sql = "SELECT * FROM courts";
$result = $conn->query($sql);

// Fetch payment images
$sql_images = "SELECT * FROM payment_images";
$result_images = $conn->query($sql_images);
$images = [];
while ($row = $result_images->fetch_assoc()) {
    $images[$row['method']] = $row['image_path'];
}

// Fetch sports
$sql_sports = "SELECT * FROM sports";
$result_sports = $conn->query($sql_sports);

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT full_name, mobile_number FROM users WHERE id = $user_id";
$result_user = $conn->query($sql_user);
$user_data = $result_user->fetch_assoc();
$full_name = isset($user_data['full_name']) ? $user_data['full_name'] : $_SESSION['username'];
$mobile_number = isset($user_data['mobile_number']) ? $user_data['mobile_number'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Court - Cafe Fifty</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background-image: url('../images/Cafefiftycenter.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }

        .booking-container {
            width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label, input, select, button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }

        button {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 10px;
        }

        .button {
            margin-top: 20px;
        }

        .popup, .overlay {
            display: none;
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 10;
            width: 50%;
            max-width: 400px;
            text-align: center;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
        }

        .popup img {
            max-width: 100%;
        }

        .popup .close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <center><h2>Cafe Fifty's Sports Center</h2></center>
        
        <form action="book.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <label for="full_name">Username:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
            <label for="mobile_number">Mobile Number:</label>
            <input type="text" id="mobile_number" name="mobile_number" value="<?php echo $mobile_number; ?>" required>
            
            <label for="sports">Select Sports:</label>
            <select id="sports" name="sports" onchange="updatePrice()" required>
                <?php
                if ($result_sports->num_rows > 0) {
                    while ($row = $result_sports->fetch_assoc()) {
                        echo "<option value='{$row['id']}' data-price='{$row['price_per_hour']}'>{$row['sport_name']}</option>";
                    }
                } else {
                    echo "<option value=''>No sports available</option>";
                }
                ?>
            </select>
            
            <label for="court">Select Court:</label>
            <select id="court" name="court" required>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['name']}'>{$row['name']}</option>";
                    }
                } else {
                    echo "<option value=''>No courts available</option>";
                }
                ?>
            </select>
 
            <label for="start-time">Start Time:</label>
            <input type="time" id="start-time" name="start-time" onchange="calculatePrice()" required>
            <label for="end-time">End Time:</label>
            <input type="time" id="end-time" name="end-time" onchange="calculatePrice()" required>
            <p id="price_display">Total Price: ₱0.00</p>
            <p id="at_least_pay">At least Pay: ₱150</p>

            <h3>Payment Information</h3>
            <label for="payment_method">Select Payment Method:</label>
            <select id="payment_method" name="payment_method" onchange="togglePaymentFields()" required>
                <option value="gcash">GCash</option>
                <option value="paymaya">PayMaya</option>
                <option value="venue">Pay at Venue</option>
            </select>

            <div id="payment_image_container" style="display: none;">
                <img id="payment_image" src="/placeholder.svg" alt="Payment Image" style="max-width: 100%; display: block; margin-top: 10px;">
            </div>

            <div id="payment_screenshot_container">
                <label for="payment_screenshot">Upload Payment Screenshot:</label>
                <input type="file" id="payment_screenshot" name="payment_screenshot" accept="image/*">
            </div>

            <input type="hidden" id="total_price" name="total_price" value="">
            <input type="hidden" id="price_per_hour" name="price_per_hour" value="">
            
            <div style="margin-top: 15px;">
                <input type="checkbox" id="termsCheckbox" required>
                <label for="termsCheckbox">
                    I agree to the <a href="#" onclick="openTermsPopup()">Terms and Conditions</a>
                </label>
            </div>
            <button type="submit" class="button">Book Now</button>
        </form>
    </div>

    <script>
        
        var images = <?php echo json_encode($images); ?>;

        function calculatePrice() {
            const startTime = document.getElementById('start-time').value;
            const endTime = document.getElementById('end-time').value;
            const sportSelect = document.getElementById('sports');
            const pricePerHour = parseFloat(sportSelect.options[sportSelect.selectedIndex].dataset.price);
            
            document.getElementById('price_per_hour').value = pricePerHour.toFixed(2);
            
            if (startTime && endTime) {
                const start = new Date(`1970-01-01T${startTime}:00`);
                const end = new Date(`1970-01-01T${endTime}:00`);
                const hours = (end - start) / (1000 * 60 * 60);

                if (hours > 0) {
                    const totalPrice = hours * pricePerHour;
                    document.getElementById('price_display').innerText = 'Total Price: ₱' + totalPrice.toFixed(2);
                    document.getElementById('at_least_pay').innerText = 'At least Pay: ₱' + (totalPrice / 2).toFixed(2);
                    document.getElementById('total_price').value = totalPrice.toFixed(2);
                } else {
                    document.getElementById('price_display').innerText = 'Total Price: ₱0.00 (Invalid time selection)';
                    document.getElementById('at_least_pay').innerText = 'At least Pay: ₱0.00';
                    document.getElementById('total_price').value = '0.00';
                }
            }
        }

        function togglePaymentFields() {
            var paymentMethod = document.getElementById('payment_method').value;
            var paymentImageContainer = document.getElementById('payment_image_container');
            var paymentImage = document.getElementById('payment_image');
            var paymentScreenshotContainer = document.getElementById('payment_screenshot_container');
            
            if (paymentMethod === 'venue') {
                paymentScreenshotContainer.style.display = 'none';
                document.getElementById('payment_screenshot').removeAttribute('required');
            } else {
                paymentScreenshotContainer.style.display = 'block';
                document.getElementById('payment_screenshot').setAttribute('required', 'required');
                
                if (images[paymentMethod]) {
                    paymentImage.src = '../' + images[paymentMethod];
                    paymentImageContainer.style.display = 'block';
                } else {
                    paymentImageContainer.style.display = 'none';
                }
            }
        }

        function updatePrice() {
            const sportSelect = document.getElementById('sports');
            const pricePerHour = parseFloat(sportSelect.options[sportSelect.selectedIndex].dataset.price);
            document.getElementById('price_per_hour').value = pricePerHour.toFixed(2);
            
            // Calculate total price if times are already selected
            calculatePrice();
            
            // If no times selected yet, just show the price per hour as total
            if (!document.getElementById('start-time').value || !document.getElementById('end-time').value) {
                document.getElementById('price_display').textContent = 'Total Price: ₱' + pricePerHour.toFixed(2);
                document.getElementById('at_least_pay').textContent = 'At least Pay: ₱' + (pricePerHour / 2).toFixed(2);
                document.getElementById('total_price').value = pricePerHour.toFixed(2);
            }
        }

        function validateForm() {
            var paymentMethod = document.getElementById('payment_method').value;
            if (paymentMethod !== 'venue') {
                var paymentScreenshot = document.getElementById('payment_screenshot').files[0];
                if (!paymentScreenshot) {
                    alert('Please upload a payment screenshot.');
                    return false;
                }
            }
        
            var termsCheckbox = document.getElementById('termsCheckbox');
            if (!termsCheckbox.checked) {
                alert('You must agree to the terms and conditions to proceed.');
                return false;
            }
        
            return true;
        }

        function openTermsPopup() {
            document.getElementById('termsPopup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeTermsPopup() {
            document.getElementById('termsPopup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        // Initialize the price display
        updatePrice();
    </script>

    <!-- Terms and Conditions Popup -->
    <div id="termsPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closeTermsPopup()">&times;</span>
            <h3>Terms and Conditions</h3>
            <p>By booking a court, you agree to the following terms:</p>
            <ul>
                <li>At least half must be paid now, with the remaining half due after the event.</li>
                <li>All bookings must be made at least 24 hours in advance.</li>
                <li>Cancellation must be made at least 12 hours before the scheduled time.</li>
                <li>Failure to arrive within 15 minutes of the booked time may result in automatic cancellation.</li>
                <li>Please use the Facebook page or phone number listed on the homepage to contact us with any questions.</li>
            </ul>
            <button onclick="closeTermsPopup()">Close</button>
        </div>
    </div>
    <div id="overlay" class="overlay"></div>
</body>
</html>

