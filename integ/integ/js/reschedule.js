let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = "";
let selectedDateElement = null;
const today = new Date(); // Get today's date
let holidayDates = [];  // This will store the holidays.
let appointmentDate = ""; // Store the original appointment date

function fetchHolidays() {
    const apiKey = 'AIzaSyDRNKH3lo-FaELDunaz3azWMXgJ31aY48g';
    const calendarId = 'en-gb.philippines%23holiday%40group.v.calendar.google.com'; // Use your correct calendar ID
    const url = `https://www.googleapis.com/calendar/v3/calendars/${calendarId}/events?key=${apiKey}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error fetching holidays: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.items && Array.isArray(data.items)) {
                holidayDates = data.items.map(event => {
                    const eventDate = new Date(event.start.dateTime || event.start.date);
                    return {
                        date: eventDate.toISOString().split('T')[0],  // Store only the date part (YYYY-MM-DD)
                        title: event.summary  // Store the event's title (holiday name)
                    };
                });
                generateCalendar(currentMonth, currentYear);  // Re-generate the calendar after fetching holidays
            } else {
                console.error("No holidays found or data format is incorrect");
            }
        })
        .catch(error => console.error('Error fetching holidays:', error));
}

function generateCalendar(month, year) {
    // Add event listeners for month navigation
    document.querySelector('.calendar-header button:first-child').addEventListener('click', prevMonth);
    document.querySelector('.calendar-header button:last-child').addEventListener('click', nextMonth);

    // Add event listener for form submission
    document.getElementById("appointmentForm").addEventListener("submit", handleFormSubmission);

    const monthYearElement = document.getElementById('monthYear');
    const calendarDatesElement = document.getElementById('calendarDates');
    const months = [
        "JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE",
        "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"
    ];

    monthYearElement.textContent = `${months[month]} ${year}`;
    calendarDatesElement.innerHTML = "";

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();  // Assuming `today` was not defined earlier

    // Add blank divs for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        const blankDiv = document.createElement('div');
        blankDiv.classList.add('calendar-date');
        calendarDatesElement.appendChild(blankDiv);
    }

    // Loop through each day of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateDiv = document.createElement('div');
        dateDiv.classList.add('calendar-date');
        dateDiv.textContent = day;

        const dateObj = new Date(year, month, day);
        const formattedDate = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

        // Check if the current date is a holiday
        const holiday = holidayDates.find(holiday => holiday.date === formattedDate);
        if (holiday) {
            dateDiv.classList.add('holiday');  // Add 'holiday' class to highlight it
            const holidayName = document.createElement('div');
            holidayName.classList.add('holiday-name');
            holidayName.textContent = holiday.title;  // Display the holiday's name
            dateDiv.appendChild(holidayName);  // Append the holiday name inside the date cell
            dateDiv.classList.add('disabled');  // Disable holiday dates
        }

        // Highlight the appointment date
        if (formattedDate === appointmentDate) {
            dateDiv.classList.add('appointment-date');
        }

        // Disable past dates and Sundays
        if (dateObj < today.setHours(0, 0, 0, 0) || dateObj.getDay() === 0 || dateDiv.classList.contains('disabled')) {
            dateDiv.classList.add('disabled');
        } else {
            dateDiv.onclick = () => selectDate(dateDiv, year, month + 1, day);
        }

        // Highlight the selected date if it matches the global selectedDate
        if (selectedDate === formattedDate) {
            dateDiv.classList.add('selected');
            selectedDateElement = dateDiv;
            loadSlots(selectedDate); // Load the slots for the selected date
        }

        calendarDatesElement.appendChild(dateDiv);
    }
}


window.onload = function() {
    fetchHolidays();  // Fetch holidays as soon as the page loads
    generateCalendar(currentMonth, currentYear);
    
    // Retrieve the original appointment date
    const appointmentIdInput = document.getElementById("appointmentId");
    if (appointmentIdInput) {
        const appointmentId = appointmentIdInput.value;
        fetchAppointmentDate(appointmentId);
    }
};


function fetchAppointmentDate(appointmentId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../php/process_reschedule.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            console.log('Server response:', xhr.responseText);
            const response = JSON.parse(xhr.responseText);
            if (response.appointment_date) {
                // Use the appointment date passed from the PHP code
                appointmentDate = response.appointment_date;
                generateCalendar(currentMonth, currentYear);
            }
        } else {
            console.error('Error loading slots:', xhr.statusText);
        }
    }
};

function selectDate(dateDiv, year, month, day) {
    if (selectedDateElement) {
        selectedDateElement.classList.remove('selected');
    }
    dateDiv.classList.add('selected');
    selectedDateElement = dateDiv;

    selectedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    document.getElementById("appointmentDate").value = selectedDate;

    // Create a new Date object
    const dateObj = new Date(year, month - 1, day); // month is 0-indexed

    // Format the date to "Day, Month DD, YYYY"
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = dateObj.toLocaleDateString('en-US', options);

    document.getElementById("selectedDate").textContent = formattedDate;

    loadSlots(selectedDate);
}


function loadSlots(date) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../php/addappointment.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            const slots = JSON.parse(xhr.responseText);
            displaySlots(slots);

            // Update the appointment_id after fetching the slots, in case it's generated by the PHP
            if (slots.appointment_id) {
                document.getElementById("appointment_id").value = slots.appointment_id;
            }
        } else {
            console.error('Error loading slots:', xhr.statusText);
        }
    };
    xhr.send('action=fetch_slots&date=' + date);
}

let selectedSlotElement = null;

function selectSlot(slot, slotElement) {
    if (selectedSlotElement) {
        selectedSlotElement.classList.remove('selected');
    }
    slotElement.classList.add('selected');
    selectedSlotElement = slotElement;

    document.getElementById("selectedTimeSlot").value = slot;
}

function displaySlots(response) {
    // Check if 'slots' exists and is an array
    if (Array.isArray(response.slots)) {
        const slotsContainer = document.getElementById('slotsContainer');
        slotsContainer.innerHTML = ''; // Clear any previous slots

        // Loop through the slots array and create buttons
        response.slots.forEach(slot => {
            const button = document.createElement('button');
            const timeText = slot.time || 'Undefined';

            button.textContent = `${timeText}`;
            button.innerHTML += `<span class="availability"> (${slot.available} available)</span>`;
            button.onclick = () => selectSlot(slot.time, button);
            button.disabled = slot.available === 0;
            button.type = 'button';  // Prevent form submission on click
            slotsContainer.appendChild(button);
        });
    } else {
        console.error('Expected an array of slots, but got:', response);
    }
}




function prevMonth(event) {
    event.preventDefault(); // Prevent any form submission
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
}

function nextMonth(event) {
    event.preventDefault(); // Prevent any form submission
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentMonth, currentYear);
}

generateCalendar(currentMonth, currentYear);
function handleFormSubmission(event) {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }

    let selectedDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;
    const appointmentId = document.getElementById("appointmentId").value;

    Swal.fire({
        title: 'Confirm Reschedule',
        html: `
            <div style="text-align: justify;">
                <p><strong>NEW DATE: </strong> ${selectedDate}</p>
                <p><strong>NEW TIME SLOT: </strong> ${selectedSlot}</p>
                <p>Are you sure you want to reschedule this appointment?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reschedule',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(document.getElementById("appointmentForm"));
            formData.append('appointment_id', appointmentId);
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../php/process_reschedule.php", true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: response.status,
                        title: response.title,
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed && response.status === 'success') {
                            // Update the selected date with the new rescheduled date
                            const newDate = new Date(selectedDate);
                            currentMonth = newDate.getMonth();
                            currentYear = newDate.getFullYear();
                            selectedDate = `${newDate.getFullYear()}-${(newDate.getMonth() + 1).toString().padStart(2, '0')}-${newDate.getDate().toString().padStart(2, '0')}`;

                            // Regenerate the calendar to reflect the new rescheduled date
                            generateCalendar(currentMonth, currentYear);

                            // Redirect to the view appointments page if needed
                            window.location.href = '../patients/view_appointments.php';
                        }
                    });
                } else {
                    Swal.fire('Error', 'An error occurred while submitting the form.', 'error');
                }
            };
            xhr.send(formData);
        }
    });
}

