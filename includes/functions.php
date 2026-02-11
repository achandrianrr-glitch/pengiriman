<?php

function format_rupiah($angka)
{
    $angka = $angka ?? 0;
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function format_tanggal($tanggal, $format = 'd F Y')
{
    if (!$tanggal) {
        return '-';
    }

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal);
    if (!$timestamp) {
        return '-';
    }

    $hari = date('d', $timestamp);
    $bulan_angka = (int)date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . $bulan[$bulan_angka] . ' ' . $tahun;
}

function format_datetime($datetime)
{
    if (!$datetime) {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '-';
    }
    return format_tanggal($datetime) . ', ' . date('H:i', $timestamp) . ' WIB';
}

function generate_nomor_resi($db)
{
    $tanggal = date('ymd'); // YYMMDD
    $prefix = 'SS-' . $tanggal . '-';

    $query = "SELECT nomor_resi FROM pengiriman
              WHERE nomor_resi LIKE ?
              ORDER BY id DESC LIMIT 1";
    $result = $db->select($query, [$prefix . '%']);

    if ($result && count($result) > 0 && !empty($result[0]['nomor_resi'])) {
        $last_resi = $result[0]['nomor_resi'];
        $last_number = (int)substr($last_resi, -4);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }

    return $prefix . str_pad((string)$new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Clean input - handles both string and array
 */
function clean_input($data)
{
    if (is_array($data)) {
        $cleaned = [];
        foreach ($data as $key => $value) {
            $cleaned[$key] = clean_input($value);
        }
        return $cleaned;
    }

    $data = trim((string)$data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validate_email($email)
{
    $email = (string)$email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (strpos($email, '@gmail.com') === false) {
        return false;
    }
    return true;
}

function validate_phone($phone)
{
    return (bool)preg_match('/^08[0-9]{8,11}$/', (string)$phone);
}

/**
 * Validate kode pos - must be 5 digits
 */
function validate_kodepos($kodepos)
{
    return (bool)preg_match('/^[0-9]{5}$/', (string)$kodepos);
}

function redirect($url, $message = '', $type = 'success')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message !== '') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    header('Location: ' . $url);
    exit;
}

function get_flash_message()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['flash_message'])) {
        $message = (string)$_SESSION['flash_message'];
        $type = isset($_SESSION['flash_type']) ? (string)$_SESSION['flash_type'] : 'info';

        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        return ['message' => $message, 'type' => $type];
    }

    return null;
}

function csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function hitung_berat_dimensional($panjang_cm, $lebar_cm, $tinggi_cm)
{
    $p = (float)$panjang_cm;
    $l = (float)$lebar_cm;
    $t = (float)$tinggi_cm;

    if ($p <= 0 || $l <= 0 || $t <= 0) return 0.0;

    $dim = ($p * $l * $t) / 6000;
    return round($dim, 2);
}

function hitung_estimasi_sampai($tanggal_kirim, $jenis_layanan)
{
    $tanggal_kirim = $tanggal_kirim ?: date('Y-m-d');

    $days = 0;
    if ($jenis_layanan === 'Regular') $days = 3;
    elseif ($jenis_layanan === 'Express') $days = 2;
    elseif ($jenis_layanan === 'Same Day') $days = 0;
    elseif ($jenis_layanan === 'Cargo') $days = 4;

    return date('Y-m-d', strtotime($tanggal_kirim . " +{$days} day"));
}

