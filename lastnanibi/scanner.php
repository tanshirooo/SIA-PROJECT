<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: scan_redirect.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Scanner - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                <div class="header-text">
                    <h1>Attendance Scanner</h1>
                    <p>Position a student's QR code inside the frame to scan.</p>
                </div>
            </header>

            <div class="scanner-grid">
                <div class="scanner-video-container">
                    <div id="qr-reader"></div>
                    <div class="scanner-overlay">
                        <div id="scanner-box-overlay" class="scanner-box">
                            <span class="scanner-corner-bottom-left"></span>
                            <span class="scanner-corner-bottom-right"></span>
                        </div>
                    </div>
                </div>
                <div class="card scan-log-card">
                    <h2 class="card-title">Recent Scans</h2>
                    <ul class="scan-log" id="scan-log">
                        <li style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">Waiting for
                            scans...</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scanLog = document.getElementById('scan-log');
            let lastScanTime = 0;
            const scanCooldown = 3000;
            function onScanSuccess(decodedText, decodedResult) {
                const now = Date.now();
                if (now - lastScanTime < scanCooldown) return;
                lastScanTime = now;
                try {
                    const qrData = JSON.parse(decodedText);
                    if (!qrData.student_id || !qrData.name || !qrData.section) { throw new Error("Invalid QR code data"); }
                    const formData = new FormData();
                    formData.append('student_id', qrData.student_id);
                    formData.append('section', qrData.section);
                    fetch('updateattendance.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(result => {
                            if (scanLog.querySelector('li').textContent.includes('Waiting for scans...')) { scanLog.innerHTML = ''; }
                            const logEntry = document.createElement('li');
                            logEntry.className = 'scan-log-entry';
                            let statusClass = result.success ? (result.status === 'late' ? 'late' : 'present') : 'error';
                            logEntry.innerHTML = `<div class="scan-status-icon ${statusClass}"></div><div class="scan-info"><div class="student-name">${qrData.name}</div><div class="scan-time">${result.message || 'Scan Error'} - ${new Date().toLocaleTimeString()}</div></div>`;
                            scanLog.prepend(logEntry);
                            if (result.success) {
                                let alertIcon = 'success';
                                if (result.status === 'late') { alertIcon = 'warning'; }
                                Swal.fire({ icon: alertIcon, title: result.message, text: `${qrData.name} (${qrData.student_id})`, timer: 2500, showConfirmButton: false, toast: true, position: 'top-end' });
                            } else { Swal.fire({ icon: 'error', title: 'Scan Error', text: result.message }); }
                        });
                } catch (e) { Swal.fire({ icon: 'error', title: 'Invalid QR Code', text: 'This QR code is not valid for SAMS-PE.' }); }
            }
            const qrboxFunction = function (viewfinderWidth, viewfinderHeight) {
                let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                let qrboxSize = Math.floor(minEdge * 0.7);
                document.getElementById('scanner-box-overlay').style.width = `${qrboxSize}px`;
                document.getElementById('scanner-box-overlay').style.height = `${qrboxSize}px`;
                return { width: qrboxSize, height: qrboxSize };
            }
            const html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
                fps: 15,
                qrbox: qrboxFunction,
                rememberLastUsedCamera: true,
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
            }, false);
            html5QrcodeScanner.render(onScanSuccess, (error) => { });
            const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
            menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
            overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });
        });
    </script>
</body>

</html>