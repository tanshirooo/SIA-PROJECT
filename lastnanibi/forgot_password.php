<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Account Recovery - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Account Recovery</h1>
            <p class="auth-subtitle">Enter your registered email to receive a password reset link.</p>
            <form id="recovery-form">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <button type="submit" class="auth-btn btn-primary">Send Password Reset Link</button>
            </form>
            <div class="auth-footer"><a href="login.php">Back to Login</a></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('recovery-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('.auth-btn');
            btn.textContent = 'Sending...'; btn.disabled = true;
            try {
                const res = await fetch('send_reset_link.php', { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Check Your Email!', text: data.message });
                } else { throw new Error(data.message); }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Request Failed', text: error.message });
            } finally {
                btn.textContent = 'Send Password Reset Link'; btn.disabled = false;
            }
        });
    </script>
</body>

</html>