<?php
header('Content-Type: application/json');
include 'db_connection.php';

$idNumber = $_POST['idNumber'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($idNumber) && empty($email)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM students WHERE student_id = ? OR email = ?");
$stmt->bind_param("ss", $idNumber, $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode(['exists' => $row['cnt'] > 0]);
?>