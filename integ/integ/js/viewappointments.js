function showTab(tab) {
    const tabs = document.querySelectorAll('.tab-pane');
    const currentTab = document.querySelector('.tab.active');

    currentTab.classList.remove('active');
    document.querySelector('.tab[data-tab="' + tab + '"]').classList.add('active');

    tabs.forEach(pane => pane.style.display = 'none');
    document.getElementById(tab).style.display = 'block';

    // Update URL without refreshing page
    const newUrl = window.location.origin + window.location.pathname + '?tab=' + tab;
    history.pushState(null, '', newUrl);
}
function confirmCancel(appointmentID, canCancel, status, appointmentDate) {
    if (!canCancel) {
        Swal.fire({
            title: 'Cannot Cancel',
            text: "This appointment cannot be cancelled. It may be too close to the appointment time or already confirmed.",
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    Swal.fire({
        title: 'What would you like to do?',
        text: "You can cancel or reschedule the appointment.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Cancel Appointment',
        cancelButtonText: 'Reschedule',
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Ask for cancellation reason
            Swal.fire({
                title: 'Are you sure?',
                text: "You can optionally provide a reason for cancellation.",
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Enter reason (optional)',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it',
                preConfirm: (reason) => {
                    return reason || null; // Return null if no reason provided
                }
            }).then((cancelResult) => {
                if (cancelResult.isConfirmed) {
                    const reason = cancelResult.value ? encodeURIComponent(cancelResult.value) : '';
                    // Redirect to cancellation page with reason
                    window.location.href = 'cancel_appointment.php?id=' + appointmentID + '&reason=' + reason;
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Redirect to rescheduling page
            window.location.href = 'reschedule_appointment.php?id=' + appointmentID;
        }
    });
}

// Add this function to handle the disabled button tooltip
document.addEventListener('DOMContentLoaded', function () {
    const disabledButtons = document.querySelectorAll('.cancel-btn[disabled]');
    disabledButtons.forEach(button => {
        button.addEventListener('mouseover', function (e) {
            Swal.fire({
                title: 'Cannot Cancel',
                text: 'This appointment cannot be cancelled. It may be too close to the appointment time or already confirmed.',
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
});
