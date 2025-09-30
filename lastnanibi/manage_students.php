<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';
$departments = $conn->query("SELECT DISTINCT name FROM departments WHERE name != 'Administration' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$students_result = $conn->query("SELECT id, student_id, first_name, last_name, email, section, year_level, department, gender FROM students ORDER BY last_name, first_name");
$students = $students_result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Students - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                <div class="header-text">
                    <h1>Manage Students</h1>
                    <p>Search, filter, edit, or remove student records.</p>
                </div>
            </header>
            <div class="card">
                <div class="card-toolbar">
                    <input type="text" id="search-input" class="form-input" placeholder="Search by name or ID...">
                    <div class="filter-group">
                        <select id="dept-filter" class="form-input">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d)
                                echo "<option value='{$d['name']}'>{$d['name']}</option>"; ?>
                        </select>
                        <select id="year-filter-main" class="form-input">
                            <option value="">All Year Levels</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                        </select>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr data-id="<?= $s['id'] ?>" data-studentid="<?= htmlspecialchars($s['student_id']) ?>"
                                    data-firstname="<?= htmlspecialchars($s['first_name']) ?>"
                                    data-lastname="<?= htmlspecialchars($s['last_name']) ?>"
                                    data-email="<?= htmlspecialchars($s['email']) ?>"
                                    data-section="<?= htmlspecialchars($s['section']) ?>"
                                    data-yearlevel="<?= htmlspecialchars($s['year_level']) ?>"
                                    data-department="<?= htmlspecialchars($s['department']) ?>"
                                    data-gender="<?= htmlspecialchars($s['gender']) ?>">
                                    <td><?= htmlspecialchars($s['student_id']) ?></td>
                                    <td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
                                    <td><?= htmlspecialchars($s['section']) ?></td>
                                    <td><?= htmlspecialchars($s['department']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-edit" onclick="openEditModal(this.closest('tr'))"
                                                title="Edit Student"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-delete" onclick="deleteStudent(<?= $s['id'] ?>)"
                                                title="Delete Student"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const allDepartments = <?= json_encode($departments) ?>;
        function openEditModal(row) { const student = row.dataset; const yearLevelOptions = `<option value="1st Year" ${student.yearlevel === '1st Year' ? 'selected' : ''}>1st Year</option><option value="2nd Year" ${student.yearlevel === '2nd Year' ? 'selected' : ''}>2nd Year</option>`; const departmentOptions = allDepartments.map(dept => `<option value="${dept.name}" ${student.department === dept.name ? 'selected' : ''}>${dept.name}</option>`).join(''); Swal.fire({ title: 'Edit Student Information', html: `<form id="edit-student-form" style="text-align: left;"><input type="hidden" name="id" value="${student.id}"><div class="input-group"><label>Student ID</label><input type="text" name="student_id" class="form-input" value="${student.studentid}" placeholder="e.g., 240000001593" pattern="[0-9]{12}" maxlength="12" required></div><div class="input-group"><label>First Name</label><input type="text" name="first_name" class="form-input" value="${student.firstname}" placeholder="e.g., Juan" required></div><div class="input-group"><label>Last Name</label><input type="text" name="last_name" class="form-input" value="${student.lastname}" placeholder="e.g., Dela Cruz" required></div><div class="input-group"><label>Gender</label><select name="gender" class="form-input" required><option value="Male" ${student.gender === 'Male' ? 'selected' : ''}>Male</option><option value="Female" ${student.gender === 'Female' ? 'selected' : ''}>Female</option></select></div><div class="input-group"><label>Email</label><input type="email" name="email" class="form-input" value="${student.email}" placeholder="e.g., juan.delacruz@example.com" required></div><div class="input-group"><label>Department</label><select name="department" class="form-input" required>${departmentOptions}</select></div><div class="input-group"><label>Year Level</label><select name="year_level" class="form-input" required>${yearLevelOptions}</select></div><div class="input-group"><label>Section</label><input type="text" name="section" class="form-input" value="${student.section}" placeholder="e.g., BSIT-2A" style="text-transform:uppercase;" required></div></form>`, showCancelButton: true, confirmButtonText: 'Save Changes', preConfirm: () => { const form = document.getElementById('edit-student-form'); const formData = new FormData(form); formData.append('action', 'update_student'); return fetch('manage_students_api.php', { method: 'POST', body: formData }).then(res => res.json().then(data => { if (!data.success) throw new Error(data.message); return data; })).catch(error => Swal.showValidationMessage(`Request failed: ${error}`)); } }).then((result) => { if (result.isConfirmed) { Swal.fire('Success!', 'Student record updated.', 'success').then(() => window.location.reload()); } }); }
        function deleteStudent(id) { Swal.fire({ title: 'Are you sure?', text: "This action is permanent!", icon: 'warning', showCancelButton: true, confirmButtonColor: 'var(--error)', confirmButtonText: 'Yes, delete' }).then(async (result) => { if (result.isConfirmed) { const formData = new FormData(); formData.append('action', 'delete_student'); formData.append('id', id); try { const res = await fetch('manage_students_api.php', { method: 'POST', body: formData }); const data = await res.json(); if (data.success) { Swal.fire('Deleted!', '', 'success').then(() => window.location.reload()); } else { throw new Error(data.message); } } catch (error) { Swal.fire('Error', error.message, 'error'); } } }); }
        const searchInput = document.getElementById('search-input'), deptFilter = document.getElementById('dept-filter'), yearFilter = document.getElementById('year-filter-main'), tableRows = document.querySelectorAll('#students-table tbody tr'); function filterTable() { const searchTerm = searchInput.value.toLowerCase(), selectedDept = deptFilter.value, selectedYear = yearFilter.value; tableRows.forEach(row => { const name = row.cells[1].textContent.toLowerCase(), studentId = row.cells[0].textContent.toLowerCase(), department = row.dataset.department, yearLevel = row.dataset.yearlevel; const matchesSearch = name.includes(searchTerm) || studentId.includes(searchTerm); const matchesDept = !selectedDept || department === selectedDept; const matchesYear = !selectedYear || yearLevel === selectedYear; row.style.display = (matchesSearch && matchesDept && matchesYear) ? '' : 'none'; }); }
        searchInput.addEventListener('input', filterTable); deptFilter.addEventListener('change', filterTable); yearFilter.addEventListener('change', filterTable);
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });
    </script>
</body>

</html> 