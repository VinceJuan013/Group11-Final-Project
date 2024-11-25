class AppointmentCalendar {
    constructor() {
        this.calendar = null;
        this.initialize();
    }

    initialize() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        } else {
            this.onDOMContentLoaded();
        }
    }

    onDOMContentLoaded() {
        this.initializeCalendar();
        this.setupEventListeners();
    }

    initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error('Calendar element not found');
            return;
        }

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,refreshButton'
            },
            customButtons: {
                refreshButton: {
                    text: '↻ Refresh',
                    click: () => this.refreshCalendar()
                }
            },
            events: (info, successCallback, failureCallback) =>
                this.fetchEvents(info, successCallback, failureCallback),
            eventClick: (info) => this.openAppointmentModal(info.event),
            eventDidMount: (info) => {
                const event = info.event;
                let tooltip = '';

                if (event.extendedProps.isHoliday) {
                    tooltip = `Holiday: ${event.title}`;
                } else {
                    tooltip = `Patient: ${event.extendedProps.username || 'N/A'}
                        Service: ${event.extendedProps.selected_services || 'N/A'}
                        Status: ${event.extendedProps.status || 'N/A'}`;
                }

                info.el.setAttribute('title', tooltip);

                // Add pointer cursor to make it clear that the event is clickable
                info.el.style.cursor = 'pointer';
            },
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6],
                startTime: '09:00',
                endTime: '18:00',
            },
            eventContent: (arg) => {
                return { html: `<span>${arg.event.title}</span>` };
            }
        });

        this.calendar.render();
    }

    fetchEvents(fetchInfo, successCallback, failureCallback) {
        this.showLoading();

        // Fetch Google Calendar holiday events
        $.ajax({
            url: `https://www.googleapis.com/calendar/v3/calendars/en-gb.philippines%23holiday%40group.v.calendar.google.com/events`,
            data: {
                key: 'AIzaSyDRNKH3lo-FaELDunaz3azWMXgJ31aY48g',
                timeMin: fetchInfo.start.toISOString(),
                timeMax: fetchInfo.end.toISOString(),
                singleEvents: true,
                orderBy: 'startTime'
            },
            success: (response) => {
                const holidayEvents = this.processHolidayEvents(response);

                // Fetch appointment events
                $.ajax({
                    url: '../staff/api/api_appointments.php',
                    type: 'GET',
                    success: (appointmentResponse) => {
                        const appointmentEvents = this.processEvents(appointmentResponse);
                        const allEvents = [...holidayEvents, ...appointmentEvents];
                        successCallback(allEvents);
                        this.hideLoading();
                    },
                    error: (error) => {
                        console.error('Error fetching appointments:', error);
                        // Still show holidays even if appointments fail
                        successCallback(holidayEvents);
                        this.hideLoading();
                        this.showError('Failed to load appointments');
                    }
                });
            },
            error: (error) => {
                console.error('Error fetching holiday events:', error);
                // If holiday fetch fails, try to get appointments only
                $.ajax({
                    url: '../staff/api/api_appointments.php',
                    type: 'GET',
                    success: (appointmentResponse) => {
                        const appointmentEvents = this.processEvents(appointmentResponse);
                        successCallback(appointmentEvents);
                        this.hideLoading();
                    },
                    error: (appointmentError) => {
                        console.error('Error fetching appointments:', appointmentError);
                        failureCallback(appointmentError);
                        this.hideLoading();
                        this.showError('Failed to load calendar data');
                    }
                });
            }
        });
    }
    processHolidayEvents(response) {
        if (!response || !response.items) return [];

        return response.items.map(event => ({
            title: `🎉 ${event.summary}`, // Holiday name with a gentle emoji
            start: event.start.date || event.start.dateTime,
            end: event.end.date || event.end.dateTime,
            allDay: !event.start.dateTime,
            display: 'block',
            backgroundColor: '#F4F9FA',  // Very light blue-gray background for subtlety
            borderColor: '#D1DCE0',  // Soft light grey border
            textColor: '#2B3A3F',  // Dark charcoal color for text
            classNames: ['holiday-event'],
            editable: false,
            overlap: false,
            extendedProps: {
                type: 'holiday',
                description: event.description || '',
                isHoliday: true
            }
        }));
    }




    processEvents(response) {
        if (!Array.isArray(response)) {
            console.error('Invalid appointment response format');
            return [];
        }

        return response.map(event => {
            try {
                const { start: appointment_date, extendedProps } = event;
                const appointment_time = extendedProps?.appointment_time;

                if (!appointment_date || !appointment_time) {
                    console.warn(`Skipping event with missing date or time: ${JSON.stringify(event)}`);
                    return null;
                }

                const startDateTime = this.parseDateTime(appointment_date, appointment_time);
                if (!startDateTime) {
                    console.warn(`Invalid date for event: ${JSON.stringify(event)}`);
                    return null;
                }

                const endDateTime = new Date(startDateTime.getTime() + (60 * 60 * 1000));
                const eventColor = this.getStatusColor(event.extendedProps.status);

                return {
                    id: event.id,
                    title: `APPOINTMENT ID: ${event.id}`,
                    start: startDateTime,
                    end: endDateTime,
                    extendedProps: {
                        ...extendedProps,
                        time: appointment_time
                    },
                    backgroundColor: eventColor,
                    borderColor: eventColor,
                    display: 'block'
                };
            } catch (error) {
                console.error('Error processing event:', error, event);
                return null;
            }
        }).filter(event => event !== null);
    }

    parseDateTime(date, time) {
        try {
            const [timePart, modifier] = time.split(' ');
            let [hours, minutes] = timePart.split(':').map(Number);

            if (modifier === 'PM' && hours !== 12) hours += 12;
            if (modifier === 'AM' && hours === 12) hours = 0;

            const dateTime = new Date(`${date}T${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`);
            return isNaN(dateTime.getTime()) ? null : dateTime;
        } catch (error) {
            console.error('Error parsing date time:', error);
            return null;
        }
    }

    setupEventListeners() {
        // Form submission
        const form = document.getElementById('appointmentForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveChanges();
            });
        }

        // Modal close handlers
        const closeButtons = document.querySelectorAll('.close-modal');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => this.closeModal());
        });

        // Click outside modal to close
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('editAppointmentModal');
            if (event.target === modal) {
                this.closeModal();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });

        // Stop propagation on modal content
        const modalContent = document.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // Window resize handler
        window.addEventListener('resize', () => {
            if (this.calendar) {
                this.calendar.updateSize();
            }
        });
    }

    getStatusColor(status) {
        if (!status) return '#6b7280';

        const colors = {
            pending: '#f59e0b',
            confirmed: '#10b981',
            completed: '#3b82f6',
            cancelled: '#ef4444'
        };
        return colors[status.toLowerCase()] || '#6b7280';
    }

    getStatusBadgeHTML(status) {
        if (!status) return '';

        const colors = {
            pending: 'bg-warning',
            confirmed: 'bg-success',
            completed: 'bg-primary',
            cancelled: 'bg-danger'
        };
        return `<span class="status-badge ${colors[status.toLowerCase()] || 'bg-secondary'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }

    openAppointmentModal(event) {
        const modal = document.getElementById('editAppointmentModal');
        const details = document.getElementById('appointmentDetails');
        if (!modal || !details) {
            console.error('Modal or details element not found');
            return;
        }
    
        // Check if this is a holiday event
        if (event.extendedProps.isHoliday) {
            details.innerHTML = `
            <div class="mb-3">
                <h5 class="mb-3">Holiday: ${event.title}</h5>
                <p><strong>Date:</strong> ${this.formatDate(event.start)}</p>
                ${event.extendedProps.description ? `<p><strong>Description:</strong> ${event.extendedProps.description}</p>` : ''}
            </div>
            `;
    
            // Hide the form elements for holidays
            const form = document.getElementById('appointmentForm');
            if (form) form.style.display = 'none';
    
            modal.style.display = 'block';
            requestAnimationFrame(() => {
                modal.classList.add('show');
            });
            return;
        }
    
        // Regular appointment handling
        const appointmentId = document.getElementById('appointmentId');
        const remarks = document.getElementById('remarks');
        const status = document.getElementById('status');
        const form = document.getElementById('appointmentForm');
    
        // Show the form for regular appointments
        if (form) form.style.display = 'block';
    
        // Set the values
        if (appointmentId) appointmentId.value = event.id;
        if (remarks) remarks.value = event.extendedProps.remarks || '';
        
        // Explicitly set the status dropdown value and ensure it exists in the options
        if (status) {
            const currentStatus = event.extendedProps.status?.toLowerCase() || '';
            
            // First ensure the value exists in the dropdown options
            let statusExists = false;
            for (let i = 0; i < status.options.length; i++) {
                if (status.options[i].value.toLowerCase() === currentStatus) {
                    statusExists = true;
                    break;
                }
            }
            
            // If the status exists in options, set it
            if (statusExists) {
                status.value = currentStatus;
            } else {
                console.warn(`Status "${currentStatus}" not found in dropdown options`);
                // Optionally add the status if it doesn't exist
                const newOption = new Option(
                    currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1), 
                    currentStatus
                );
                status.add(newOption);
                status.value = currentStatus;
            }
        }
    
        details.innerHTML = `
        <div class="mb-3">
            <h5 class="mb-3">Appointment #${event.id} ${this.getStatusBadgeHTML(event.extendedProps.status)}</h5>
            <p><strong>Patient:</strong> ${event.extendedProps.username || 'N/A'}</p>
            <p><strong>Services:</strong> ${event.extendedProps.selected_services || 'N/A'}</p>
            <p><strong>Date:</strong> ${this.formatDate(event.start)}</p>
            <p><strong>Time:</strong> ${event.extendedProps.appointment_time || 'N/A'}</p>
            <p><strong>Complaint:</strong> ${event.extendedProps.complaint || 'N/A'}</p>
            <p><strong>Preferred Dentist:</strong> ${event.extendedProps.preferred_dentist || 'ANY'}</p>
        </div>
        `;
    
        modal.style.display = 'block';
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
    }

    closeModal() {
        const modal = document.getElementById('editAppointmentModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    saveChanges() {
        if (!this.validateForm()) {
            return;
        }

        const appointmentId = document.getElementById('appointmentId').value;
        const remarks = document.getElementById('remarks').value.trim();
        const status = document.getElementById('status').value;

        Swal.fire({
            title: 'Confirm Update',
            text: `Are you sure you want to update this appointment to ${status} status?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it',
            cancelButtonText: 'No, cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                this.showLoading();

                $.ajax({
                    url: '/php/update_appointment.php',
                    type: 'POST',
                    data: {
                        appointment_id: appointmentId,
                        remarks: remarks,
                        status: status
                    },
                    success: (response) => {
                        this.hideLoading();
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                this.showSuccess('Appointment updated successfully');
                                this.closeModal();
                                if (this.calendar) {
                                    this.calendar.refetchEvents();
                                }
                            } else {
                                this.showError(result.error || 'Failed to update appointment');
                            }
                        } catch (e) {
                            this.showError('Invalid server response');
                        }
                    },
                    error: (xhr) => {
                        this.hideLoading();
                        this.showError('Failed to update appointment. Please try again.');
                    }
                });
            }
        });
    }

    validateForm() {
        const status = document.getElementById('status');
        if (!status || !status.value) {
            this.showError('Please select a status');
            return false;
        }
        return true;
    }

    formatDate(date) {
        try {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (error) {
            console.error('Error formatting date:', error);
            return 'Invalid Date';
        }
    }

    showLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.classList.add('show');
        }
    }

    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInRight'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutRight'
            }
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInRight'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutRight'
            }
        });
    }

    refreshCalendar() {
        this.showLoading();
        if (this.calendar) {
            this.calendar.refetchEvents();
        }
        this.hideLoading();
        this.showSuccess('Calendar refreshed');
    }
}

// Initialize the calendar
document.addEventListener('DOMContentLoaded', () => {
    try {
        const appointmentCalendar = new AppointmentCalendar();
    } catch (error) {
        console.error('Error initializing calendar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Calendar Initialization Error',
            text: 'Failed to initialize the calendar. Please refresh the page.',
            confirmButtonText: 'Refresh Page',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    }
});