function hitung_biaya_pengiriman($db, $input)
{
    $kota_asal = trim((string)($input['kota_asal'] ?? ''));
    $kota_tujuan = trim((string)($input['kota_tujuan'] ?? ''));
    $jenis_layanan = trim((string)($input['jenis_layanan'] ?? ''));

    $berat_aktual = (float)($input['berat_kg'] ?? 0);
    $panjang = (float)($input['panjang_cm'] ?? 0);
    $lebar = (float)($input['lebar_cm'] ?? 0);
    $tinggi = (float)($input['tinggi_cm'] ?? 0);

    $asuransi = (bool)($input['asuransi'] ?? false);
    $packing = (bool)($input['packing'] ?? false);
    $nilai_barang = (float)($input['nilai_barang'] ?? 0);

    $biaya_tambahan = (float)($input['biaya_tambahan'] ?? 0);
    if ($biaya_tambahan < 0) $biaya_tambahan = 0;

    if ($kota_asal === '' || $kota_tujuan === '' || $jenis_layanan === '') {
        return ['ok' => false, 'message' => 'Kota asal/tujuan dan jenis layanan wajib diisi.'];
    }
    if ($berat_aktual <= 0) {
        return ['ok' => false, 'message' => 'Berat barang wajib lebih dari 0.'];
    }

    $berat_dim = hitung_berat_dimensional($panjang, $lebar, $tinggi);

    $berat_akhir = max($berat_aktual, $berat_dim);
    $berat_akhir = round($berat_akhir, 2);

    $tarif = $db->select(
        "SELECT harga_per_kg, minimal_kg, biaya_minimum 
         FROM tarif_dasar 
         WHERE kota_asal = ? AND kota_tujuan = ? AND jenis_layanan = ? AND status_aktif = 1
         LIMIT 1",
        [$kota_asal, $kota_tujuan, $jenis_layanan]
    );

    if (!$tarif || count($tarif) === 0) {
        return ['ok' => false, 'message' => 'Tarif untuk rute & layanan ini tidak ditemukan / nonaktif.'];
    }

    $harga_per_kg = (float)$tarif[0]['harga_per_kg'];
    $minimal_kg = (float)($tarif[0]['minimal_kg'] ?? 1.00);
    $biaya_minimum = (float)$tarif[0]['biaya_minimum'];

    if ($berat_akhir <= $minimal_kg) {
        $biaya_pengiriman = $biaya_minimum;
    } else {
        $biaya_pengiriman = $berat_akhir * $harga_per_kg;
        if ($biaya_pengiriman < $biaya_minimum) {
            $biaya_pengiriman = $biaya_minimum;
        }
    }
    $biaya_pengiriman = round($biaya_pengiriman, 2);

    $pengaturan = $db->select(
        "SELECT biaya_asuransi_persen, biaya_packing_kecil, biaya_packing_sedang, biaya_packing_besar
         FROM pengaturan
         ORDER BY id ASC LIMIT 1"
    );

    $asuransi_persen = 0.50;
    $packing_kecil = 5000;
    $packing_sedang = 15000;
    $packing_besar = 30000;

    if ($pengaturan && count($pengaturan) > 0) {
        $asuransi_persen = (float)$pengaturan[0]['biaya_asuransi_persen'];
        $packing_kecil = (float)$pengaturan[0]['biaya_packing_kecil'];
        $packing_sedang = (float)$pengaturan[0]['biaya_packing_sedang'];
        $packing_besar = (float)$pengaturan[0]['biaya_packing_besar'];
    }

    $biaya_asuransi = 0.0;
    if ($asuransi) {
        if ($nilai_barang <= 0) {
            return ['ok' => false, 'message' => 'Nilai barang wajib diisi jika asuransi dicentang.'];
        }
        $biaya_asuransi = $nilai_barang * ($asuransi_persen / 100);
        $biaya_asuransi = round($biaya_asuransi, 2);
    }

    $biaya_packing = 0.0;
    if ($packing) {
        if ($berat_akhir < 5) $biaya_packing = $packing_kecil;
        elseif ($berat_akhir >= 5 && $berat_akhir <= 15) $biaya_packing = $packing_sedang;
        else $biaya_packing = $packing_besar;
        $biaya_packing = round($biaya_packing, 2);
    }

    $total = $biaya_pengiriman + $biaya_asuransi + $biaya_packing + $biaya_tambahan;
    $total = round($total, 2);

    $estimasi = hitung_estimasi_sampai($input['tanggal_kirim'] ?? date('Y-m-d'), $jenis_layanan);

    return [
        'ok' => true,
        'message' => 'OK',
        'berat_dimensional' => $berat_dim,
        'berat_aktual' => round($berat_aktual, 2),
        'berat_akhir' => $berat_akhir,
        'biaya_pengiriman' => $biaya_pengiriman,
        'biaya_asuransi' => $biaya_asuransi,
        'biaya_packing' => $biaya_packing,
        'biaya_tambahan' => round($biaya_tambahan, 2),
        'total_biaya' => $total,
        'estimasi_sampai' => $estimasi
    ];
}