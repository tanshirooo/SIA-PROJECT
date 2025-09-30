<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Welcome Back!</h1>
            <p class="auth-subtitle">Log in to access your SAMS-PE dashboard.</p>
            <form id="login-form">
                <div class="input-group">
                    <label for="username">Student or Teacher ID</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="auth-btn btn-primary">Login</button>
            </form>
            <div class="auth-footer">
                <a href="forgot_password.php">Forgot Password?</a><br>
                Don't have an account? <a href="register.php">Register here</a>.
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('.auth-btn');
            btn.textContent = 'Logging in...';
            btn.disabled = true;

            try {
                const response = await fetch('account.php?action=login', {
                    method: 'POST',
                    body: new FormData(this)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    throw new Error(data.message || 'An unknown error occurred.');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: error.message
                });
            } finally {
                btn.textContent = 'Login';
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>