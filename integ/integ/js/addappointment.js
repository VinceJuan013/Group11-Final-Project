let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = "";
let selectedDateElement = null;
let selectedSlotElement = null;


let holidayDates = [];  // This will store the holidays.

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
                        date: eventDate.toLocaleDateString('en-CA'),  // Store date in 'YYYY-MM-DD' format
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
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let nextAvailableDate = null;

    // Add blank divs for the days before the start of the month
    for (let i = 0; i < firstDay; i++) {
        const blankDiv = document.createElement('div');
        blankDiv.classList.add('calendar-date');
        calendarDatesElement.appendChild(blankDiv);
    }

    // Loop through the days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateDiv = document.createElement('div');
        dateDiv.classList.add('calendar-date');
        dateDiv.textContent = day;

        const dateObj = new Date(year, month, day);
        dateObj.setHours(0, 0, 0, 0);
        const formattedDate = dateObj.toLocaleDateString('en-CA');  // Format date as YYYY-MM-DD

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

        // Disable past dates, Sundays, and holidays
        if (dateObj <= today || dateObj.getDay() === 0 || dateDiv.classList.contains('disabled')) {
            dateDiv.classList.add('disabled');
        } else {
            dateDiv.onclick = () => selectDate(dateDiv, year, month + 1, day);
            if (!nextAvailableDate) {
                nextAvailableDate = dateObj;
                dateDiv.classList.add('next-available');  // Add class for next available date
            }
        }

        calendarDatesElement.appendChild(dateDiv);
    }

    // Pre-select the next available date if one exists
    if (nextAvailableDate) {
        selectDate(null, nextAvailableDate.getFullYear(), nextAvailableDate.getMonth() + 1, nextAvailableDate.getDate());
    }
}



window.onload = function() {
    fetchHolidays();  // Fetch holidays as soon as the page loads
};




function selectDate(dateDiv, year, month, day) {
    if (selectedDateElement) {
        selectedDateElement.classList.remove('selected');
    }
    if (dateDiv) {
        dateDiv.classList.add('selected');
        selectedDateElement = dateDiv;
    }

    selectedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    document.getElementById("appointmentDate").value = selectedDate;

    const dateObj = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = dateObj.toLocaleDateString('en-US', options);

    document.getElementById("selectedDate").textContent = formattedDate;

    loadSlots(selectedDate);
}

function loadSlots(date) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../php/addappointment.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === "error") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#3085d6'
                });
            } else {
                displaySlots(response);
            }
        } else {
            Swal.fire('Error', 'An error occurred while fetching slots.', 'error');
        }
    };
    xhr.send(`action=fetch_slots&date=${date}`);
}

function displaySlots(slots) {
    const slotsContainer = document.getElementById('slotsContainer');
    slotsContainer.innerHTML = '';

    selectedSlotElement = null;
    document.getElementById("selectedTimeSlot").value = '';

    // Handle appointment_id if present
    if (slots.appointment_id) {
        document.getElementById("appointment_id").value = slots.appointment_id;
    }

    // Ensure slots is an array
    let slotsArray = [];
    if (Array.isArray(slots)) {
        slotsArray = slots;
    } else if (slots.slots && Array.isArray(slots.slots)) {
        // If slots is wrapped in an object
        slotsArray = slots.slots;
    } else if (typeof slots === 'object' && !Array.isArray(slots)) {
        // If slots is an object but not an array, convert to array
        slotsArray = Object.values(slots).filter(slot =>
            typeof slot === 'object' && 'time' in slot
        );
    }

    // If we still don't have any valid slots, show an error message
    if (slotsArray.length === 0) {
        const message = document.createElement('p');
        message.textContent = 'No available time slots for this date.';
        message.classList.add('no-slots-message');
        slotsContainer.appendChild(message);
        return;
    }

    // Create buttons for each slot
    slotsArray.forEach(slot => {
        const button = document.createElement('button');
        const timeText = slot.time || 'Undefined';

        button.textContent = `${timeText}`;
        button.innerHTML += `<span class="availability"> ${slot.available} AVAILABLE</span>`;
        button.onclick = () => selectSlot(slot.time, button);
        button.disabled = slot.available === 0;
        button.type = 'button';
        slotsContainer.appendChild(button);
    });
}

// Helper function to validate slot data
function isValidSlot(slot) {
    return (
        typeof slot === 'object' &&
        slot !== null &&
        'time' in slot &&
        'available' in slot &&
        'pending' in slot
    );
}

function selectSlot(time, button) {
    if (selectedSlotElement) {
        selectedSlotElement.classList.remove('selected');
    }
    button.classList.add('selected');
    selectedSlotElement = button;
    document.getElementById("selectedTimeSlot").value = time;
}

function prevMonth() {
    if (currentMonth === 0) {
        currentMonth = 11;
        currentYear--;
    } else {
        currentMonth--;
    }
    generateCalendar(currentMonth, currentYear);
}

function nextMonth() {
    if (currentMonth === 11) {
        currentMonth = 0;
        currentYear++;
    } else {
        currentMonth++;
    }
    generateCalendar(currentMonth, currentYear);
}

