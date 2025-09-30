<?php
$activePage = basename($_SERVER['PHP_SELF'], ".php");
$role = $_SESSION['role'] ?? 'student';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="logo.png" alt="SAMS-PE Logo">
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php" class="<?= ($activePage == 'dashboard') ? 'active' : '' ?>">Dashboard</a>
            </li>
            <li>
                <a href="schedule.php" class="<?= ($activePage == 'schedule') ? 'active' : '' ?>">Schedule & Rules</a>
            </li>
            <?php if ($role === 'student'): ?>
                <li>
                    <a href="generate_my_qr.php" class="<?= ($activePage == 'generate_my_qr') ? 'active' : '' ?>">My QR
                        Code</a>
                </li>
            <?php endif; ?>
            <?php if (in_array($role, ['teacher', 'admin'])): ?>
                <li>
                    <a href="scan_redirect.php"
                        class="<?= ($activePage == 'scanner' || $activePage == 'scan_redirect') ? 'active' : '' ?>">Scan
                        Attendance</a>
                </li>
                <li>
                    <a href="manage_students.php" class="<?= ($activePage == 'manage_students') ? 'active' : '' ?>">Manage
                        Students</a>
                </li>
                <li>
                    <a href="reports.php" class="<?= ($activePage == 'reports') ? 'active' : '' ?>">Attendance Reports</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">Logout</a>
    </div>
</aside>