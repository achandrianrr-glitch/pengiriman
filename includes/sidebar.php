<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
$base = rtrim(BASE_URL, '/') . '/';
$page = $_GET['page'] ?? 'dashboard';

function is_active($page, $target)
{
    return $page === $target;
}
function starts_with($page, $prefix)
{
    return strpos($page, $prefix) === 0;
}

/**
 * Logo: pakai logo SMKN 4 Malang jika ada,
 * fallback ke logo lama kalau file belum dipasang.
 */
$logoFsSmkn4 = __DIR__ . '/../assets/img/logo-grafika.png';
$logoUrlSmkn4 = $base . 'assets/img/logo-grafika.png';

$logoFsOld = __DIR__ . '/../assets/img/logo-grafika.png';
$logoUrlOld = $base . 'assets/img/logo-grafika.png';

$logoUrl = file_exists($logoFsSmkn4) ? $logoUrlSmkn4 : $logoUrlOld;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <!-- BRAND / LOGO -->
        <a href="<?= $base ?>?page=dashboard" aria-label="SoftSend - SMKN 4 Malang"
            style="display:flex; align-items:center; gap:12px; width:100%; text-decoration:none;">

            <!-- Logo container: besar tapi tetap hemat ruang -->
            <div style="
                width:58px; height:58px;
                flex:0 0 58px;
                border-radius:18px;
                display:flex; align-items:center; justify-content:center;
                background: rgba(255,255,255,.18);
                border: 1px solid rgba(255,255,255,.20);
                overflow:hidden;">

                <img
                    src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Logo SMKN 4 Malang"
                    width="50" height="50"
                    loading="eager" decoding="async"
                    style="
                        width:50px; height:50px;
                        object-fit:contain;
                        image-rendering:auto;
                        -webkit-transform: translateZ(0);
                        transform: translateZ(0);
                        filter: drop-shadow(0 2px 8px rgba(0,0,0,.08));
                    ">
            </div>

            <!-- Text: lebih rapi + font fallback bagus -->
            <div class="sidebar-title" style="line-height:1.05;">
                <h2 style="
                    margin:0;
                    font-size:18px;
                    font-weight:800;
                    letter-spacing:.2px;
                    color:#ffffff;
                    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Inter, Arial, 'Noto Sans', 'Liberation Sans', sans-serif;">
                    SoftSend
                </h2>
                <p style="
                    margin:6px 0 0;
                    font-size:12px;
                    font-weight:600;
                    color: rgba(255,255,255,.88);
                    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Inter, Arial, 'Noto Sans', 'Liberation Sans', sans-serif;">
                    SMKN 4 Malang •
                </p>
            </div>
        </a>
    </div>

    <div class="sidebar-nav">

        <a class="sidebar-menu-item <?= is_active($page, 'dashboard') ? 'active' : '' ?>" href="<?= $base ?>?page=dashboard">
            <span class="menu-icon"><i class="bi bi-speedometer2"></i></span>
            <span class="menu-label">Dashboard</span>
        </a>

        <a class="sidebar-menu-item <?= starts_with($page, 'pengiriman/') ? 'active' : '' ?>" href="<?= $base ?>?page=pengiriman/create">
            <span class="menu-icon"><i class="bi bi-box-seam"></i></span>
            <span class="menu-label">Input Pengiriman</span>
        </a>

        <a class="sidebar-menu-item <?= starts_with($page, 'tarif/') ? 'active' : '' ?>" href="<?= $base ?>?page=tarif/cek">
            <span class="menu-icon"><i class="bi bi-cash"></i></span>
            <span class="menu-label">Cek Tarif</span>
        </a>

        <a class="sidebar-menu-item <?= starts_with($page, 'tracking/') ? 'active' : '' ?>" href="<?= $base ?>?page=tracking/index">
            <span class="menu-icon"><i class="bi bi-truck"></i></span>
            <span class="menu-label">Tracking Pengiriman</span>
        </a>

        <a class="sidebar-menu-item <?= starts_with($page, 'resi') ? 'active' : '' ?>" href="<?= $base ?>?page=resi/preview">
            <span class="menu-icon"><i class="bi bi-receipt"></i></span>
            <span class="menu-label">Data Pengiriman</span>
        </a>

        <a class="sidebar-menu-item <?= starts_with($page, 'laporan/') ? 'active' : '' ?>" href="<?= $base ?>?page=laporan/index">
            <span class="menu-icon"><i class="bi bi-graph-up"></i></span>
            <span class="menu-label">Laporan</span>
        </a>

    </div>

    <div class="sidebar-footer">
        <div class="sidebar-meta">
            <div>Website V2</div>
            <div>v<?= htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <a class="sidebar-menu-item" href="<?= $base ?>?page=logout">
            <span class="menu-icon"><i class="bi bi-box-arrow-left"></i></span>
            <span class="menu-label">Logout</span>
        </a>
    </div>
</aside>