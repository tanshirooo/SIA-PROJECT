<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';
$student_id = $_SESSION['username'];
$stmt = $conn->prepare("SELECT student_id, first_name, last_name, section, department FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
if (!$student) {
    die("Error: Could not find student data.");
}
$qr_data = json_encode([
    'student_id' => $student['student_id'],
    'name' => "{$student['first_name']} {$student['last_name']}",
    'section' => $student['section']
]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My QR Code - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
</head>

<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                <div class="header-text">
                    <h1>My Attendance QR Code</h1>
                    <p>Your personal code for attendance marking.</p>
                </div>
            </header>

            <div class="card" style="max-width: 450px; margin: 2rem auto; text-align: center; padding: 2.5rem 2rem;">
                <div id="qrcode-container"
                    style="display: inline-block; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-lg);">
                </div>
                <div
                    style="margin-top: 1.5rem; text-align: left; width: 100%; border-top: 1px solid var(--border-color); padding-top: 1.5rem; line-height: 1.8;">
                    <p><strong>Name:</strong>
                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                    <p><strong>Section:</strong> <?= htmlspecialchars($student['section']) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($student['department']) ?></p>
                </div>
                <button id="download-btn" class="btn btn-primary" style="margin-top: 1.5rem;">Download QR Code</button>
                <div
                    style="margin-top: 2rem; padding: 1rem; background-color: var(--primary-light); border-radius: var(--radius-md); text-align: left; color: #592949; border-left: 4px solid var(--primary);">
                    <h4 style="margin-bottom: 0.5rem; font-weight: 700;">Important Instructions</h4>
                    <p style="font-size: 0.9rem; line-height: 1.6;">Present this QR code to the scanner to record your
                        attendance. Please keep a digital or printed copy of this code for your classes.</p>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>
    <script>
        new QRCode(document.getElementById('qrcode-container'), { text: '<?= addslashes($qr_data) ?>', width: 250, height: 250, correctLevel: QRCode.CorrectLevel.H });
        document.getElementById('download-btn').addEventListener('click', function () {
            const canvas = document.querySelector('#qrcode-container canvas');
            if (canvas) {
                const a = document.createElement('a');
                a.href = canvas.toDataURL("image/png");
                a.download = "SAMS-PE_QR_<?= htmlspecialchars($student['student_id']) ?>.png";
                a.click();
            }
        });
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });
    </script>
</body>

</html>