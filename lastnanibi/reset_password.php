<?php
include 'db_connection.php';
$token = $_GET['token'] ?? '';
$error_message = '';
$success_message = '';
$is_token_valid = false;

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $is_token_valid = true;
        $email = $result->fetch_assoc()['email'];
    } else {
        $error_message = "This link is invalid or has expired.";
    }
    $stmt->close();
} else {
    $error_message = "No reset token provided.";
}

if ($is_token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $error_message = "Passwords do not match.";
    } else {
        // Find username associated with the email
        $stmt_user = $conn->prepare("SELECT student_id as username FROM students WHERE email = ? UNION SELECT teacher_id as username FROM teachers WHERE email = ?");
        $stmt_user->bind_param("ss", $email, $email);
        $stmt_user->execute();
        $user = $stmt_user->get_result()->fetch_assoc();
        $stmt_user->close();

        if ($user) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE accounts SET password = ? WHERE username = ?");
            $stmt_update->bind_param("ss", $hashed_password, $user['username']);
            $stmt_update->execute();
            $stmt_update->close();

            // Invalidate the token
            $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_delete->bind_param("s", $email);
            $stmt_delete->execute();
            $stmt_delete->close();

            $success_message = "Your password has been reset successfully!";
        } else {
            $error_message = "Could not find an account linked to this email.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Reset Your Password</h1>
            <?php if ($success_message): ?>
                <p style="text-align: center; color: var(--success); margin-bottom: 1.5rem;"><?= $success_message ?></p>
                <a href="login.php" class="auth-btn btn-primary">Proceed to Login</a>
            <?php elseif ($error_message && !$is_token_valid): ?>
                <p style="text-align: center; color: var(--error); margin-bottom: 1.5rem;"><?= $error_message ?></p>
                <a href="forgot_password.php" class="auth-btn btn-secondary">Request a New Link</a>
            <?php elseif ($is_token_valid): ?>
                <p class="auth-subtitle">Enter and confirm your new password.</p>
                <?php if ($error_message): ?>
                    <p style="text-align: center; color: var(--error); margin-bottom: 1rem;"><?= $error_message ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="input-group"><label>New Password</label><input type="password" name="password"
                            class="form-input" required></div>
                    <div class="input-group"><label>Confirm New Password</label><input type="password"
                            name="password_confirm" class="form-input" required></div>
                    <button type="submit" class="auth-btn btn-primary">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>