document.getElementById("appointmentForm").addEventListener("submit", function (event) {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }


    let selectedDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;
    const appointmentId = document.getElementById("appointmentId").value;


    Swal.fire({
        title: 'Confirm Reschedule',
        html: `
            <div style="text-align: justify;">
                <p><strong>NEW DATE: </strong> ${selectedDate}</p>
                <p><strong>NEW TIME SLOT: </strong> ${selectedSlot}</p>
                <p>Are you sure you want to reschedule this appointment?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reschedule',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(document.getElementById("appointmentForm"));
            formData.append('appointment_id', appointmentId);
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../php/process_reschedule.php", true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: response.status,
                        title: response.title,
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed && response.status === 'success') {
                            // Redirect to the view appointments page if needed
                            window.location.href = '../patients/view_appointments.php';
                        }
                    });
                } else {
                    Swal.fire('Error', 'An error occurred while submitting the form.', 'error');
                }
            };
            xhr.send(formData);
        }
    });
});


function validateForm() {
    const selectedDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;

    const errorMessages = [];

    if (selectedDate === "") {
        errorMessages.push("Please select a date.");
    }
    if (selectedSlot === "") {
        errorMessages.push("Please select a time slot.");
    }

    if (errorMessages.length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Please fill in all required fields',
            html: errorMessages.join('<br>'),
            confirmButtonColor: '#3085d6'
        });
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    generateCalendar(currentMonth, currentYear);
});