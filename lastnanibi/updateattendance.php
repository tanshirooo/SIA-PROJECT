<?php
header('Content-Type: application/json');
include 'db_connection.php';

$student_id = $_POST['student_id'] ?? '';
$section = strtoupper($_POST['section'] ?? '');

if (empty($student_id) || empty($section)) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code data.']);
    exit;
}

date_default_timezone_set('Asia/Manila');
$scan_time_obj = new DateTime();
$scan_time_sql = $scan_time_obj->format('Y-m-d H:i:s');
$today_start = $scan_time_obj->format('Y-m-d 00:00:00');
$today_end = $scan_time_obj->format('Y-m-d 23:59:59');

// 1. Check for duplicate attendance
$stmt_check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND scan_time BETWEEN ? AND ?");
$stmt_check->bind_param("sss", $student_id, $today_start, $today_end);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Attendance already recorded for today.']);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// 2. Find the student's current class schedule to get the correct start time
$current_day = $scan_time_obj->format('l'); // Full day name, e.g., "Monday"
$current_time = $scan_time_obj->format('H:i:s');
$stmt_schedule = $conn->prepare("SELECT start_time FROM schedules WHERE section = ? AND day_of_week = ? AND ? BETWEEN start_time AND end_time");
$stmt_schedule->bind_param("sss", $section, $current_day, $current_time);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();

if ($schedule_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => "Scan rejected. No class scheduled for section $section at this time."]);
    $stmt_schedule->close();
    $conn->close();
    exit;
}
$class_start_time = $schedule_result->fetch_assoc()['start_time'];
$stmt_schedule->close();

// 3. Determine status (present or late) based on the specific class start time and grace period
$rules_result = $conn->query("SELECT content FROM rules WHERE name = 'grace_period' LIMIT 1");
$grace_period_minutes = ($rules_result->num_rows > 0) ? (int) $rules_result->fetch_assoc()['content'] : 15;

$class_start_datetime = new DateTime(date('Y-m-d') . ' ' . $class_start_time);
$deadline = $class_start_datetime->modify("+{$grace_period_minutes} minutes");
$status = ($scan_time_obj > $deadline) ? 'late' : 'present';

// 4. Insert the attendance record (This ensures it's saved for reports)
$stmt_insert = $conn->prepare("INSERT INTO attendance (student_id, section, status, scan_time) VALUES (?, ?, ?, ?)");
$stmt_insert->bind_param("ssss", $student_id, $section, $status, $scan_time_sql);

if ($stmt_insert->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Attendance Recorded: ' . ucfirst($status),
        'status' => $status,
        'time' => $scan_time_obj->format('h:i:s A')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Could not save attendance.']);
}

$stmt_insert->close();
$conn->close();
?>