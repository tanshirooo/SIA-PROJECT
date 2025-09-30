<?php
session_start();
header('Content-Type: application/json');
include 'db_connection.php';

if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$is_admin_or_teacher = in_array($_SESSION['role'], ['teacher', 'admin']);
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_data':
        getData($conn);
        break;
    case 'save_schedule':
        if ($is_admin_or_teacher)
            saveSchedule($conn);
        break;
    case 'delete_schedule':
        if ($is_admin_or_teacher)
            deleteSchedule($conn);
        break;
    case 'save_rules':
        if ($is_admin_or_teacher)
            saveRules($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

function getData($conn)
{
    $schedules_sql = "
        SELECT 
            id, course_name, section, instructor, department, day_of_week, 
            TIME_FORMAT(start_time, '%H:%i') as start_time, 
            TIME_FORMAT(end_time, '%H:%i') as end_time,
            announcement
        FROM schedules 
        ORDER BY course_name, day_of_week, start_time
    ";
    $schedules = $conn->query($schedules_sql)->fetch_all(MYSQLI_ASSOC);
    $rules_result = $conn->query("SELECT name, content FROM rules");
    $rules = [];
    while ($row = $rules_result->fetch_assoc()) {
        $rules[$row['name']] = $row['content'];
    }

    // FIXED: Fetch teacher's department along with their name
    $teachers = $conn->query("SELECT teacher_id, first_name, last_name, department FROM teachers ORDER BY last_name")->fetch_all(MYSQLI_ASSOC);

    $departments = $conn->query("SELECT name FROM departments WHERE name != 'Administration' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['schedules' => $schedules, 'rules' => $rules, 'teachers' => $teachers, 'departments' => $departments]);
}

function saveSchedule($conn)
{
    // This function remains the same as the previous version
    $id = $_POST['id'] ?? 0;
    $course_name = $_POST['course_name'] ?? '';
    $section = $_POST['section'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    $department = $_POST['department'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $announcement = $_POST['announcement'] ?? null;
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE schedules SET course_name=?, section=?, instructor=?, department=?, day_of_week=?, start_time=?, end_time=?, announcement=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $course_name, $section, $instructor, $department, $day_of_week, $start_time, $end_time, $announcement, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO schedules (course_name, section, instructor, department, day_of_week, start_time, end_time, announcement) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $course_name, $section, $instructor, $department, $day_of_week, $start_time, $end_time, $announcement);
    }
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save schedule.']);
    }
    $stmt->close();
}

function deleteSchedule($conn)
{
    // This function remains the same as the previous version
    $id = $_POST['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID required.']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule.']);
    }
    $stmt->close();
}

function saveRules($conn)
{
    // This function remains the same as the previous version
    $grace_period = $_POST['grace_period'] ?? 15;
    $stmt = $conn->prepare("UPDATE rules SET content = ? WHERE name = 'grace_period'");
    $stmt->bind_param("s", $grace_period);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rules updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update rules.']);
    }
    $stmt->close();
}
?>