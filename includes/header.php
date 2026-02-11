<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
$base = rtrim(BASE_URL, '/') . '/';
$title = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME . ' - Admin Panel';
$currentPage = $_GET['page'] ?? 'dashboard';

$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Preload theme (anti flicker) -->
    <script>
        (function() {
            try {
                const old = localStorage.getItem('darkMode');
                const hasNew = localStorage.getItem('theme');

                if (!hasNew && old) {
                    localStorage.setItem('theme', old === 'enabled' ? 'dark' : 'light');
                }

                const theme = localStorage.getItem('theme') || 'system';
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

                const shouldDark = (theme === 'dark') || (theme === 'system' && prefersDark);
                if (shouldDark) document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
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
    <link rel="stylesheet" href="<?= $base ?>assets/css/app.css">

    <!-- Chart.js hanya untuk dashboard (lebih ringan) -->
    <?php if ($currentPage === 'dashboard'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>

<body>
    <div class="app-container">

        <nav class="navbar">
            <div class="navbar-left" style="display:flex;align-items:center;gap:12px;min-width:0;">
                <button id="sidebar-toggle" class="hamburger" aria-label="Toggle sidebar"
                    style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-list"></i>
                </button>

                <!-- Brand: GRID (logo + text lebih nempel & rata kiri) -->
                <a href="<?= $base ?>?page=dashboard"
                    class="brand"
                    style="
                        display:grid;
                        grid-template-columns: 64px auto;
                        align-items:center;
                        column-gap:6px;              /* ini yang bikin text nempel */
                        text-decoration:none;
                        min-width:0;
                   ">
                    <img
                        src="<?= $base ?>assets/img/logo.png"
                        alt="SoftSend Logo"
                        style="
                            height:64px; width:64px; /* logo lebih besar */
                            object-fit:contain;
                            display:block;
                        "
                        decoding="async"
                        fetchpriority="high">

                    <span style="
                        font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
                        font-weight:900;
                        font-size:18px;
                        letter-spacing:-0.3px;
                        line-height:1.05;
                        color:inherit;
                        text-align:left;             /* text rata kiri */
                        justify-self:start;
                        white-space:nowrap;
                        overflow:hidden;
                        text-overflow:ellipsis;
                        margin-left:-1px;            /* dorong dikit biar makin rapat */
                    ">
                        <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
            </div>

            <!-- kanan: icon jaraknya dibuat lebih lega (bukan grid) -->
            <div class="navbar-right" style="display:flex;align-items:center;gap:16px;position:relative;">

                <!-- Theme toggle: 3 mode (System -> Light -> Dark) -->
                <button id="theme-toggle" class="hamburger" type="button"
                    aria-label="Toggle theme"
                    style="display:flex;align-items:center;justify-content:center;">
                    <i id="theme-icon" class="bi bi-circle-half"></i>
                </button>

                <!-- Admin profile icon + popup -->
                <div style="position:relative;">
                    <button id="admin-menu-toggle" class="hamburger" type="button"
                        aria-label="Admin menu"
                        style="display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-circle"></i>
                    </button>

                    <div id="admin-menu"
                        style="
                        position:absolute;
                        right:0;
                        top:calc(100% + 10px);
                        width:220px;
                        padding:12px;
                        border-radius:16px;
                        background:rgba(255,255,255,0.98);
                        border:1px solid rgba(148,163,184,0.35);
                        box-shadow:0 18px 40px rgba(15,23,42,0.14);
                        transform:translateY(-8px);
                        opacity:0;
                        pointer-events:none;
                        transition:all .18s ease;
                        z-index:50;
                     "
                        class="dark:!bg-slate-900 dark:!border-slate-700">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="
                            width:40px;height:40px;border-radius:14px;
                            background:rgba(107,159,212,0.14);
                            display:flex;align-items:center;justify-content:center;
                            font-weight:900;
                        ">
                                <?= htmlspecialchars(mb_substr($adminNama, 0, 1), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-weight:900;font-size:13px;line-height:1.1;color:inherit;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($adminNama, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="font-weight:700;font-size:12px;opacity:.7;line-height:1.1;">
                                    Administrator
                                </div>
                            </div>
                        </div>

                        <div style="height:10px;"></div>
                        <a href="<?= $base ?>?page=logout"
                            style="
                        display:flex;align-items:center;gap:10px;
                        padding:10px 12px;border-radius:14px;
                        text-decoration:none;
                        border:1px solid rgba(148,163,184,0.35);
                        color:inherit;
                       "
                            class="hover:bg-gray-50 dark:hover:bg-slate-800">
                            <i class="bi bi-box-arrow-right"></i>
                            <span style="font-weight:800;">Logout</span>
                        </a>
                    </div>
                </div>

            </div>
        </nav>

        <script>
            (function() {
                // ===== Theme (3-mode) =====
                const THEME_KEY = 'theme';
                const btn = document.getElementById('theme-toggle');
                const icon = document.getElementById('theme-icon');

                const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

                function applyTheme(theme) {
                    const prefersDark = mql ? mql.matches : false;
                    const shouldDark = (theme === 'dark') || (theme === 'system' && prefersDark);

                    document.documentElement.classList.toggle('dark', shouldDark);

                    if (!icon) return;
                    icon.className = 'bi ' + (
                        theme === 'system' ? 'bi-circle-half' :
                        theme === 'light' ? 'bi-sun' :
                        'bi-moon'
                    );
                }

                function getTheme() {
                    return localStorage.getItem(THEME_KEY) || 'system';
                }

                function setTheme(theme) {
                    localStorage.setItem(THEME_KEY, theme);
                    applyTheme(theme);
                }

                function handleSystemChange() {
                    if (getTheme() === 'system') applyTheme('system');
                }
                if (mql && mql.addEventListener) mql.addEventListener('change', handleSystemChange);
                else if (mql && mql.addListener) mql.addListener(handleSystemChange);

                applyTheme(getTheme());

                if (btn) {
                    btn.addEventListener('click', function() {
                        const cur = getTheme();
                        const next = (cur === 'system') ? 'light' : (cur === 'light') ? 'dark' : 'system';
                        setTheme(next);
                    });
                }

                // ===== Admin popup =====
                const adminBtn = document.getElementById('admin-menu-toggle');
                const adminMenu = document.getElementById('admin-menu');

                function openMenu() {
                    if (!adminMenu) return;
                    adminMenu.style.opacity = '1';
                    adminMenu.style.transform = 'translateY(0)';
                    adminMenu.style.pointerEvents = 'auto';
                }

                function closeMenu() {
                    if (!adminMenu) return;
                    adminMenu.style.opacity = '0';
                    adminMenu.style.transform = 'translateY(-8px)';
                    adminMenu.style.pointerEvents = 'none';
                }

                function isOpen() {
                    return adminMenu && adminMenu.style.pointerEvents === 'auto';
                }

                if (adminBtn && adminMenu) {
                    adminBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        isOpen() ? closeMenu() : openMenu();
                    });

                    adminMenu.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });

                    document.addEventListener('click', function() {
                        closeMenu();
                    });

                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') closeMenu();
                    });
                }
            })();
        </script>