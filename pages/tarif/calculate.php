<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'message' => 'Method tidak valid.']);
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        echo json_encode(['ok' => false, 'message' => 'CSRF token tidak valid.']);
        exit;
    }

    $db = new Database();

    $input = [
        'kota_asal' => clean_input($_POST['kota_asal'] ?? ''),
        'kota_tujuan' => clean_input($_POST['kota_tujuan'] ?? ''),
        'jenis_layanan' => clean_input($_POST['jenis_layanan'] ?? ''),
        'berat_kg' => (float)($_POST['berat_kg'] ?? 0),
        'panjang_cm' => (float)($_POST['panjang_cm'] ?? 0),
        'lebar_cm' => (float)($_POST['lebar_cm'] ?? 0),
        'tinggi_cm' => (float)($_POST['tinggi_cm'] ?? 0),
        'asuransi' => (($_POST['asuransi'] ?? '0') === '1'),
        'packing' => (($_POST['packing'] ?? '0') === '1'),
        'nilai_barang' => (float)($_POST['nilai_barang'] ?? 0),
        'biaya_tambahan' => (float)($_POST['biaya_tambahan'] ?? 0),
        'tanggal_kirim' => clean_input($_POST['tanggal_kirim'] ?? date('Y-m-d')),
    ];

    $hasil = hitung_biaya_pengiriman($db, $input);
    echo json_encode($hasil);
    exit;

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Terjadi error: ' . $e->getMessage()]);
    exit;
}
