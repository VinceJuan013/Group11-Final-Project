<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment</title>
    <link rel="stylesheet" href="/css/add_appointment.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://apis.google.com/js/api.js"></script>
</head>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>

    <div class="container">
        <?php include 'sidebar.html'; ?>
        <div class="main-content">
                <h1>ADD APPOINTMENT</h1>
                <div class="thin-line"></div>
                <div class="content-wrapper">
                <div class="form-section">
                    <form class="appointment-form" action="../php/addappointment.php" id="appointmentForm" method="POST">
                    <div class="form-group services">
            <label class="label" for="service">SELECT SERVICE</label>
            <select name="selected_services" id="service">
                <option value="">SELECT</option>
                <option value="cleaning">Cleaning</option>
                <option value="filling">Crowns and bridges</option>
                <option value="custombraces">Custom Braces</option>
                <option value="digital orthodontics">Digital Orthodontics</option>
                <option value="palatal expanders">Palatal Expanders</option>
                <option value="tooth extraction">Tooth Extractions</option>
                <option value="tmj treatment">TMJ Treatment</option>
                <option value="wisdom teethremoval">Wisdom Teeth Removal</option>
                <option value="other">Other</option>
            </select>
            <div class="selected-tags"></div>
            <!-- Hidden input to store all selected services -->
            <input type="hidden" id="selected_services" name="selected_services" value="">
        </div>
                        <div class="form-group complaint">
                            <label class="label" for="complaint">COMPLAINT</label>
                            <input autocomplete="off" name="complaint" id="complain" class="input" type="text" required>
                        </div>
                        <div class="form-group complaint">
                            <label class="label" for="otherdetails">OTHER DETAILS</label>
                            <textarea autocomplete="off" name="other_details" id="other_details" class="inputotd"></textarea>
                        </div>
                        <div class="form-group followup">
                            <label class="label">IS THIS FOLLOW UP?</label>
                            <div class="radio-group">
                                <input type="radio" id="yes" name="followup" value="yes">
                                <label for="yes">YES</label>
                                <input type="radio" id="no" name="followup" value="no" checked>
                                <label for="no">NO</label>
                            </div>
                        </div>
                        <div class="form-group preferred-dentist">
                            <label class="label" for="preferred_dentist">PREREFERRED DENTIST</label>
                            <select name="preferred_dentist" id="preferred_dentist">
                                <option value="">SELECT</option>
                                <option value="Mrs. Arciaga - Juntilla">Mrs. Olivia Arciaga - Juntilla</option>
                                <option value="TBD">TBD</option>
                                <option value="TBD">TBD</option>
                            </select>
                        </div>
                        <input type="hidden" name="date" id="appointmentDate" required>
                        <input type="hidden" name="time_slot" id="selectedTimeSlot" required>
                        <input type="hidden" id="appointment_id" name="appointment_id">
                        <button type="submit" class="save-btn">SCHEDULE APPOINTMENT</button>
                    </form>
                </div>
                <div class="calendar-section">
                    <div class="calendar">
                        <div class="calendar-header">
                            <button onclick="prevMonth()" class="month-nav">&lt;</button>
                            <span id="monthYear"></span>
                            <button onclick="nextMonth()" class="month-nav">&gt;</button>
                        </div>
                        <div class="calendar-days">
                            <div class="calendar-day">SUN</div>
                            <div class="calendar-day">MON</div>
                            <div class="calendar-day">TUE</div>
                            <div class="calendar-day">WED</div>
                            <div class="calendar-day">THU</div>
                            <div class="calendar-day">FRI</div>
                            <div class="calendar-day">SAT</div>
                        </div>
                        <div class="calendar-dates" id="calendarDates"></div>
                    </div>
                    <div class="slots-section">
                        <h2 class="slots-title">AVAILABLE SLOTS <br><span id="selectedDate">[Select a Date]</span></h2>
                        <div id="slotsContainer" class="slots-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/addappointment.js"></script>
    <script src="/js/sidebar.js"></script>
</body>

</html>