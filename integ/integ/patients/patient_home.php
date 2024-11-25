<?php
session_start();
// Include the rest of the home.php logic
include '../php/home.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Clinic Appointment</title>
    <link rel="stylesheet" href="/css/patient_home.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- Display SweetAlert Toast for Login Success -->
    <?php if ($login_success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Signed in successfully',
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true,
                    position: 'top-end'
                });
            });
        </script>
    <?php endif; ?>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="container">
        <?php include 'sidebar.html'; ?>
        <div class="content">
            <div class="welcome-message">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Using the sidebar, you may request a new consultation, view/edit your pending consultation requests,
                    and view/cancel/request a reschedule for your upcoming appointments.</p>
                <p class="second-sen">You can also see your next appointment below.</p>
            </div>

            <div class="appointment-section">
                <h2>Your next appointment</h2>
                <div class="appointment-details">
                    <div class="appointment-item">
                        <i class="fas fa-hashtag"></i>
                        <div class="appointment-info">
                            <span class="item-label">Appointment Reference No.</span>
                            <span class="item-value"><?php echo $appointment_id; ?></span>
                        </div>
                    </div>
                    <div class="appointment-item">
                        <i class="fas fa-info-circle"></i>
                        <div class="appointment-info">
                            <span class="item-label">Appointment Status</span>
                            <span class="item-value"><?php echo $status; ?></span>
                        </div>
                    </div>

                    <div class="appointment-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="appointment-info">
                            <span class="item-label">Consultation Date</span>
                            <span class="item-value">
                                <?php
                                if ($appointment_date === "N/A" || empty($appointment_date)) {
                                    echo "N/A";
                                } else {
                                    $date_obj = DateTime::createFromFormat('Y-m-d', $appointment_date);
                                    if ($date_obj) {
                                        $formatted_date = $date_obj->format('F j, Y');
                                        echo strtoupper($formatted_date);
                                    } else {
                                        echo "Invalid date";
                                    }
                                }
                                ?>

                            </span>
                        </div>
                    </div>
                    <div class="appointment-item">
                        <i class="fas fa-notes-medical"></i>
                        <div class="appointment-info">
                            <span class="item-label">Services</span>
                            <span class="item-value"> <?php
    if (!empty($selected_services)) {
        $services = explode(',', $selected_services); // Adjust delimiter if necessary
        foreach ($services as $service) {
            echo htmlspecialchars(trim($service)) . '<br>';
            error_log("Selected Services: " . $selected_services);

        }
    } else {
        echo 'N/A';
    }
    ?></span>
                        </div>
                    </div>
                    <div class="appointment-item">
                        <i class="fas fa-clock"></i>
                        <div class="appointment-info">
                            <span class="item-label">Time Block</span>
                            <span class="item-value"><?php echo $appointment_time; ?></span>
                        </div>
                    </div>

                    <div class="appointment-item">
                        <i class="fas fa-notes-medical"></i>
                        <div class="appointment-info">
                            <span class="item-label">Main Complaint</span>
                            <span class="item-value"><?php echo $complaint; ?></span>
                        </div>
                    </div>
                    <div class="appointment-item">
                        <i class="fas fa-comment-dots"></i>
                        <div class="appointment-info">
                            <span class="item-label">Remarks</span>
                            <span class="item-value">
                                <?php echo !empty($remarks) ? $remarks : '-'; ?>
                            </span>
                        </div>
                    </div>

                </div>
                <?php if ($appointment_id == 'N/A'): ?>
                    <div class="no-appointment">
                        <p class="no-appointment-message">No Appointment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/js/sidebar.js"></script>
</body>

</html>