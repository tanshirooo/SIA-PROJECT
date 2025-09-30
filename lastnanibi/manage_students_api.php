<?php
session_start();
header('Content-Type: application/json');
include 'db_connection.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$action = $_POST['action'] ?? '';
switch ($action) {
    case 'update_student':
        updateStudent($conn);
        break;
    case 'delete_student':
        deleteStudent($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
$conn->close();

function updateStudent($conn)
{
    $id = $_POST['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is missing.']);
        return;
    }
    $stmt_orig = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
    $stmt_orig->bind_param("i", $id);
    $stmt_orig->execute();
    $original_student_id = $stmt_orig->get_result()->fetch_assoc()['student_id'];
    $stmt_orig->close();

    // Normalize the section input
    $new_student_id = $_POST['student_id'] ?? '';
    $section = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['section']));

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, gender=?, email=?, department=?, year_level=?, section=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $new_student_id, $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['email'], $_POST['department'], $_POST['year_level'], $section, $id);
        $stmt->execute();

        if ($original_student_id !== $new_student_id) {
            $stmt_acc = $conn->prepare("UPDATE accounts SET username = ? WHERE username = ? AND role = 'student'");
            $stmt_acc->bind_param("ss", $new_student_id, $original_student_id);
            $stmt_acc->execute();
            $stmt_acc->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student record updated.']);
    } catch (Exception $e) {
        $conn->rollback();
        if ($conn->errno == 1062) {
            echo json_encode(['success' => false, 'message' => 'The new Student ID or Email already exists for another student.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed. Error: ' . $e->getMessage()]);
        }
    }
    $stmt->close();
}

function deleteStudent($conn)
{
    $id = $_POST['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required.']);
        return;
    }
    $conn->begin_transaction();
    try {
        $stmt_get = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $student_id_to_delete = $stmt_get->get_result()->fetch_assoc()['student_id'];
        $stmt_get->close();

        if ($student_id_to_delete) {
            $stmt1 = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM accounts WHERE username = ? AND role = 'student'");
            $stmt2->bind_param("s", $student_id_to_delete);
            $stmt2->execute();
            $stmt2->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete student.']);
    }
}
?>