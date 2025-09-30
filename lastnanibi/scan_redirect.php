<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php?redirect=scanner');
    exit;
}
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: scanner.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Access Denied - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body style="background-color: var(--background);">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: 'Only teachers and admins can access the attendance scanner.',
            confirmButtonText: 'Go to My Dashboard',
            allowOutsideClick: false,
            // This ensures the button in the alert uses our new design
            customClass: {
                confirmButton: 'auth-btn btn-primary'
            }
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
    </script>
</body>

</html>