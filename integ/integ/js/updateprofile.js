document.addEventListener('DOMContentLoaded', function() {
    // Form submission handler
    const updateProfileForm = document.getElementById('updateProfileForm');
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', function(event) {
            event.preventDefault();

            Swal.fire({
                title: 'Do you want to save the changes?',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Save',
                denyButtonText: `Don't save`,
                customClass: {
                    confirmButton: 'swal2-confirm',
                    denyButton: 'swal2-deny',
                    cancelButton: 'swal2-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.status === 'success' ? 'success' : 'error',
                            title: data.status === 'success' ? 'Success!' : 'Error!',
                            text: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });

                        if (data.status === 'success') {
                            // Optional: Reload page or update UI
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An unexpected error occurred. Please try again.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    });
                }
            });
        });
    }

    // Auto-capitalize inputs
    const inputs = document.querySelectorAll('.input, .input1, .inputadd');
    inputs.forEach(input => {
        if (input.type !== 'email') {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });

    // Date of birth and age calculation
    const dateOfBirthInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');

    function calculateAge(birthDate) {
        if (!birthDate) return '';
        const today = new Date();
        const birthDateObj = new Date(birthDate);
        if (isNaN(birthDateObj.getTime())) return '';
        
        let age = today.getFullYear() - birthDateObj.getFullYear();
        const monthDiff = today.getMonth() - birthDateObj.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDateObj.getDate())) {
            age--;
        }
        
        return age;
    }

    if (dateOfBirthInput) {
        flatpickr(dateOfBirthInput, {
            dateFormat: "Y-m-d",
            maxDate: "today",
            allowInput: true,
            onChange: function(selectedDates, dateStr) {
                if (dateStr && ageInput) {
                    const age = calculateAge(dateStr);
                    ageInput.value = age;
                }
            }
        });

        // Initialize age if date of birth is already set
        if (dateOfBirthInput.value && ageInput) {
            const age = calculateAge(dateOfBirthInput.value);
            ageInput.value = age;
        }
    }
});