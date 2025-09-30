<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $new_code = bin2hex(random_bytes(8));
    $stmt = $conn->prepare("INSERT INTO invitation_codes (code) VALUES (?)");
    $stmt->bind_param("s", $new_code);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_codes.php?generated=true");
    exit;
}
$codes_result = $conn->query("SELECT code, is_used, used_by FROM invitation_codes ORDER BY created_at DESC");
$all_codes = $codes_result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Invitation Codes - SAMS-PE</title>
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
                    <h1>Manage Teacher Codes</h1>
                    <p>Generate new invitation codes for teacher registration.</p>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
                <div class="card">
                    <h2 class="card-title">Generate New Code</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="generate">
                        <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">Click the button below to create
                            a new, unique 16-character code.</p>
                        <button type="submit" class="btn btn-primary">Generate Code</button>
                    </form>
                </div>
                <div class="card">
                    <h2 class="card-title">Existing Codes</h2>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invitation Code</th>
                                    <th>Status</th>
                                    <th>Used By (Teacher ID)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_codes)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center; padding: 2rem;">No codes generated yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_codes as $code): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($code['code']); ?></strong></td>
                                            <td>
                                                <?php if ($code['is_used']): ?>
                                                    <span class="status-badge status-absent">Used</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-present">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($code['used_by'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('generated')) {
                Swal.fire({ icon: 'success', title: 'New Code Generated!', timer: 2000, showConfirmButton: false });
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });
    </script>
</body>

</html>