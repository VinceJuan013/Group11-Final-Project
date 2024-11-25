
document.addEventListener('DOMContentLoaded', function () {
    // Initialize search functionality
    initializeSearch();

    // Initialize entries per page select
    initializeEntriesSelect();

    // Initialize profile modal
    initializeProfileModal();
});

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.appointment-table');

    if (searchInput && table) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                const patientId = row.children[0].textContent.toLowerCase();
                const matches = name.includes(searchTerm) || patientId.includes(searchTerm);
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

function updateQueryStringParameter(uri, key, value) {
    const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    const separator = uri.indexOf('?') !== -1 ? "&" : "?";

    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    } else {
        return uri + separator + key + "=" + value;
    }
}

function initializeProfileModal() {
    // Initialize form validation
    initializeFormValidation();

    // Initialize modal close handlers
    initializeModalClose();

    // Initialize date of birth age sync
    initializeDateAgeSync();
}

function initializeFormValidation() {
    const form = document.getElementById('updateProfileForm');
    if (!form) return;

    form.addEventListener('submit', handleFormSubmit);
}

function initializeModalClose() {
    const closeButton = document.querySelector('.close');
    const modal = document.getElementById('profileModal');

    if (closeButton && modal) {
        closeButton.onclick = () => closeModal(modal);

        window.onclick = (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        };
    }
}

function initializeDateAgeSync() {
    const dobInput = document.getElementById('date_of_birth');
    const ageSelect = document.getElementById('age');

    if (dobInput && ageSelect) {
        dobInput.addEventListener('change', function () {
            const age = calculateAge(this.value);
            if (age >= 0 && age <= 100) {
                ageSelect.value = age;
            }
        });
    }
}

function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
}

async function openProfileModal(patient_id) {
    const modal = document.getElementById('profileModal');
    const loadingSpinner = createLoadingSpinner();

    try {
        modal.style.display = 'flex';
        document.getElementById('patient_id').value = patient_id;

        // Show loading spinner
        modal.querySelector('.modal-content').appendChild(loadingSpinner);

        const response = await fetch(`get_patient.php?patient_id=${patient_id}`);
        if (!response.ok) {
            throw new Error('Failed to fetch patient data');
        }

        const data = await response.json();
        if (data.error) {
            throw new Error(data.error);
        }

        updateFormFields(data);
    } catch (error) {
        console.error('Error:', error);
        closeModal(modal);
        Swal.fire('Error', error.message || 'Failed to load patient data', 'error');
    } finally {
        loadingSpinner.remove();
    }
}

function updateFormFields(data) {
    const fields = [
        'fu ll_name', 'first_name', 'last_name', 'middle_initial', 'gender',
        'date_of_birth', 'age', 'phone_number', 'present_address',
        'email', 'occupation'
    ];

    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            element.value = data[field] || '';
        }
    });
}

async function handleFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');

    if (!validateForm(form)) {
        return;
    }

    try {
        // Add confirmation dialog before proceeding
        const confirmResult = await Swal.fire({
            title: 'Confirm Update',
            text: 'Are you sure you want to update this profile?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6', // Blue color
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel'
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span> Updating...';

        const formData = new FormData(form);
        const response = await fetch('../php/patient_update.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Updated success message with green styling
            await Swal.fire({
                title: 'Success',
                text: 'Profile updated successfully',
                icon: 'success',
                confirmButtonColor: '#28a745', // Green color
                allowOutsideClick: false
            });
            location.reload();
        } else {
            throw new Error(data.error || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: error.message || 'An error occurred while updating the profile',
            icon: 'error',
            confirmButtonColor: '#d33' // Red color for errors
        });
    } finally {
        // Re-enable submit button and restore original text
        submitButton.disabled = false;
        submitButton.innerHTML = 'Submit';
    }
}

function validateForm(form) {
    const requiredFields = ['first_name', 'last_name', 'date_of_birth', 'gender'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = form.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            Swal.fire('Error', `${field.replace('_', ' ').toUpperCase()} is required`, 'error');
            isValid = false;
        }
    });

    const email = form.querySelector('[name="email"]');
    if (email && email.value && !isValidEmail(email.value)) {
        Swal.fire('Error', 'Please enter a valid email address', 'error');
        isValid = false;
    }

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function createLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = `
        <style>
            .loading-spinner {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        </style>
    `;
    return spinner;
}

function closeModal(modal) {
    modal.style.display = 'none';
    // Reset form
    const form = document.getElementById('updateProfileForm');
    if (form) {
        form.reset();
    }
}

// Make functions available globally if needed
window.openProfileModal = openProfileModal;



       
       // Default to opening the first tab
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("patient-info").style.display = "block";
        });
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.querySelector('.appointment-table');
            if (table) {
                table.querySelectorAll('tr').forEach(row => {
                    row.addEventListener('click', function () {
                        this.classList.toggle('selected');
                    });
                });
            }
        });


        function openLightbox(index) {
            currentImageIndex = index;
            updateLightboxImage();
            document.getElementById('lightbox').style.display = 'flex';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }

        function navigateImage(direction) {
            currentImageIndex = (currentImageIndex + direction + images.length) % images.length;
            updateLightboxImage();
        }

        function updateLightboxImage() {
            const lightboxImg = document.getElementById('lightboxImage');
            lightboxImg.src = images[currentImageIndex];
        }

        // Add keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (document.getElementById('lightbox').style.display === 'flex') {
                if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                } else if (e.key === 'Escape') {
                    closeLightbox();
                }
            }
        });

        // Prevent lightbox from closing when clicking on the image
        document.getElementById('lightboxImage').addEventListener('click', function (e) {
            e.stopPropagation();
        });

        // Close lightbox when clicking outside the image
        document.getElementById('lightbox').addEventListener('click', function (e) {
            if (e.target === this) {
                closeLightbox();
            }
        });


        function openTab(tabName) {
            // Hide all elements with the class "tab-content"
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tabContent => {
                tabContent.style.display = 'none';
            });
        
            // Show the selected tab
            const activeTab = document.getElementById(tabName);
            if (activeTab) {
                activeTab.style.display = 'block';
            }
        }
        