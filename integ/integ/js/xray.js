document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitButton');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const fileInput = document.getElementById('xray_file');
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    const maxSize = 5 * 1024 * 1024; // 5MB

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            // Check file type
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Only JPG, PNG & GIF files are allowed.');
                this.value = '';
                return;
            }

            // Check file size
            if (file.size > maxSize) {
                alert('File is too large. Maximum size is 5MB.');
                this.value = '';
                return;
            }
        }
    });

    form.addEventListener('submit', function (e) {
        // Prevent double submission
        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            return;
        }

        submitBtn.disabled = true;
        loadingIndicator.classList.add('visible');
        form.dataset.submitting = 'true';

        // Re-enable form after 30 seconds (safety timeout)
        setTimeout(function () {
            submitBtn.disabled = false;
            loadingIndicator.classList.remove('visible');
            form.dataset.submitting = 'false';
        }, 30000);
    });
});
function previewImage(event) {
    const imagePreview = document.getElementById('image_preview');
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block'; // Show the image
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.style.display = 'none'; // Hide the image if no file is selected
    }
}