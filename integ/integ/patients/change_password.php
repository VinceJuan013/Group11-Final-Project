<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/css/change_password.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>


<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="container">
        <!-- Include sidebar -->
        <?php include 'sidebar.html'; ?>

        <!-- Main content with rounded corners -->
        <div class="main-content">
            <!-- Displaying message using SweetAlert if session message exists -->
            <?php
            session_start();
            if (isset($_SESSION['message'])) {
                // Set default icon to 'warning' in case no type is set
                $icon = 'warning'; 

                // Check for message type (success, error, etc.)
                if (isset($_SESSION['message_type'])) {
                    $icon = $_SESSION['message_type']; // Set the icon based on message type
                }

                echo "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: '" . addslashes($icon) . "', // Dynamically set the icon
                            title: '" . addslashes($_SESSION['message']) . "',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    });
                </script>";

                // Clear the session message and type after displaying
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <h1>CHANGE PASSWORD</h1>
            <hr class="thin-line">

            <div class="form-container">
                <form class="forgot-password-form" action="../php/changepassword.php" method="POST">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">Current Password</span>
                            <input class="input" type="password" name="currentPassword" placeholder="Enter current password" required>
                        </div>
                        <div class="input-box">
                            <span class="details">New Password</span>
                            <input class="input" type="password" name="newPassword" placeholder="Enter new password" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Confirm Password</span>
                            <input class="input" type="password" name="confirmPassword" placeholder="Confirm your new password" required>
                        </div>
                        <button type="submit" class="change-btn">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../script/sidebar.js"></script>
</body>

</html>
