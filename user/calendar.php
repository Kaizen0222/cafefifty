<?php
// Database connection and initial queries remain the same
$dbPath = '../includes/db.php';

if (!file_exists($dbPath)) {
    die("Error: Database connection file not found. Please check the path: " . $dbPath);
}

require_once $dbPath;

// Check if the connection was successful
if (!isset($conn) || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

// Fetch all bookings with additional details
$sql = "SELECT b.id, b.start_time, b.end_time, b.court_id, b.status, b.sport, c.name as court_name 
        FROM bookings b 
        JOIN courts c ON b.court_id = c.id 
        WHERE b.status IN ('Pending', 'Booked')";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching bookings: " . $conn->error);
}

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Fetch all courts
$sql_courts = "SELECT id, name FROM courts";
$result_courts = $conn->query($sql_courts);

if (!$result_courts) {
    die("Error fetching courts: " . $conn->error);
}

$courts = [];
while ($row = $result_courts->fetch_assoc()) {
    $courts[$row['id']] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Fifty's Sports Center - Calendar</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
    <style>
        /* Previous styles remain the same */
        .fc-event {
            cursor: pointer;
        }
        .pending-event {
            background-color: #ffd700;
            border-color: #ffd700;
        }
        .booked-event {
            background-color: #32cd32;
            border-color: #32cd32;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .button:hover {
            background-color: #003d82;
        }
    </style>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px;">
        <a href="http://localhost/cafe_fifty/user_auth/pages/user_home.php" class="button">Home</a>
    </div>
    <div class="container">
        <h1>Cafe Fifty's Sports Center - Booking Calendar</h1>
        <div id="calendar"></div>
        <div id="hourly-view"></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var hourlyViewEl = document.getElementById('hourly-view');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?php echo json_encode(array_map(function($booking) {
                return [
                    'id' => $booking['id'],
                    'title' => $booking['court_name'] . ' - ' . $booking['sport'],
                    'start' => $booking['start_time'],
                    'end' => $booking['end_time'],
                    'className' => $booking['status'] === 'Pending' ? 'pending-event' : 'booked-event',
                    'extendedProps' => [
                        'status' => $booking['status'],
                        'court' => $booking['court_name'],
                        'sport' => $booking['sport']
                    ]
                ];
            }, $bookings)); ?>,
            eventClick: function(info) {
                alert('Booking Details:\n' +
                      'Court: ' + info.event.extendedProps.court + '\n' +
                      'Sport: ' + info.event.extendedProps.sport + '\n' +
                      'Status: ' + info.event.extendedProps.status + '\n' +
                      'Start: ' + info.event.start.toLocaleString() + '\n' +
                      'End: ' + info.event.end.toLocaleString());
            },
            dateClick: function(info) {
                showHourlyView(info.date);
            }
        });
        calendar.render();

        function showHourlyView(date) {
            var bookings = <?php echo json_encode($bookings); ?>;
            var courts = <?php echo json_encode($courts); ?>;
            var hours = [];
            for (var i = 0; i < 24; i++) {
                hours.push(i + ":00");
            }

            var hourlyHtml = "<h2>Availability for " + date.toDateString() + "</h2>";
            hourlyHtml += "<div class='hour-slots'>";

            hours.forEach(function(hour, index) {
                var nextHour = (index + 1) % 24 + ":00";
                var isUnavailable = false;
                var unavailableCourts = [];
                bookings.forEach(function(booking) {
                    var bookingStart = new Date(booking.start_time);
                    var bookingEnd = new Date(booking.end_time);
                    if (bookingStart.toDateString() === date.toDateString()) {
                        var slotStart = new Date(date.getFullYear(), date.getMonth(), date.getDate(), index);
                        var slotEnd = new Date(date.getFullYear(), date.getMonth(), date.getDate(), (index + 1) % 24);
                        if (bookingStart < slotEnd && bookingEnd > slotStart) {
                            isUnavailable = true;
                            unavailableCourts.push(booking.court_name + " (" + booking.sport + ")");
                        }
                    }
                });

                var className = isUnavailable ? 'hour-slot unavailable' : 'hour-slot';
                var displayHour = (index % 12 || 12) + ':00 ' + (index < 12 ? 'AM' : 'PM');
                hourlyHtml += "<div class='" + className + "'>" + displayHour;
                if (isUnavailable) {
                    hourlyHtml += " - Unavailable (" + unavailableCourts.join(", ") + ")";
                }
                hourlyHtml += "</div>";
            });

            hourlyHtml += "</div>";
            hourlyViewEl.innerHTML = hourlyHtml;
            hourlyViewEl.style.display = 'block';
        }
    });
    </script>
</body>
</html>

