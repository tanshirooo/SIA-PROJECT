<?php
session_start();
header('Content-Type: application/json');
include 'db_connection.php';
date_default_timezone_set('Asia/Manila');

// --- Filter Handling ---
$department = $_GET['department'] ?? '';
$year_level = $_GET['year_level'] ?? '';
$where_conditions = [];
$params = [];
$types = "";

if (!empty($department)) {
    $where_conditions[] = "s.department = ?";
    $params[] = $department;
    $types .= "s";
}
if (!empty($year_level)) {
    $where_conditions[] = "s.year_level = ?";
    $params[] = $year_level;
    $types .= "s";
}
$where_clause = count($where_conditions) > 0 ? " WHERE " . implode(" AND ", $where_conditions) : "";

// --- Calculate Stats ---
// 1. Total Students
$stmt_total = $conn->prepare("SELECT COUNT(id) as total FROM students s" . $where_clause);
if (!empty($params))
    $stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$totalStudents = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

// 2. Present Today (any status)
$today = date('Y-m-d');
$today_where_clause = $where_clause . (count($where_conditions) > 0 ? " AND " : " WHERE ") . "DATE(a.scan_time) = ?";
$today_params = array_merge($params, [$today]);
$today_types = $types . "s";

$stmt_present = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) as present FROM attendance a JOIN students s ON a.student_id = s.student_id" . $today_where_clause);
$stmt_present->bind_param($today_types, ...$today_params);
$stmt_present->execute();
$presentToday = $stmt_present->get_result()->fetch_assoc()['present'] ?? 0;
$stmt_present->close();

// 3. Late Today
$late_where_clause = $today_where_clause . " AND a.status = 'late'";
$stmt_late = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) as late FROM attendance a JOIN students s ON a.student_id = s.student_id" . $late_where_clause);
$stmt_late->bind_param($today_types, ...$today_params);
$stmt_late->execute();
$lateToday = $stmt_late->get_result()->fetch_assoc()['late'] ?? 0;
$stmt_late->close();

// 4. Absent Today
$absentToday = $totalStudents - $presentToday;

// --- Prepare Chart Data (Last 7 Days) ---
$chartLabels = [];
$chartPresentData = [];
$chartLateData = [];
$chartAbsentData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M d', strtotime($date));

    $day_params = array_merge($params, [$date]);
    $day_types = $types . "s";
    $day_where_clause = $where_clause . (count($where_conditions) > 0 ? " AND " : " WHERE ") . "DATE(a.scan_time) = ?";

    $stmtChartPresent = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) FROM attendance a JOIN students s ON a.student_id = s.student_id" . $day_where_clause);
    $stmtChartPresent->bind_param($day_types, ...$day_params);
    $stmtChartPresent->execute();
    $presentForDay = $stmtChartPresent->get_result()->fetch_row()[0] ?? 0;
    $chartPresentData[] = $presentForDay;
    $stmtChartPresent->close();

    $stmtChartLate = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) FROM attendance a JOIN students s ON a.student_id = s.student_id" . $day_where_clause . " AND a.status = 'late'");
    $stmtChartLate->bind_param($day_types, ...$day_params);
    $stmtChartLate->execute();
    $chartLateData[] = $stmtChartLate->get_result()->fetch_row()[0] ?? 0;
    $stmtChartLate->close();

    $chartAbsentData[] = $totalStudents > 0 ? $totalStudents - $presentForDay : 0;
}

// --- Final JSON Output ---
$output = [
    'stats' => [
        'totalStudents' => $totalStudents,
        'presentToday' => $presentToday,
        'lateToday' => $lateToday,
        'absentToday' => $absentToday > 0 ? $absentToday : 0
    ],
    'chart' => [
        'labels' => $chartLabels,
        'presentData' => $chartPresentData,
        'lateData' => $chartLateData,
        'absentData' => $chartAbsentData
    ]
];

echo json_encode($output);
$conn->close();
?>