<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$db = new Database();
$resi = clean_input($_GET['resi'] ?? '');
if ($resi === '') die('Resi tidak valid.');

$data = $db->select("SELECT * FROM pengiriman WHERE nomor_resi = ? LIMIT 1", [$resi]);
if (!$data) die('Data tidak ditemukan.');
$p = $data[0];

// Render HTML dari print.php (tanpa auto print)
ob_start();
$_GET['preview'] = 1;
include __DIR__ . '/print.php';
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A6', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="resi-'.$p['nomor_resi'].'.pdf"');
echo $dompdf->output();
