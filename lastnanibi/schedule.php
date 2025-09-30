<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule & Rules - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <div><h1>Class Schedules & Rules</h1><p>View official schedules and post announcements for your class.</p></div>
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
            </header>
            <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
                <div class="card" style="flex: 2 1 600px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 class="card-title" style="margin:0;">Class Schedules</h2>
                        <?php if ($role !== 'student'): ?>
                            <button class="btn btn-primary" onclick="openScheduleModal()">+ Add Schedule</button>
                        <?php endif; ?>
                    </div>
                    <div class="report-filters" style="padding: 1rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1rem; flex-wrap: wrap;">
                        <input type="text" id="schedule-search" class="form-input" placeholder="Search course or section...">
                        <select id="schedule-dept-filter" class="form-input"></select>
                        <select id="schedule-instructor-filter" class="form-input"></select>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="schedule-table">
                            <thead><tr><th>Day & Time</th><th>Course</th><th>Section</th><th>Instructor</th><th>Announcement</th><?php if ($role !== 'student') echo '<th>Actions</th>'; ?></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="card" style="flex: 1 1 300px;">
                     <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 class="card-title" style="margin:0;">Attendance Policy</h2>
                        <?php if ($role !== 'student'): ?>
                            <div><button id="editRulesBtn" class="btn btn-secondary" onclick="toggleRulesEdit(true)">Edit</button><button id="cancelRulesBtn" class="btn btn-secondary" onclick="toggleRulesEdit(false)" style="display:none;">Cancel</button></div>
                        <?php endif; ?>
                    </div>
                    <div id="rules-info-view">
                        <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                            <label style="font-size: 0.9rem; color: var(--text-secondary); display:block; font-weight: 600;">GRACE PERIOD</label>
                            <p style="font-size: 2rem; font-weight: 700; color: var(--primary); line-height: 1.2; margin-top: 0.25rem;"><span id="grace_period_display">--</span> Minutes</p>
                            <p style="font-size: 0.9rem; color: var(--text-secondary);">This is the time allowed after class starts before you are marked 'Late'.</p>
                        </div>
                         <div style="padding: 1rem; background-color: var(--background); border-radius: var(--radius-md);"><h4 style="font-weight: 600; margin-bottom: 0.5rem;">Time Management Tip 💡</h4><p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-secondary);">Arrive on time to ensure you don't miss important announcements from your instructor.</p></div>
                    </div>
                    <form id="rules-edit-view" style="display:none;">
                        <div class="input-group"><label for="grace_period_input">Grace Period (Minutes)</label><input type="number" id="grace_period_input" name="grace_period" class="form-input"></div><button type="submit" class="btn btn-primary">Save Rules</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let initialData = {};
        const isAdminOrTeacher = '<?= $role ?>' !== 'student';
        const scheduleBody = document.getElementById('schedule-table').querySelector('tbody');

        async function loadData() { try { const res = await fetch('manage_schedule_api.php?action=get_data'); initialData = await res.json(); scheduleBody.innerHTML = ''; if(initialData.schedules.length === 0) { scheduleBody.innerHTML = `<tr><td colspan="${isAdminOrTeacher ? 6 : 5}" style="text-align:center; padding: 2rem;">No schedules have been added yet.</td></tr>`; } else { initialData.schedules.forEach(s => { const timeDisplay = `${formatTime(s.start_time)} - ${formatTime(s.end_time)}`; const announcementDisplay = s.announcement ? `<span title="${s.announcement}">${s.announcement.substring(0, 20)}...</span>` : '<span style="color: var(--text-secondary);">None</span>'; const actions = isAdminOrTeacher ? `<td><button class="btn btn-secondary" onclick='openScheduleModal(${JSON.stringify(s)})'>Edit</button> <button class="btn" style="background-color:var(--error);color:white;" onclick="deleteSchedule(${s.id})">Delete</button></td>` : ''; scheduleBody.innerHTML += `<tr data-department="${s.department}"><td><strong>${s.day_of_week}</strong><br>${timeDisplay}</td><td>${s.course_name}</td><td>${s.section}</td><td>${s.instructor}</td><td>${announcementDisplay}</td>${actions}</tr>`; }); } const deptFilter = document.getElementById('schedule-dept-filter'); const instructorFilter = document.getElementById('schedule-instructor-filter'); let deptOptions = '<option value="">All Departments</option>'; initialData.departments.forEach(d => deptOptions += `<option value="${d.name}">${d.name}</option>`); deptFilter.innerHTML = deptOptions; let instructorOptions = '<option value="">All Instructors</option>'; initialData.teachers.forEach(t => { const name = `${t.first_name} ${t.last_name}`; instructorOptions += `<option value="${name}">${name}</option>`; }); instructorFilter.innerHTML = instructorOptions; document.getElementById('grace_period_display').textContent = initialData.rules.grace_period; document.getElementById('grace_period_input').value = initialData.rules.grace_period; } catch (error) { Swal.fire('Error', 'Could not load data from the server.', 'error'); } }
        
        // --- FIXED: Redesigned modal with cleaner layout and stricter validation ---
        function openScheduleModal(schedule = null) {
            const isEdit = schedule !== null;
            const teacherOptions = initialData.teachers.map(t => `<option value="${`${t.first_name} ${t.last_name}`}" ${isEdit && `${t.first_name} ${t.last_name}` === schedule.instructor ? 'selected' : ''}>${t.first_name} ${t.last_name}</option>`).join('');
            const departmentOptions = initialData.departments.map(d => `<option value="${d.name}" ${isEdit && d.name === schedule.department ? 'selected' : ''}>${d.name}</option>`).join('');
            const courseOptions = ['PATH-FIT 1', 'PATH-FIT 2', 'PATH-FIT 3', 'PATH-FIT 4'].map(c => `<option value="${c}" ${isEdit && c === schedule.course_name ? 'selected' : ''}>${c}</option>`).join('');
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const dayOptions = days.map(day => `<option value="${day}" ${isEdit && day === schedule.day_of_week ? 'selected' : ''}>${day}</option>`).join('');

            Swal.fire({
                title: isEdit ? 'Edit Schedule' : 'Add New Schedule',
                html: `
                <form id="schedule-form" style="text-align: left;">
                    <input type="hidden" name="id" value="${isEdit ? schedule.id : '0'}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="input-group"><label>Course Name</label><select name="course_name" class="form-input" required><option value="" disabled selected>Select Course</option>${courseOptions}</select></div>
                        <div class="input-group"><label>Section</label><input type="text" name="section" class="form-input" value="${isEdit ? schedule.section : ''}" placeholder="e.g., BSIT-2A" required></div>
                        <div class="input-group"><label>Instructor</label><select name="instructor" class="form-input" required><option value="" disabled selected>Select Instructor</option>${teacherOptions}</select></div>
                        <div class="input-group"><label>Department</label><select name="department" class="form-input" required><option value="" disabled selected>Select Department</option>${departmentOptions}</select></div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 1rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 0.5rem;">
                        <div class="input-group"><label>Day of Week</label><select name="day_of_week" class="form-input" required>${dayOptions}</select></div>
                        <div class="input-group"><label>Start Time</label><input type="time" name="start_time" class="form-input" value="${isEdit ? schedule.start_time : ''}" required></div>
                        <div class="input-group"><label>End Time</label><input type="time" name="end_time" class="form-input" value="${isEdit ? schedule.end_time : ''}" required></div>
                    </div>
                    
                    <div class="input-group"><label>Announcement (Optional)</label><textarea name="announcement" class="form-input" rows="3" placeholder="e.g., Quiz next meeting...">${isEdit ? (schedule.announcement || '') : ''}</textarea></div>
                </form>`,
                showCancelButton: true, 
                confirmButtonText: 'Save Schedule',
                width: '700px', // A more compact width
                preConfirm: () => {
                    const form = document.getElementById('schedule-form');
                    let isValid = true;
                    form.querySelectorAll('[required]').forEach(input => {
                        input.classList.remove('error');
                        if (!input.value) {
                            input.classList.add('error');
                            isValid = false;
                        }
                    });

                    if (!isValid) {
                        Swal.showValidationMessage('Please fill out all required fields.');
                        return false; // This stops the submission
                    }
                    
                    const formData = new FormData(form); 
                    formData.append('action', 'save_schedule');
                    return fetch('manage_schedule_api.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (!data.success) throw new Error(data.message); return data; }).catch(error => Swal.showValidationMessage(`Request failed: ${error}`));
                }
            }).then(result => { if(result.isConfirmed) { Swal.fire('Success!', 'Schedule saved.', 'success'); loadData(); }});
        }
        
        // (The rest of the script remains the same)
        function formatTime(timeStr) { if(!timeStr) return ''; const [hour, minute] = timeStr.split(':'); const hourInt = parseInt(hour, 10); const ampm = hourInt >= 12 ? 'PM' : 'AM'; const formattedHour = hourInt % 12 || 12; return `${String(formattedHour).padStart(2, '0')}:${minute} ${ampm}`; }
        function filterSchedules() { const searchTerm = document.getElementById('schedule-search').value.toLowerCase(); const selectedDept = document.getElementById('schedule-dept-filter').value; const selectedInstructor = document.getElementById('schedule-instructor-filter').value; const rows = scheduleBody.querySelectorAll('tr'); let visibleRows = 0; rows.forEach(row => { if (!row.dataset.department) return; const course = row.cells[1].textContent.toLowerCase(); const section = row.cells[2].textContent.toLowerCase(); const instructor = row.cells[3].textContent; const department = row.dataset.department; const matchesSearch = course.includes(searchTerm) || section.includes(searchTerm); const matchesDept = !selectedDept || department === selectedDept; const matchesInstructor = !selectedInstructor || instructor === selectedInstructor; if (matchesSearch && matchesDept && matchesInstructor) { row.style.display = ''; visibleRows++; } else { row.style.display = 'none'; } }); let noResultsRow = scheduleBody.querySelector('.no-results'); if (noResultsRow) noResultsRow.remove(); if (visibleRows === 0 && initialData.schedules.length > 0) { scheduleBody.insertAdjacentHTML('beforeend', `<tr class="no-results"><td colspan="${isAdminOrTeacher ? 6 : 5}" style="text-align:center; padding: 2rem;">No schedules match your filters.</td></tr>`); } }
        function toggleRulesEdit(isEditing) { document.getElementById('rules-info-view').style.display = isEditing ? 'none' : 'block'; document.getElementById('rules-edit-view').style.display = isEditing ? 'block' : 'none'; document.getElementById('editRulesBtn').style.display = isEditing ? 'none' : 'inline-flex'; document.getElementById('cancelRulesBtn').style.display = isEditing ? 'inline-flex' : 'none'; }
        document.getElementById('rules-edit-view').addEventListener('submit', async function (e) { e.preventDefault(); const formData = new FormData(this); formData.append('action', 'save_rules'); try { const res = await fetch('manage_schedule_api.php', { method: 'POST', body: formData }); const data = await res.json(); if (data.success) { await Swal.fire('Success!', data.message, 'success'); await loadData(); toggleRulesEdit(false); } else { throw new Error(data.message); } } catch (error) { Swal.fire('Error', error.message, 'error'); } });
        function deleteSchedule(id) { Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: 'var(--error)', confirmButtonText: 'Yes, delete it' }).then(async (result) => { if (result.isConfirmed) { const formData = new FormData(); formData.append('action', 'delete_schedule'); formData.append('id', id); const res = await fetch('manage_schedule_api.php', { method: 'POST', body: formData }); const data = await res.json(); if (data.success) { Swal.fire('Deleted!', '', 'success'); loadData(); } } }); }
        document.addEventListener('DOMContentLoaded', loadData);
        document.getElementById('schedule-search').addEventListener('input', filterSchedules); document.getElementById('schedule-dept-filter').addEventListener('change', filterSchedules); document.getElementById('schedule-instructor-filter').addEventListener('change', filterSchedules);
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });
    </script>
</body>
</html>