<?php
// Check if a session is already active before starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arciaga-Juntilla Ortho Dental Clinic - Login</title>
    <link rel="stylesheet" href="/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&family=Poppins:wght@400;600&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="brand-section">
            <img src="logo.png" alt="Arciaga-Juntilla Ortho Dental Clinic Logo" class="logo">
            <h1 class="clinic-name">ARCIAGA - JUNTILLA TMJ - ORTHO DENTAL CLINIC</h1>
            <p class="clinic-tagline">OUR PASSION IS YOUR SMILE.</p>
            <div class="brand-decoration"></div>
        </div>
        <div class="login-container">
            <h2 class="title">Welcome!</h2>
            <p class="subtitle">Please login to access your account.</p>

            <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
                <p class="error-message show"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form class="login-form" action="login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="input-group">
                    <i class="fa fa-user icon"></i>
                    <input required type="text" name="username" id="username" autocomplete="off" class="input">
                    <label class="user-label" for="username">USERNAME</label>
                </div>
                <div class="input-group">
                    <i class="fa fa-lock icon"></i>
                    <input required type="password" name="password" id="password" autocomplete="off" class="input">
                    <label class="user-label" for="password">PASSWORD</label>
                    <i class="fa fa-eye-slash toggle-password" id="togglePassword"></i>
                </div>
                <button type="submit" class="login-btn">LOGIN</button>
                <div class="additional-options">
                    <a href="../forgotpass/forgotpass.html" class="forgot-password">Forgot Password?</a>
                    <a href="signup_form.php" class="create-account">Create Account</a>
                </div>
                <hr class="line">
                <p class="reserved">© 2024 Arciaga-Juntilla Ortho Dental Clinic. All rights reserved.</p>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>