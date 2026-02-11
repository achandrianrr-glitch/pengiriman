<?php
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'auth/check_auth.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';

$db = new Database();

function to_int($val)
{
    return (int)($val ?? 0);
}
function to_float($val)
{
    return (float)($val ?? 0);
}

function build_date_series($rows, $days, $keyDate, $keyValue)
{
    $map = [];
    foreach ($rows as $r) {
        $d = (string)$r[$keyDate];
        $map[$d] = (float)($r[$keyValue] ?? 0);
    }

    $labels = [];
    $data = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = $date;
        $data[] = isset($map[$date]) ? (float)$map[$date] : 0.0;
    }
    return [$labels, $data];
}

function status_badge_class($status)
{
    $s = strtolower(trim((string)$status));
    if ($s === 'diproses') return 'badge bg-yellow-100 text-yellow-700';
    if ($s === 'dalam perjalanan') return 'badge bg-blue-100 text-blue-700';
    if ($s === 'sampai tujuan') return 'badge bg-indigo-100 text-indigo-700';
    if ($s === 'selesai') return 'badge bg-green-100 text-green-700';
    if ($s === 'dibatalkan') return 'badge bg-red-100 text-red-700';
    if ($s === 'terlambat') return 'badge bg-red-200 text-red-900';
    return 'badge bg-gray-100 text-gray-700';
}

/* ===== STAT CARDS ===== */
$total_pengiriman = to_int(($db->select("SELECT COUNT(*) AS total FROM pengiriman")[0]['total'] ?? 0));

