document.addEventListener('DOMContentLoaded', function () {
    initializeSearch();
    initializeEntriesSelect();
    initializeProfileModal();
    initializeAddPatientForm();
});

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.patient-table');

    if (searchInput && table) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                const matches = name.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
            });
        });
    }
}

function initializeEntriesSelect() {
    const entriesSelect = document.getElementById('entriesSelect');
    if (entriesSelect) {
        entriesSelect.addEventListener('change', function () {
            const limit = this.value;
            window.location.href = updateQueryStringParameter(window.location.href, 'limit', limit);
        });
    }
}

function initializeProfileModal() {
    initializeFormValidation();
    initializeDateAgeSync();
}

function initializeDateAgeSync() {
    const dobInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');

    if (dobInput && ageInput) {
        dobInput.addEventListener('change', function () {
            const age = calculateAge(this.value);
            ageInput.value = age;
        });
    }
}

function initializeAddPatientForm() {
    const form = document.getElementById('addPatientForm');
    if (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!validateForm(form)) return;

            try {
                const formData = new FormData(form);
                const response = await fetch('/php/add_patient.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Patient added successfully',
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            popup: 'swal2-no-underline'  // Apply custom class to remove underline
                        }
                    });
                    
                    form.reset();
                    closeModal(document.getElementById('addPatientModal'));
                } else {
                    Swal.fire('Error', data.error || 'Failed to add patient', 'error');
                }
            } catch (error) {
                Swal.fire('Error', error.message || 'An error occurred', 'error');
            }
        });
    }
}

function calculateAge(dob) {
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

// Function for basic form validation
function initializeFormValidation() {
    const form = document.getElementById('addPatientForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            const requiredFields = ['last_name', 'first_name', 'birthdate', 'gender'];
            let valid = true;

            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element || !element.value.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: `${field.replace('_', ' ').toUpperCase()} is required.`,
                    });
                    valid = false;
                }
            });
            if (!valid) event.preventDefault();
        });
    }
}

function validateForm(form) {
    const requiredFields = ['first_name', 'last_name', 'birthdate', 'gender'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = form.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            Swal.fire('Error', `${field.replace('_', ' ').toUpperCase()} is required`, 'error');
            isValid = false;
        }
    });

    return isValid;
}

function updateQueryStringParameter(uri, key, value) {
    const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    const separator = uri.indexOf('?') !== -1 ? "&" : "?";

    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    } else {
        return uri + separator + key + "=" + value;
    }
}

function closeModal(modal) {
    modal.style.display = 'none';
    const form = document.getElementById('addPatientForm');
    if (form) {
        form.reset();
    }
}

function openModal(modal) {
    if (modal) {
        modal.style.display = 'flex';
    }
}
