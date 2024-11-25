<?php
// Start the session
session_start();

// Check if success or error messages are present
$registration_success = isset($_SESSION['registration_success']) && $_SESSION['registration_success'] === true;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear the error message from the session
unset($_SESSION['error_message']);

// Initialize variables with form data if available
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$keys = array('last_name', 'first_name', 'middle_initial', 'gender', 'date_of_birth', 'age', 'phone_number', 'email', 'occupation', 'present_address', 'username');
foreach ($keys as $key) {
    $$key = isset($form_data[$key]) ? htmlspecialchars($form_data[$key]) : '';
}

// Clear the stored form data
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="/css/signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>
    <div class="container">
        <div class="signup-container">
            <h2 class="title">Create Account</h2>

            <?php if ($registration_success): ?>
                <script>
                    Swal.fire({
                        title: "Registration Successful!",
                        text: "You can now log in to your account.",
                        icon: "success",
                        confirmButtonText: "Okay"
                    }).then(() => {
                        window.location.href = "login_form.php";
                    });
                </script>
                <?php unset($_SESSION['registration_success']); ?>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form id="registrationForm" class="signup-form" action="signup.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="input-row">
                    <div class="input-group">
                        <label class="user-label" for="last_name">LASTNAME</label>
                        <input required type="text" name="last_name" id="last_name" value="<?php echo $last_name; ?>"
                            autocomplete="off">
                    </div>
                    <div class="input-group">
                        <label class="user-label" for="first_name">FIRST NAME</label>
                        <input required type="text" name="first_name" id="first_name" value="<?php echo $first_name; ?>"
                            autocomplete="off">
                    </div>
                    <div class="input-group">
                        <label class="user-label" for="middle_initial">M.I</label>
                        <input required type="text" name="middle_initial" id="middle_initial" maxlength="1"
                            value="<?php echo $middle_initial; ?>" autocomplete="off">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label class="user-label" for="gender">GENDER</label>
                        <select name="gender" id="gender" required>
                            <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select</option>
                            <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label class="user-label" for="date_of_birth">DATE OF BIRTH</label>
                        <input required type="date" name="date_of_birth" id="date_of_birth"
                            value="<?php echo $date_of_birth; ?>" autocomplete="off" placeholder="SELECT DATE">
                    </div>

                    <div class="input-group">
                        <label class="user-label" for="age">AGE</label>
                        <input type="number" name="age" id="age" min="5" max="90" value="<?php echo $age; ?>" required
                            readonly>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label class="user-label" for="phone_number">PHONE NUMBER</label>
                        <div class="phone-input-container">
                            <span class="phone-prefix">+63</span>
                            <input required type="tel" name="phone_number" id="phone_number"
                                value="<?php echo ltrim($phone_number, '+63'); ?>" pattern="\d{10}" maxlength="10"
                                autocomplete="off" placeholder="9123456789">
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="user-label" for="email">EMAIL ADDRESS</label>
                        <input required type="email" name="email" id="email" value="<?php echo $email; ?>"
                            autocomplete="off">
                    </div>
                </div>


                <div class="input-group">
                    <label class="user-label" for="occupation">OCCUPATION</label>
                    <input required type="text" name="occupation" id="occupation" value="<?php echo $occupation; ?>"
                        autocomplete="off">
                </div>

                <div class="input-group">
                    <label class="user-label" for="present_address">PRESENT ADDRESS</label>
                    <input required type="text" name="present_address" id="present_address"
                        value="<?php echo $present_address; ?>" autocomplete="off">
                </div>

                <hr class="line">

                <div class="input-group">
                    <label class="user-label" for="username">USERNAME</label>
                    <input required type="text" name="username" id="username" value="<?php echo $username; ?>"
                        autocomplete="off">
                </div>

                <div class="input-group">
                    <label class="user-label" for="password">PASSWORD</label>
                    <input required type="password" name="password" id="password" autocomplete="off">
                </div>

                <div class="input-group">
                    <label class="user-label" for="confirmpassword">CONFIRM PASSWORD</label>
                    <input required type="password" name="confirmpassword" id="confirmpassword" autocomplete="off">
                </div>
                <ul id="password-requirements" style="display: none;">
                    <li id="length" class="invalid">At least 8 characters.</li>
                    <li id="uppercase" class="invalid">At least one uppercase letter.</li>
                    <li id="lowercase" class="invalid">At least one lowercase letter.</li>
                    <li id="number" class="invalid">At least one number.</li>
                </ul>
                <button type="submit" class="signup-btn">SIGN UP</button>

                <div class="have-account">
                    <label>Already have an Account? <a href="login_form.php">Login</a></label>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr("#date_of_birth", {
                dateFormat: "Y-m-d",
                maxDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    const dob = dateStr;
                    if (dob) {
                        const age = calculateAge(dob);
                        document.getElementById('age').value = age;
                    }
                }
            });

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

            const password = document.getElementById("password");
            const passwordRequirements = document.getElementById("password-requirements");

            // Show password requirements when the password field is focused
            password.addEventListener("focus", () => {
                passwordRequirements.style.display = "block";
            });

            // Hide password requirements when clicking away from the password field
            password.addEventListener("blur", () => {
                passwordRequirements.style.display = "none";
            });

            // Real-time password strength feedback
            const length = document.getElementById("length");
            const uppercase = document.getElementById("uppercase");
            const lowercase = document.getElementById("lowercase");
            const number = document.getElementById("number");

            password.addEventListener("input", () => {
                const value = password.value;

                length.classList.toggle("valid", value.length >= 8);
                length.classList.toggle("invalid", value.length < 8);

                uppercase.classList.toggle("valid", /[A-Z]/.test(value));
                uppercase.classList.toggle("invalid", !/[A-Z]/.test(value));

                lowercase.classList.toggle("valid", /[a-z]/.test(value));
                lowercase.classList.toggle("invalid", !/[a-z]/.test(value));

                number.classList.toggle("valid", /\d/.test(value));
                number.classList.toggle("invalid", !/\d/.test(value));

                special.classList.toggle("valid", /[\W_]/.test(value));
                special.classList.toggle("invalid", !/[\W_]/.test(value));
            });
        });
    </script>



</body>

</html>