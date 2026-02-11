<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/_report_helper.php';

$db = new Database();

$today = date('Y-m-d');
$startDefault = date('Y-m-01');

$start = $_GET['start'] ?? $startDefault;
$end   = $_GET['end']   ?? $today;
$q     = trim($_GET['q'] ?? '');

$dateColUsed = '';
$dateColOk = false;

$rows = laporan_fetch_rows($db, $start, $end, $q, $dateColUsed, $dateColOk);

// Ringkasan
$totalPengiriman = count($rows);
$totalPendapatan = 0.0;

foreach ($rows as $r) {
    $totalPendapatan += (float)($r['total_biaya'] ?? 0);
}

$avg = $totalPengiriman > 0 ? ($totalPendapatan / $totalPengiriman) : 0;

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Pengiriman</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Filter laporan berdasarkan tanggal & kata kunci, lalu cetak ke PDF.
                </p>

                <?php if (!$dateColOk): ?>
                    <div class="mt-3 text-xs rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        Kolom tanggal tidak terdeteksi di tabel <b>pengiriman</b>. Filter tanggal tidak diterapkan.
                        Jika tabelmu punya kolom tanggal lain, ubah kandidat kolom di <code>_report_helper.php</code>.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-5">
                <div class="text-sm text-gray-600 dark:text-gray-300">Total Pengiriman</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                    <?= number_format($totalPengiriman, 0, ',', '.') ?>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-5">
                <div class="text-sm text-gray-600 dark:text-gray-300">Total Pendapatan</div>
                <div class="text-2xl font-extrabold text-sky-600 dark:text-sky-400 mt-1">
                    <?= function_exists('format_rupiah') ? format_rupiah($totalPendapatan) : ('Rp ' . number_format($totalPendapatan, 0, ',', '.')) ?>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-5">
                <div class="text-sm text-gray-600 dark:text-gray-300">Rata-rata / Resi</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                    <?= function_exists('format_rupiah') ? format_rupiah($avg) : ('Rp ' . number_format($avg, 0, ',', '.')) ?>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-5">
                <div class="text-sm text-gray-600 dark:text-gray-300">Rentang</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white mt-2">
                    <?= htmlspecialchars($start) ?> → <?= htmlspecialchars($end) ?>
                </div>
            </div>
        </div>

        <!-- Filter + Actions -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <input type="hidden" name="page" value="laporan">

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>"
                        class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>"
                        class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kata Kunci</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                        placeholder="Cari resi / pengirim / penerima..."
                        class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit"
                        class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-2xl text-white font-semibold bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95 w-full">
                        <i class="bi bi-filter"></i> Terapkan
                    </button>
                </div>

                <div class="md:col-span-12 flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="?page=laporan"
                        class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-2xl font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>

                    <a href="?page=laporan/export_pdf&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&q=<?= urlencode($q) ?>"
                        class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-2xl font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                        <i class="bi bi-filetype-pdf"></i> Cetak PDF
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="bi bi-table text-sky-500"></i>
                Data Pengiriman
            </h2>

            <?php if (empty($rows)): ?>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Tidak ada data pada filter yang dipilih.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr class="text-gray-700 dark:text-gray-200">
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">No Resi</th>
                                <th class="px-4 py-3 text-left font-semibold">Pengirim</th>
                                <th class="px-4 py-3 text-left font-semibold">Penerima</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Asal</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Tujuan</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Layanan</th>
                                <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">Total</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60 text-gray-900 dark:text-gray-100 bg-white dark:bg-slate-800">
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $tgl = $r['tanggal_view'] ?? ($dateColUsed ? ($r[$dateColUsed] ?? '') : '');
                                $asal = $r['pengirim_kota'] ?? ($r['kota_asal'] ?? '-');
                                $tujuan = $r['penerima_kota'] ?? ($r['kota_tujuan'] ?? '-');
                                $layanan = $r['jenis_layanan'] ?? ($r['layanan'] ?? '-');
                                $total = (float)($r['total_biaya'] ?? 0);
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/40 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?= $tgl ? (function_exists('format_tanggal') ? format_tanggal($tgl) : htmlspecialchars($tgl)) : '—' ?>
                                    </td>

                                    <td class="px-4 py-3 font-semibold whitespace-nowrap">
                                        <?= htmlspecialchars($r['nomor_resi'] ?? '—') ?>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="max-w-[220px] truncate" title="<?= htmlspecialchars($r['pengirim_nama'] ?? '—') ?>">
                                            <?= htmlspecialchars($r['pengirim_nama'] ?? '—') ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="max-w-[220px] truncate" title="<?= htmlspecialchars($r['penerima_nama'] ?? '—') ?>">
                                            <?= htmlspecialchars($r['penerima_nama'] ?? '—') ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?= htmlspecialchars($asal) ?>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?= htmlspecialchars($tujuan) ?>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?= htmlspecialchars($layanan) ?>
                                    </td>

                                    <td class="px-4 py-3 text-right font-extrabold text-sky-600 dark:text-sky-400 whitespace-nowrap">
                                        <?= function_exists('format_rupiah') ? format_rupiah($total) : ('Rp ' . number_format($total, 0, ',', '.')) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <tfoot class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">
                                    Total Pendapatan
                                </td>
                                <td class="px-4 py-3 text-right font-extrabold text-gray-900 dark:text-white whitespace-nowrap">
                                    <?= function_exists('format_rupiah') ? format_rupiah($totalPendapatan) : ('Rp ' . number_format($totalPendapatan, 0, ',', '.')) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>