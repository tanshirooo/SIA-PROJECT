<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';
// Fetch departments for the filter dropdown
$departments_result = $conn->query("SELECT name FROM departments WHERE name != 'Administration' ORDER BY name ASC");
$departments = $departments_result->fetch_all(MYSQLI_ASSOC);
// NOTE: Year levels are now hardcoded in the HTML below, so the DB query is removed.
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</p>
                </div>
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
            </header>

            <div class="dashboard-controls">
                <div class="control-group">
                    <label for="department-filter">Filter by Department</label>
                    <select id="department-filter" class="form-input">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control-group">
                    <label for="year-level-filter">Filter by Year Level</label>
                    <select id="year-level-filter" class="form-input">
                        <option value="">All Year Levels</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                    </select>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card total">
                    <div class="stat-value" id="total-students">...</div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card present">
                    <div class="stat-value" id="present-today">...</div>
                    <div class="stat-label">Present Today</div>
                </div>
                <div class="stat-card late">
                    <div class="stat-value" id="late-today">...</div>
                    <div class="stat-label">Late Today</div>
                </div>
                <div class="stat-card absent">
                    <div class="stat-value" id="absent-today">...</div>
                    <div class="stat-label">Absent Today</div>
                </div>
            </div>

            <div class="card chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>

        </div>
        <div class="overlay"></div>
    </div>

    <script>
        const deptFilter = document.getElementById('department-filter');
        const yearFilter = document.getElementById('year-level-filter');
        let attendanceChart = null;

        function getCssVariable(variable) {
            return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        }

        async function fetchDashboardData() {
            const department = deptFilter.value;
            const yearLevel = yearFilter.value;
            document.querySelectorAll('.stat-value').forEach(el => el.textContent = '...');

            const response = await fetch(`get_dashboard_data.php?department=${department}&year_level=${yearLevel}`);
            const data = await response.json();

            document.getElementById('total-students').textContent = data.stats.totalStudents;
            document.getElementById('present-today').textContent = data.stats.presentToday;
            document.getElementById('late-today').textContent = data.stats.lateToday;
            document.getElementById('absent-today').textContent = data.stats.absentToday;

            const chartData = {
                labels: data.chart.labels,
                datasets: [{
                    label: 'Present',
                    data: data.chart.presentData,
                    borderColor: getCssVariable('--success'),
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Late',
                    data: data.chart.lateData,
                    borderColor: getCssVariable('--warning'),
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Absent',
                    data: data.chart.absentData,
                    borderColor: getCssVariable('--error'),
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }
                ]
            };
            if (attendanceChart) {
                attendanceChart.data = chartData;
                attendanceChart.update();
            } else {
                const ctx = document.getElementById('attendanceChart').getContext('2d');
                attendanceChart = new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        }
        deptFilter.addEventListener('change', fetchDashboardData);
        yearFilter.addEventListener('change', fetchDashboardData);
        document.addEventListener('DOMContentLoaded', fetchDashboardData);

        const menuToggle = document.getElementById('menu-toggle'),
            sidebar = document.querySelector('.sidebar'),
            overlay = document.querySelector('.overlay');
        menuToggle.addEventListener('click', () => {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    </script>
</body>

</html>