document.getElementById("appointmentForm").addEventListener("submit", function (event) {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }

    // Get all selected services with proper formatting
    const servicesHtml = selectedServices.map(service => {
        if (typeof service === 'object' && service.value === 'other') {
            return `Other: ${service.specification}`;
        } else {
            const option = select.querySelector(`option[value="${service}"]`);
            return option ? option.text : service;
        }
    }).join(', ');

    const complain = document.getElementById('complain').value;
    const other_details = document.getElementById('other_details').value;
    const followup = document.querySelector('input[name="followup"]:checked').value;
    const preferred_dentist = document.getElementById("preferred_dentist").value;
    const appointmentDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;

    const [year, month, day] = appointmentDate.split('-');
    const dateObj = new Date(year, parseInt(month) - 1, day);
    const formattedDate = dateObj.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    Swal.fire({
        title: 'Confirm Appointment',
        html:
            `<div style="text-align: justify;">
                <p><strong>SERVICES: </strong> ${servicesHtml}</p>
                <p><strong>COMPLAINT: </strong> ${complain}</p>
                <p><strong>OTHER DETAILS: </strong> ${other_details}</p>
                <p><strong>FOLLOWUP: </strong> ${followup}</p>
                <p><strong>PREFERRED DENTIST: </strong> ${preferred_dentist}</p>
                <p><strong>DATE: </strong> ${formattedDate}</p>
                <p><strong>TIME SLOT: </strong> ${selectedSlot}</p>
                <p>Are you sure you want to confirm this appointment?</p>
            </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(document.getElementById("appointmentForm"));
            formData.append('action', 'book_appointment'); // Add the action parameter
            formData.append('selected_services', JSON.stringify(selectedServices));
            
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../php/addappointment.php", true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === "error") {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#3085d6'
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonColor: '#3085d6'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    if (response.updatedSlots) {
                                        displaySlots(response.updatedSlots);
                                    }
                                    window.location.href = '../patients/view_appointments.php';
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        Swal.fire('Error', 'An error occurred while processing the response.', 'error');
                    }
                } else {
                    Swal.fire('Error', 'An error occurred while submitting the form.', 'error');
                }
            };
            xhr.send(formData);
        }
    });
});


function validateForm() {
    const complain = document.getElementById('complain').value;
    const selectedDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;

    const errorMessages = [];

    // Validate services and "Other" specification
    if (selectedServices.length === 0) {
        errorMessages.push("Please select at least one service.");
    } else {
        // Check if "Other" service has specification
        const otherService = selectedServices.find(service => 
            typeof service === 'object' && service.value === 'other'
        );
        if (otherService && (!otherService.specification || otherService.specification.trim() === '')) {
            errorMessages.push("Please specify the other service.");
        }
    }

    if (complain.trim() === "") {
        errorMessages.push("Please enter a complaint.");
    }
    if (selectedDate === "") {
        errorMessages.push("Please select a date.");
    }
    if (selectedSlot === "") {
        errorMessages.push("Please select a time slot.");
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const selectedDateObj = new Date(selectedDate);
    if (selectedDateObj <= today) {
        errorMessages.push("Please select a future date for the appointment.");
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

const select = document.getElementById('service');
const tagsContainer = document.querySelector('.selected-tags');
const hiddenInput = document.getElementById('selected_services');
let selectedServices = [];

function createTag(value, text, isOther = false) {
    const div = document.createElement('div');
    div.setAttribute('class', 'tag');
    div.setAttribute('data-value', value);
    
    const span = document.createElement('span');
    
    if (isOther) {
        // Create input field for Other
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Please specify';
        input.className = 'other-input';
        input.onchange = function() {
            // Update the stored value when input changes
            const serviceObj = selectedServices.find(s => s.value === 'other');
            if (serviceObj) {
                serviceObj.specification = this.value;
                updateHiddenInput();
            }
        };
        span.appendChild(input);
    } else {
        span.textContent = text;
    }
    
    const closeBtn = document.createElement('span');
    closeBtn.setAttribute('class', 'tag-close');
    closeBtn.innerHTML = '×';
    closeBtn.onclick = function() {
        removeTag(div, value);
    };

    div.appendChild(span);
    div.appendChild(closeBtn);
    return div;
}

function removeTag(tagElement, value) {
    tagsContainer.removeChild(tagElement);
    selectedServices = selectedServices.filter(service => 
        typeof service === 'string' ? service !== value : service.value !== value
    );
    updateHiddenInput();
    
    // Re-enable the option in the select
    const option = select.querySelector(`option[value="${value}"]`);
    if (option) {
        option.disabled = false;
    }
}

function updateHiddenInput() {
    hiddenInput.value = JSON.stringify(selectedServices);
}

select.addEventListener('change', function() {
    const selectedValue = this.value;
    const selectedText = this.options[this.selectedIndex].text;
    
    if (selectedValue && !selectedServices.some(s => 
        typeof s === 'string' ? s === selectedValue : s.value === selectedValue
    )) {
        if (selectedValue === 'other') {
            // Handle 'Other' selection differently
            selectedServices.push({
                value: 'other',
                specification: ''
            });
            const tag = createTag(selectedValue, selectedText, true);
            tagsContainer.appendChild(tag);
        } else {
            // Handle normal services
            selectedServices.push(selectedValue);
            const tag = createTag(selectedValue, selectedText);
            tagsContainer.appendChild(tag);
        }
        
        // Update hidden input
        updateHiddenInput();
        
        // Disable the selected option
        this.options[this.selectedIndex].disabled = true;
        
        // Reset select to default option
        this.value = '';
    }
});
// Form submission handling
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    if (selectedServices.length === 0) {
        e.preventDefault();
        alert('Please select at least one service');
    }
});
generateCalendar(currentMonth, currentYear);