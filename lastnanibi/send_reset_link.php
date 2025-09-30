<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

header('Content-Type: application/json');
include 'db_connection.php';

$email = $_POST['email'] ?? '';
$action = $_POST['action'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'A valid email and action are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT student_id, first_name, last_name, section, department FROM students WHERE email = ? UNION SELECT teacher_id, first_name, last_name, '' as section, '' as department FROM teachers WHERE email = ?");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account was found with that email address.']);
    exit;
}

$mail = new PHPMailer(true);
try {
    // --- Server Settings (CONFIGURE FOR YOUR EMAIL) ---
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; // Your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com'; // Your email
    $mail->Password = 'your_email_password'; // Your password or app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('no-reply@samspe.com', 'SAMS-PE System');
    $mail->addAddress($email, $user['first_name'] . ' ' . $user['last_name']);
    $mail->isHTML(true);

    if ($action === 'send_password') {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        $mail->Subject = 'SAMS-PE Password Reset Request';
        $mail->Body = "Hello {$user['first_name']},<br><br>Click the link below to reset your password. This link is valid for one hour.<br><br><a href='{$reset_link}'>Reset My Password</a>";
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'A password reset link has been sent.']);

    } elseif ($action === 'send_qr' && !empty($user['section'])) {
        $qr_data = json_encode(['student_id' => $user['student_id'], 'name' => "{$user['first_name']} {$user['last_name']}", 'section' => $user['section'], 'department' => $user['department']]);
        $mail->Subject = 'Your SAMS-PE Attendance QR Code';
        $mail->Body = "Hello {$user['first_name']},<br><br>Your attendance QR code is attached to this email.";
        $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qr_data);
        $mail->addStringAttachment(file_get_contents($qr_image_url), 'SAMS-PE_QR_Code.png');
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Your QR code has been sent.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action for this user type.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Email could not be sent. Please contact an administrator."]);
}
?>