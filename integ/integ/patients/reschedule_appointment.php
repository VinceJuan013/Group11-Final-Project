<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Retrieve the appointment ID from the correct GET parameter
$appointmentId = $_GET['id'] ?? null;

// If not found in GET, try SESSION as a fallback
if (!$appointmentId) {
    $appointmentId = $_SESSION['appointment_id'] ?? null;
}

if (!$appointmentId) {
    echo "<strong style='color: red;'>No appointment ID found. Please try again.</strong>";
    exit;
}

// Store the appointment ID in the session for future use
$_SESSION['appointment_id'] = $appointmentId;

// Fetch the appointment date from the database
$servername = "localhost";
$dbname = "dental_db";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

$stmt = $conn->prepare("SELECT appointment_date FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $appointmentDate = $row['appointment_date'];
} else {
    $appointmentDate = date('Y-m-d'); // Fallback to today's date if not found
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment</title>
    <link rel="stylesheet" href="/css/reschedule.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="container">
        <?php include 'sidebar.html'; ?>
        <div class="main-content">
            <div class="form-section">
                <h1>RESCHEDULE APPOINTMENT</h1>
                <hr class="thin-line">
                <div id="slot-spinner" class="spinner"></div>

                <form id="appointmentForm" action="../php/process_reschedule.php" method="POST">
                <input type="hidden" name="appointment_id" id="appointmentId" value="<?php echo $appointmentId; ?>">

                    <input type="hidden" name="appointment_date" required id="appointmentDate"
                        value="<?php echo $appointmentDate; ?>">
                    <input type="hidden" name="time_slot" id="selectedTimeSlot" required>

                    <!-- Calendar Section -->
                    <div class="calendar-section">
                        <div class="calendar">
                            <div class="calendar-header">
                                <button>&#8249;</button>
                                <span id="monthYear"></span>
                                <button>&#8250;</button>
                            </div>
                            <div class="calendar-day">SUN</div>
                            <div class="calendar-day">MON</div>
                            <div class="calendar-day">TUE</div>
                            <div class="calendar-day">WED</div>
                            <div class="calendar-day">THU</div>
                            <div class="calendar-day">FRI</div>
                            <div class="calendar-day">SAT</div>
                            <div class="calendar-dates" id="calendarDates"></div>
                        </div>

                        <div class="slots-section">
                            <h2 class="slots-title">AVAILABLE SLOTS <br><span id="selectedDate">[Select a Date]</span>
                            </h2>
                            <div id="slotsContainer" class="slots-container"></div>
                        </div>
                    </div>
                    
                    <div id="form-spinner" class="spinner"></div>
                    <button class="confirm-btn" type="submit">RESCHEDULE APPOINTMENT</button>
                </form>
            </div>
        </div>
    </div>


    <script src="/js/reschedule.js"></script>
    <script src="/js/sidebar.js"></script>

</body>

</html>