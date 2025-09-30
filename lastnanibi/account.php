<?php
header('Content-Type: application/json');
include 'db_connection.php';
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'login':
        handleLogin($conn);
        break;
    case 'register_student':
        handleStudentRegistration($conn);
        break;
    case 'register_teacher':
        handleTeacherRegistration($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
$conn->close();

function handleLogin($conn)
{
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'ID and password are required.']);
        return;
    }
    $stmt = $conn->prepare("SELECT password, role FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        $name_query = ($user['role'] === 'student') ? "SELECT first_name, last_name FROM students WHERE student_id = ?" : "SELECT first_name, last_name FROM teachers WHERE teacher_id = ?";
        $stmt_name = $conn->prepare($name_query);
        $stmt_name->bind_param("s", $username);
        $stmt_name->execute();
        $name_result = $stmt_name->get_result()->fetch_assoc();
        $stmt_name->close();
        $_SESSION['full_name'] = $name_result ? trim("{$name_result['first_name']} {$name_result['last_name']}") : $username;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or password.']);
    }
}

function handleStudentRegistration($conn)
{
    $required_fields = ['student_id', 'first_name', 'last_name', 'gender', 'email', 'password', 'department', 'year_level', 'section'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
            return;
        }
    }
    if (!preg_match('/^[0-9]{12}$/', $_POST['student_id'])) {
        echo json_encode(['success' => false, 'message' => 'Student ID must be exactly 12 digits.']);
        return;
    }
    if (strlen($_POST['password']) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        return;
    }

    // Normalize the section input
    $section = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['section']));

    $conn->begin_transaction();
    try {
        $stmt_student = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, gender, email, department, year_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_student->bind_param("ssssssss", $_POST['student_id'], $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['email'], $_POST['department'], $_POST['year_level'], $section);
        $stmt_student->execute();
        $stmt_student->close();

        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt_account = $conn->prepare("INSERT INTO accounts (username, password, role) VALUES (?, ?, 'student')");
        $stmt_account->bind_param("ss", $_POST['student_id'], $hashed_password);
        $stmt_account->execute();
        $stmt_account->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => ($conn->errno == 1062) ? 'A student with this ID or email already exists.' : 'An error occurred during registration.']);
    }
}

function handleTeacherRegistration($conn)
{
    $required_fields = ['invitation_code', 'teacher_id', 'first_name', 'last_name', 'gender', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
            return;
        }
    }
    if (!preg_match('/^[0-9]{12}$/', $_POST['teacher_id'])) {
        echo json_encode(['success' => false, 'message' => 'Teacher ID must be exactly 12 digits.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_code = $conn->prepare("SELECT id FROM invitation_codes WHERE code = ? AND is_used = FALSE");
        $stmt_code->bind_param("s", $_POST['invitation_code']);
        $stmt_code->execute();
        $result_code = $stmt_code->get_result();
        if ($result_code->num_rows === 0) {
            throw new Exception('Invalid or already used invitation code.');
        }
        $code_id = $result_code->fetch_assoc()['id'];
        $stmt_code->close();

        $stmt_update_code = $conn->prepare("UPDATE invitation_codes SET is_used = TRUE, used_by = ? WHERE id = ?");
        $stmt_update_code->bind_param("si", $_POST['teacher_id'], $code_id);
        $stmt_update_code->execute();
        $stmt_update_code->close();

        $stmt_teacher = $conn->prepare("INSERT INTO teachers (teacher_id, first_name, last_name, gender, email) VALUES (?, ?, ?, ?, ?)");
        $stmt_teacher->bind_param("sssss", $_POST['teacher_id'], $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['email']);
        $stmt_teacher->execute();
        $stmt_teacher->close();

        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt_account = $conn->prepare("INSERT INTO accounts (username, password, role) VALUES (?, ?, 'teacher')");
        $stmt_account->bind_param("ss", $_POST['teacher_id'], $hashed_password);
        $stmt_account->execute();
        $stmt_account->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => ($conn->errno == 1062) ? 'A teacher with this ID or email already exists.' : $e->getMessage()]);
    }
}
?>