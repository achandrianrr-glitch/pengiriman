<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/_report_helper.php';

// Dompdf autoload (aman jika projectmu sudah autoload vendor di index.php juga)
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Dompdf\Dompdf;
use Dompdf\Options;

if (!class_exists(Dompdf::class)) {
    echo "Library PDF belum terpasang. Jalankan: composer require dompdf/dompdf";
    exit;
}

$db = new Database();

$today = date('Y-m-d');
$startDefault = date('Y-m-01');

$start = $_GET['start'] ?? $startDefault;
$end   = $_GET['end']   ?? $today;
$q     = trim($_GET['q'] ?? '');

$dateColUsed = '';
$dateColOk = false;
$rows = laporan_fetch_rows($db, $start, $end, $q, $dateColUsed, $dateColOk);

$totalPendapatan = 0.0;
foreach ($rows as $r) {
    $totalPendapatan += (float)($r['total_biaya'] ?? 0);
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$html = '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    .title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
    .meta { margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .right { text-align: right; }
    .bold { font-weight: 700; }
</style>
</head>
<body>
<div class="title">Laporan Pengiriman</div>
<div class="meta">
    Periode: <b>' . e($start) . '</b> s/d <b>' . e($end) . '</b><br>
    ' . ($q !== '' ? ('Filter: <b>' . e($q) . '</b><br>') : '') . '
    Total Resi: <b>' . e(count($rows)) . '</b>
</div>

<table>
<thead>
<tr>
    <th style="width: 12%;">Tanggal</th>
    <th style="width: 14%;">No Resi</th>
    <th style="width: 14%;">Pengirim</th>
    <th style="width: 14%;">Penerima</th>
    <th style="width: 12%;">Asal</th>
    <th style="width: 12%;">Tujuan</th>
    <th style="width: 10%;">Layanan</th>
    <th style="width: 12%;" class="right">Total</th>
</tr>
</thead>
<tbody>
';

foreach ($rows as $r) {
    $tgl = $r['tanggal_view'] ?? ($r[$dateColUsed] ?? '');
    $asal = $r['pengirim_kota'] ?? ($r['kota_asal'] ?? '-');
    $tujuan = $r['penerima_kota'] ?? ($r['kota_tujuan'] ?? '-');
    $layanan = $r['jenis_layanan'] ?? ($r['layanan'] ?? '-');
    $total = (float)($r['total_biaya'] ?? 0);

    $html .= '
    <tr>
        <td>' . e($tgl ?: '—') . '</td>
        <td class="bold">' . e($r['nomor_resi'] ?? '—') . '</td>
        <td>' . e($r['pengirim_nama'] ?? '—') . '</td>
        <td>' . e($r['penerima_nama'] ?? '—') . '</td>
        <td>' . e($asal) . '</td>
        <td>' . e($tujuan) . '</td>
        <td>' . e($layanan) . '</td>
        <td class="right bold">' . e(function_exists('format_rupiah') ? format_rupiah($total) : ('Rp ' . number_format($total, 0, ',', '.'))) . '</td>
    </tr>';
}

$html .= '
</tbody>
<tfoot>
<tr>
    <td colspan="7" class="right bold">Total Pendapatan</td>
    <td class="right bold">' . e(function_exists('format_rupiah') ? format_rupiah($totalPendapatan) : ('Rp ' . number_format($totalPendapatan, 0, ',', '.'))) . '</td>
</tr>
</tfoot>
</table>

</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "Laporan_Pengiriman_{$start}_sd_{$end}.pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
