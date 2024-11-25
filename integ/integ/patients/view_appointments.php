<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment</title>
    <link rel="stylesheet" href="/css/view_appointments.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<div id="hamburger-menu" class="hamburger">&#9776;</div>

<body>
    <div class="appointment-container">
        <?php
        session_start();
        if (!isset($_SESSION['patient_id'])) {
            header("Location: ../login/login_form.php?$error_message=Please login first");
        }

        // Get current user ID from session
        $loggedInUserId = $_SESSION['patient_id'];
        $conn = new mysqli("localhost", "root", "", "appointment_db");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check for tab parameter to control the active tab view
        $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming'; // Default to upcoming
        
        include 'sidebar.html';
        // Define the canCancelAppointment function only once
        function canCancelAppointment($appointmentDate, $status)
        {
            $currentDate = new DateTime();
            $appointmentDateTime = new DateTime($appointmentDate);
            $interval = $currentDate->diff($appointmentDateTime);

            // Allow cancellation if status is pending, regardless of time
            if (strtolower($status) === 'pending') {
                return true;
            }

            // For confirmed appointments, only allow cancellation if more than 24 hours away
            if (strtolower($status) === 'confirmed') {
                $isMoreThan24HoursAway = $interval->days > 0 || ($interval->days == 0 && $interval->h >= 24);
                return $isMoreThan24HoursAway;
            }

            // For any other status, don't allow cancellation
            return false;
        }
        ?>

        <div class="appointment-content">
            <h1>APPOINTMENTS</h1>
            <hr class="appointment-thin-line">
            <!-- Tabs -->
            <div class="tabs">
                <div class="tab <?php echo $currentTab == 'upcoming' ? 'active' : ''; ?>" data-tab="upcoming"
                    onclick="showTab('upcoming')">Upcoming</div>
                <div class="tab <?php echo $currentTab == 'past' ? 'active' : ''; ?>" data-tab="past"
                    onclick="showTab('past')">Past</div>
                <div class="tab <?php echo $currentTab == 'cancel' ? 'active' : ''; ?>" data-tab="cancel"
                    onclick="showTab('cancel')">Cancelled Appointments</div>
            </div>

            <div class="tab-content">
                <!-- Upcoming Appointments -->
                <div id="upcoming" class="tab-pane"
                    style="<?php echo $currentTab == 'upcoming' ? 'display: block;' : 'display: none;'; ?>">
                    <?php
                    $sql_upcoming = "SELECT appointment_id, selected_services, complaint, other_details, appointment_date, appointment_time, remarks, status 
                    FROM appointments
                    WHERE patient_id = ? AND (appointment_date >= CURDATE() OR appointment_date IS NULL)
                    AND (status != 'Cancelled' AND status != 'Completed') 
                    ORDER BY  appointment_id DESC";
                    $stmt_upcoming = $conn->prepare($sql_upcoming);
                    $stmt_upcoming->bind_param("i", $loggedInUserId);
                    $stmt_upcoming->execute();
                    $result_upcoming = $stmt_upcoming->get_result();

                    if ($result_upcoming->num_rows > 0) {
                        echo '<table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Complaint</th>
                                        <th>Other Details</th>
                                        <th>Time Block</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                        <th>Options</th>
                                    </tr>
                                </thead>
                                <tbody>';

                        while ($row = $result_upcoming->fetch_assoc()) {
                            $canCancel = canCancelAppointment($row['appointment_date'], $row['status']);
                            $cancelButtonTitle = $canCancel ? "Cancel this appointment" : "Cannot cancel this appointment";

                            // Format the date if it's not TBD
                            $formattedDate = ($row['appointment_date'] && $row['appointment_date'] != 'TBD')
                                ? date('F d, Y', strtotime($row['appointment_date']))
                                : 'TBD';

                            echo '<tr>
                                            <td class="appointment-id">' . htmlspecialchars($row['appointment_id']) . '</td> 
                                            <td>' . htmlspecialchars($formattedDate) . '</td>
                                            <td>' . htmlspecialchars($row['selected_services']) . '</td>
                                            <td>' . htmlspecialchars($row['complaint']) . '</td>
                                            <td>' . htmlspecialchars($row['other_details']) . '</td>
                                            <td>' . htmlspecialchars($row['appointment_time']) . '</td>
                                            <td>' . htmlspecialchars($row['remarks']) . '</td>
                                            <td>' . htmlspecialchars($row['status']) . '</td>
                                            <td>';

                            if ($canCancel) {
                                echo '<button class="cancel-btn" onclick="confirmCancel(\'' . htmlspecialchars($row['appointment_id']) . '\', true, \'' . htmlspecialchars($row['status']) . '\', \'' . htmlspecialchars($row['appointment_date']) . '\')">CANCEL APPOINTMENT</button>';
                            } else {
                                echo '<button class="cancel-btn" disabled title="' . $cancelButtonTitle . '">CANCEL APPOINTMENT</button>';
                            }
                            echo '</td></tr>';
                        }
                        echo '  </tbody>
                            </table>';
                    } else {
                        echo '<p>No upcoming appointments found.</p>';
                    }

                    $stmt_upcoming->close();
                    ?>
                </div>

                <!-- Past Appointments -->
                <div id="past" class="tab-pane"
                    style="<?php echo $currentTab == 'past' ? 'display: block;' : 'display: none;'; ?>">
                    <?php
                    $sql_past = "SELECT appointment_id, selected_services, complaint, other_details, appointment_date, appointment_time, remarks, status 
                                 FROM appointments
                                 WHERE patient_id = ? AND (appointment_date < CURDATE() OR status = 'Completed')
                                 ORDER BY  appointment_id DESC";
                    $stmt_past = $conn->prepare($sql_past);
                    $stmt_past->bind_param("i", $loggedInUserId);
                    $stmt_past->execute();
                    $result_past = $stmt_past->get_result();

                    if ($result_past->num_rows > 0) {
                        echo '<table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Complaint</th>
                                        <th>Other Details</th>
                                        <th>Time Block</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>';

                        while ($row = $result_past->fetch_assoc()) {
                            $formattedDate = date('F d, Y', strtotime($row['appointment_date']));
                            echo '<tr>
                                            <td class="appointment-id">' . htmlspecialchars($row['appointment_id']) . '</td> 
                                            <td>' . htmlspecialchars($formattedDate) . '</td>
                                            <td>' . htmlspecialchars($row['selected_services']) . '</td>
                                            <td>' . htmlspecialchars($row['complaint']) . '</td>
                                            <td>' . htmlspecialchars($row['other_details']) . '</td>
                                            <td>' . htmlspecialchars($row['appointment_time']) . '</td>
                                            <td>' . htmlspecialchars($row['remarks']) . '</td>
                                            <td>' . htmlspecialchars($row['status']) . '</td>
                                        </tr>';
                        }
                        echo '  </tbody>
                            </table>';
                    } else {
                        echo '<p>No past appointments found.</p>';
                    }

                    $stmt_past->close();
                    ?>
                </div>

                <!-- Cancelled Appointments -->
                <div id="cancel" class="tab-pane"
                    style="<?php echo $currentTab == 'cancel' ? 'display: block;' : 'display: none;'; ?>">
                    <?php
                    $sql_cancelled = "SELECT appointment_id, selected_services, complaint, other_details, appointment_date, appointment_time, remarks, status 
                                      FROM appointments 
                                      WHERE patient_id = ? AND status = 'Cancelled'
                                       ORDER BY  appointment_id DESC";
                    $stmt_cancelled = $conn->prepare($sql_cancelled);
                    $stmt_cancelled->bind_param("i", $loggedInUserId);
                    $stmt_cancelled->execute();
                    $result_cancelled = $stmt_cancelled->get_result();

                    if ($result_cancelled->num_rows > 0) {
                        echo '<table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Complaint</th>
                                        <th>Other Details</th>
                                        <th>Time Block</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>';

                        while ($row = $result_cancelled->fetch_assoc()) {
                            $formattedDate = date('F d, Y', strtotime($row['appointment_date']));
                            echo '<tr>
                                            <td class="appointment-id">' . htmlspecialchars($row['appointment_id']) . '</td> 
                                            <td>' . htmlspecialchars($formattedDate) . '</td>
                                            <td>' . htmlspecialchars($row['selected_services']) . '</td>
                                            <td>' . htmlspecialchars($row['complaint']) . '</td>
                                            <td>' . htmlspecialchars($row['other_details']) . '</td>
                                            <td>' . htmlspecialchars($row['appointment_time']) . '</td>
                                            <td>' . htmlspecialchars($row['remarks']) . '</td>
                                            <td>' . htmlspecialchars($row['status']) . '</td>
                                        </tr>';
                        }
                        echo '  </tbody>
                            </table>';
                    } else {
                        echo '<p>No cancelled appointments found.</p>';
                    }
                    $stmt_cancelled->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/viewappointments.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php
            if (isset($_SESSION['cancel_result'])) {
                $result = $_SESSION['cancel_result'];
                unset($_SESSION['cancel_result']); // Clear the session variable
                ?>
                Swal.fire({
                    icon: <?php echo $result['success'] ? "'success'" : "'error'"; ?>,
                    title: <?php echo $result['success'] ? "'Success!'" : "'Oops...'"; ?>,
                    text: <?php echo json_encode($result['message']); ?>,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                <?php
            }
            ?>
        });
    </script>
</body>

</html>