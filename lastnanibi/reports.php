<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: login.php');
    exit;
}
$section_filter = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : null;
$date_filter = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Reports - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <?php if ($section_filter) : ?>
                <header class="main-header">
                    <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                    <div class="header-text">
                        <h1>Attendance for <?= $section_filter ?></h1>
                        <p>Report for date: <?= date("F j, Y", strtotime($date_filter)) ?></p>
                    </div>
                </header>
                <div class="card">
                    <div class="report-controls">
                        <a href="reports.php?date=<?= $date_filter ?>" class="btn btn-secondary" style="width: auto;">&larr; Back to Summary</a>
                        <button id="export-btn" class="btn btn-primary" style="width: auto;">Export to CSV</button>
                    </div>
                    <table class="data-table" id="details-table">
                        <thead><tr><th>Student ID</th><th>Name</th><th>Status</th><th>Time In</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            <?php else : ?>
                <header class="main-header">
                    <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                    <div class="header-text">
                        <h1>Attendance Reports</h1>
                        <p>Overall attendance summary by section.</p>
                    </div>
                </header>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="filter-group-inline">
                        <label for="date-filter">Showing reports for date:</label>
                        <input type="date" id="date-filter" class="form-input" value="<?= $date_filter ?>">
                    </div>
                </div>
                <div class="report-grid" id="report-summary-grid"></div>
            <?php endif; ?>
        </div>
        <div class="overlay"></div>
    </div>
    <script>
        const dateFilter = document.getElementById('date-filter');
        let detailedRecords = [];
        <?php if ($section_filter) : ?>
            const reportBody = document.querySelector('#details-table tbody');
            const exportBtn = document.getElementById('export-btn');
            async function fetchDetailedReport() {
                const url = `reports_api.php?action=get_details&date=<?= $date_filter ?>&section=<?= $section_filter ?>`;
                const response = await fetch(url);
                detailedRecords = await response.json();
                renderDetailedReport(detailedRecords);
            }
            function renderDetailedReport(records) {
                reportBody.innerHTML = '';
                if (records.length === 0) { reportBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 2rem;">No students found for this section.</td></tr>`; return; }
                records.forEach(rec => { reportBody.innerHTML += `<tr><td>${rec.student_id}</td><td>${rec.last_name}, ${rec.first_name}</td><td><span class="status-badge status-${rec.status.toLowerCase()}">${rec.status}</span></td><td>${rec.scan_time || 'N/A'}</td></tr>`; });
            }
            function exportToCsv(data, filename) {
                const headers = ['Student ID', 'Last Name', 'First Name', 'Status', 'Time'];
                const rows = data.map(rec => [rec.student_id, rec.last_name, rec.first_name, rec.status, rec.scan_time]);
                let csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
            exportBtn.addEventListener('click', () => {
                const filename = `Attendance_<?= $section_filter ?>_<?= $date_filter ?>.csv`;
                exportToCsv(detailedRecords, filename);
            });
            document.addEventListener('DOMContentLoaded', fetchDetailedReport);
        <?php else : ?>
            const summaryGrid = document.getElementById('report-summary-grid');
            async function fetchSummaryReport() {
                const date = dateFilter.value;
                const response = await fetch(`reports_api.php?action=get_summary&date=${date}`);
                const data = await response.json();
                renderSummary(data, date);
            }
            function renderSummary(sections, date) {
                summaryGrid.innerHTML = '';
                if (sections.length === 0) { summaryGrid.innerHTML = '<div class="card" style="grid-column: 1 / -1; text-align: center;">No section data available for this date.</div>'; return; }
                const iconPresent = `<svg class="icon" fill="var(--success)" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>`;
                const iconLate = `<svg class="icon" fill="var(--warning)" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"></path></svg>`;
                const iconAbsent = `<svg class="icon" fill="var(--error)" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path></svg>`;
                sections.forEach(sec => {
                    const absent = sec.total - sec.present;
                    const cardHtml = `<a href="?section=${sec.section}&date=${date}" class="report-section-card v2"><div class="report-section-header"><h3>${sec.section}</h3><span class="student-count">${sec.total} Students</span></div><ul class="attendance-summary-list"><li class="summary-item"><div class="label-group">${iconPresent} <span>Present</span></div><span class="value present">${sec.present}</span></li><li class="summary-item"><div class="label-group">${iconLate} <span>Late</span></div><span class="value late">${sec.late}</span></li><li class="summary-item"><div class="label-group">${iconAbsent} <span>Absent</span></div><span class="value absent">${absent > 0 ? absent : 0}</span></li></ul></a>`;
                    summaryGrid.insertAdjacentHTML('beforeend', cardHtml);
                });
            }
            dateFilter.addEventListener('change', () => { const url = new URL(window.location); url.searchParams.set('date', dateFilter.value); window.history.pushState({}, '', url); fetchSummaryReport(); });
            document.addEventListener('DOMContentLoaded', fetchSummaryReport);
        <?php endif; ?>
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        if(menuToggle) { menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }
    </script>
</body>
</html>