$bulan_lalu = to_int(($db->select("
    SELECT COUNT(*) AS total
    FROM pengiriman
    WHERE MONTH(tanggal_kirim) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
      AND YEAR(tanggal_kirim) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
")[0]['total'] ?? 0));

$persentase_pengiriman = 0.0;
if ($bulan_lalu > 0) {
    $persentase_pengiriman = (($total_pengiriman - $bulan_lalu) / $bulan_lalu) * 100;
}

$paket_diproses = to_int(($db->select("SELECT COUNT(*) AS total FROM pengiriman WHERE status='Diproses'")[0]['total'] ?? 0));
$diproses_hari_ini = to_int(($db->select("
    SELECT COUNT(*) AS total FROM pengiriman
    WHERE status='Diproses' AND DATE(tanggal_kirim)=CURDATE()
")[0]['total'] ?? 0));

$paket_perjalanan = to_int(($db->select("SELECT COUNT(*) AS total FROM pengiriman WHERE status='Dalam Perjalanan'")[0]['total'] ?? 0));

$total_pendapatan = to_float(($db->select("SELECT COALESCE(SUM(total_biaya),0) AS total FROM pengiriman")[0]['total'] ?? 0));

$pendapatan_bulan_lalu = to_float(($db->select("
    SELECT COALESCE(SUM(total_biaya),0) AS total
    FROM pengiriman
    WHERE MONTH(tanggal_kirim) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
      AND YEAR(tanggal_kirim) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
")[0]['total'] ?? 0));

$persentase_pendapatan = 0.0;
if ($pendapatan_bulan_lalu > 0) {
    $persentase_pendapatan = (($total_pendapatan - $pendapatan_bulan_lalu) / $pendapatan_bulan_lalu) * 100;
}

/* ===== CHART DATA ===== */
$rows_pendapatan = $db->select("
    SELECT DATE(tanggal_kirim) AS tanggal, COALESCE(SUM(total_biaya),0) AS total
    FROM pengiriman
    WHERE tanggal_kirim >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
    GROUP BY DATE(tanggal_kirim)
    ORDER BY tanggal ASC
");
list($revenue_labels_365, $revenue_data_365) = build_date_series($rows_pendapatan, 365, 'tanggal', 'total');

$rows_status = $db->select("
    SELECT status, COUNT(*) AS jumlah
    FROM pengiriman
    GROUP BY status
    ORDER BY jumlah DESC
");
$status_labels = array_map(fn($r) => (string)$r['status'], $rows_status);
$status_data = array_map(fn($r) => (int)$r['jumlah'], $rows_status);

$rows_transit_7 = $db->select("
    SELECT DATE(tanggal_kirim) AS tanggal, COUNT(*) AS total
    FROM pengiriman
    WHERE status='Dalam Perjalanan'
      AND tanggal_kirim >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(tanggal_kirim)
    ORDER BY tanggal ASC
");
list($transit_labels_7, $transit_data_7) = build_date_series($rows_transit_7, 7, 'tanggal', 'total');

/* ===== LATEST TABLE ===== */
$pengiriman_terbaru = $db->select("
    SELECT id, nomor_resi, penerima_nama, penerima_kota, status, tanggal_kirim, created_at
    FROM pengiriman
    ORDER BY created_at DESC
    LIMIT 10
");

$base = rtrim(BASE_URL, '/') . '/';
$page_title = 'Dashboard';

include ROOT_PATH . 'includes/header.php';
include ROOT_PATH . 'includes/sidebar.php';
?>

<main class="main-content">
    <div class="container">

        <div class="dashboard-header">
            <div>
                <h1>Dashboard</h1>
                <p>Ringkasan aktivitas pengiriman</p>
         </div>
      <a href="<?= BASE_URL ?>?page=pengiriman/create"
         class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold
                                   bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
        <i class="bi bi-plus-lg"></i> Input Pengiriman
      </a>
    </div>

        <!-- STAT GRID (4 kotak) -->
        <div class="stats-grid">
            <!-- Total Pengiriman -->
            <div class="stat-card">
                <div class="stat-left">
                    <p class="stat-label">Total Pengiriman</p>
                    <div class="stat-number" data-countup="<?= (int)$total_pengiriman ?>">0</div>
                    <div class="stat-meta <?= $persentase_pengiriman >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= $persentase_pengiriman >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <span><?= abs(round($persentase_pengiriman, 1)) ?>% vs bulan lalu</span>
                    </div>
                </div>
                <div class="stat-icon icon-blue">
                    <i class="bi bi-box-seam text-xl"></i>
                </div>
            </div>

            <!-- Paket Diproses -->
            <div class="stat-card">
                <div class="stat-left">
                    <p class="stat-label">Paket Diproses</p>
                    <div class="stat-number" data-countup="<?= (int)$paket_diproses ?>">0</div>
                    <div class="stat-meta">
                        <span class="badge" style="background: rgba(245,158,11,0.14); border-color: transparent; color:#F59E0B;">
                            Hari ini: <?= (int)$diproses_hari_ini ?>
                        </span>
                    </div>
                </div>
                <div class="stat-icon icon-yellow">
                    <i class="bi bi-clock text-xl"></i>
                </div>
            </div>

            <!-- Dalam Perjalanan -->
            <div class="stat-card">
                <div class="stat-left">
                    <p class="stat-label">Dalam Perjalanan</p>
                    <div class="stat-number" data-countup="<?= (int)$paket_perjalanan ?>">0</div>
                    <div class="stat-meta">
                        <span>Tren 7 hari</span>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <div class="stat-icon icon-blue">
                        <i class="bi bi-truck text-xl"></i>
                    </div>
                    <div class="mini-chart-wrap">
                        <canvas id="chartTransitMini"></canvas>
                    </div>
                </div>
            </div>

            <!-- Total Pendapatan -->
            <div class="stat-card">
                <div class="stat-left">
                    <p class="stat-label">Total Pendapatan</p>
                    <div class="stat-number currency" data-countup="<?= (float)$total_pendapatan ?>">Rp 0</div>
                    <div class="stat-meta <?= $persentase_pendapatan >= 0 ? 'positive' : 'negative' ?>">
                        <i class="bi bi-<?= $persentase_pendapatan >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <span><?= abs(round($persentase_pendapatan, 1)) ?>% vs bulan lalu</span>
                    </div>
                </div>
                <div class="stat-icon icon-green">
                    <i class="bi bi-cash-coin text-xl"></i>
                </div>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="charts-grid section-gap">
            <div class="card chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3>Pendapatan</h3>
                        <p>Grafik pendapatan berdasarkan tanggal kirim</p>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <select id="revenueFilter" class="select">
                            <option value="7">7 Hari</option>
                            <option value="30" selected>30 Hari</option>
                            <option value="90">3 Bulan</option>
                            <option value="365">1 Tahun</option>
                        </select>
                        <button type="button" id="downloadRevenuePng" 
                        class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold
                                   bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
                            <i class="bi bi-download"></i> PNG
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chartPendapatan"></canvas>
                </div>
            </div>

            <div class="card chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3>Status Pengiriman</h3>
                        <p>Distribusi status pengiriman</p>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card section-gap">
            <div class="chart-header">
                <div class="chart-title">
                    <h3>Pengiriman Terbaru</h3>
                    <p>10 pengiriman terbaru</p>
                </div>
                <a class="btn" href="<?= $base ?>?page=pengiriman/index" style="background:var(--bg-secondary); border-color:var(--border); color:var(--text-primary);">
                    <i class="bi bi-list-ul"></i> Lihat Semua
                </a>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Resi</th>
                            <th>Penerima</th>
                            <th>Tujuan</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pengiriman_terbaru)): ?>
                            <?php foreach ($pengiriman_terbaru as $i => $p): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <a href="<?= $base ?>?page=pengiriman/detail&id=<?= (int)$p['id'] ?>">
                                            <?= htmlspecialchars((string)$p['nomor_resi'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars((string)$p['penerima_nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$p['penerima_kota'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="<?= status_badge_class((string)$p['status']) ?>">
                                            <?= htmlspecialchars((string)$p['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(format_tanggal((string)($p['tanggal_kirim'] ?: $p['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="<?= $base ?>?page=pengiriman/detail&id=<?= (int)$p['id'] ?>" class="btn" style="padding:9px 12px;background:var(--bg-secondary);border-color:var(--border);color:var(--text-primary);">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:var(--text-secondary); font-weight:900; padding:18px 10px;">
                                    Belum ada data pengiriman
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- data ke chart -->
    <script>
        window.__dashboardData = {
            revenue: {
                labels365: <?= json_encode($revenue_labels_365, JSON_UNESCAPED_UNICODE) ?>,
                data365: <?= json_encode($revenue_data_365, JSON_UNESCAPED_UNICODE) ?>
            },
            status: {
                labels: <?= json_encode($status_labels, JSON_UNESCAPED_UNICODE) ?>,
                data: <?= json_encode($status_data, JSON_UNESCAPED_UNICODE) ?>
            },
            transitMini: {
                labels: <?= json_encode($transit_labels_7, JSON_UNESCAPED_UNICODE) ?>,
                data: <?= json_encode($transit_data_7, JSON_UNESCAPED_UNICODE) ?>
            }
        };
    </script>

    <script src="<?= $base ?>assets/js/countup.js"></script>
    <script src="<?= $base ?>assets/js/chart-config.js"></script>
</main>

<?php include ROOT_PATH . 'includes/footer.php'; ?>