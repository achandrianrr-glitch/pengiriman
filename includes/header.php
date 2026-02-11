<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
$base = rtrim(BASE_URL, '/') . '/';
$title = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME . ' - Admin Panel';
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Preload darkmode (anti flicker) -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $base ?>assets/css/dark-mode.css">
    <link rel="stylesheet" href="<?= $base ?>assets/css/custom.css">

    <!-- Chart.js hanya untuk dashboard (lebih ringan) -->
    <?php if ($currentPage === 'dashboard'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
<body>
    <div class="app-container">
        <nav class="navbar">
            <div class="navbar-left">
                <button id="sidebar-toggle" class="hamburger" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>

                <div class="brand">
                    <img src="<?= $base ?>assets/img/logo.png" alt="SoftSend Logo"
                    style="height:70px; width:auto; max-height:none;">
                    <span><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="navbar-right">
                <button id="dark-mode-toggle" class="hamburger" aria-label="Toggle dark mode">
                    <i class="bi bi-moon"></i>
                </button>

                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:38px;height:38px;border-radius:14px;background:rgba(107,159,212,0.14);display:flex;align-items:center;justify-content:center;font-weight:900;">
                        <?= htmlspecialchars(mb_substr($_SESSION['admin_nama'] ?? 'A', 0, 1), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div style="display:flex; flex-direction:column; line-height:1.1;">
                        <span style="font-weight:900; font-size:13px;"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:800; font-size:12px; color:var(--text-secondary);">Admin Panel</span>
                    </div>
                </div>
            </div>
        </nav>