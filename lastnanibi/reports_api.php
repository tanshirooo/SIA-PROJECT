<?php
header('Content-Type: application/json');
include 'db_connection.php';
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'get_summary':
        getSummaryReport($conn, $_GET['date'] ?? date('Y-m-d'));
        break;
    case 'get_details':
        getDetailedReport($conn, $_GET['date'] ?? date('Y-m-d'), $_GET['section'] ?? null);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

// FIXED: Rewritten this function to be more accurate and efficient
function getSummaryReport($conn, $date)
{
    // Step 1: Get all sections and their total number of enrolled students.
    $sections = [];
    $totals_sql = "SELECT section, COUNT(*) as total_students FROM students GROUP BY section ORDER BY section";
    $totals_result = $conn->query($totals_sql);
    while ($row = $totals_result->fetch_assoc()) {
        $sections[$row['section']] = ['total' => (int) $row['total_students'], 'present' => 0, 'late' => 0];
    }

    // Step 2: Get all attendance data for the selected date in a single, efficient query.
    $attendance_sql = "
        SELECT
            section,
            COUNT(DISTINCT student_id) AS present_count,
            COUNT(DISTINCT CASE WHEN status = 'late' THEN student_id END) AS late_count
        FROM attendance
        WHERE DATE(scan_time) = ?
        GROUP BY section
    ";
    $stmt = $conn->prepare($attendance_sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $attendance_result = $stmt->get_result();

    // Step 3: Merge the attendance data into our list of sections.
    while ($row = $attendance_result->fetch_assoc()) {
        if (isset($sections[$row['section']])) {
            $sections[$row['section']]['present'] = (int) $row['present_count'];
            $sections[$row['section']]['late'] = (int) $row['late_count'];
        }
    }
    $stmt->close();

    // Step 4: Format the final array for output.
    $final_summary = [];
    foreach ($sections as $name => $data) {
        $final_summary[] = array_merge(['section' => $name], $data);
    }
    echo json_encode($final_summary);
}

function getDetailedReport($conn, $date, $section)
{
    if (empty($section)) {
        echo json_encode([]);
        return;
    }
    // Step 1: Get all students in the specified section.
    $student_list = [];
    $stmt_students = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE section = ? ORDER BY last_name, first_name");
    $stmt_students->bind_param("s", $section);
    $stmt_students->execute();
    $students_result = $stmt_students->get_result();
    while ($student = $students_result->fetch_assoc()) {
        // Assume everyone is absent until we find an attendance record.
        $student_list[$student['student_id']] = array_merge($student, ['status' => 'absent', 'scan_time' => null]);
    }
    $stmt_students->close();

    // Step 2: Get the attendance records for those students on the specified date.
    $stmt_attendance = $conn->prepare("SELECT student_id, status, TIME_FORMAT(scan_time, '%h:%i:%s %p') as scan_time FROM attendance WHERE DATE(scan_time) = ? AND section = ?");
    $stmt_attendance->bind_param("ss", $date, $section);
    $stmt_attendance->execute();
    $attendance_result = $stmt_attendance->get_result();
    while ($record = $attendance_result->fetch_assoc()) {
        // If a student has an attendance record, update their status.
        if (isset($student_list[$record['student_id']])) {
            $student_list[$record['student_id']]['status'] = $record['status'];
            $student_list[$record['student_id']]['scan_time'] = $record['scan_time'];
        }
    }
    $stmt_attendance->close();
    // Return the final list, converting from an associative array to a simple one.
    echo json_encode(array_values($student_list));
}
?>