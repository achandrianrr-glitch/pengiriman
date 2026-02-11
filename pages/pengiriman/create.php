<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$errors = [];

$kotaRows = $db->select("
    SELECT kota FROM (
        SELECT kota_asal AS kota FROM tarif_dasar WHERE status_aktif = 1
        UNION
        SELECT kota_tujuan AS kota FROM tarif_dasar WHERE status_aktif = 1
    ) t
    ORDER BY kota ASC
");

$kotaList = [];
if (!empty($kotaRows)) {
    foreach ($kotaRows as $r) {
        $kotaList[] = $r['kota'];
    }
}

$old = [
    'tanggal_kirim'      => date('Y-m-d'),
    'jenis_layanan'      => 'Regular',
    'metode_pembayaran'  => 'Tunai',
    'status_pembayaran'  => 'Belum Dibayar',
    'biaya_tambahan'     => 0,
];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = clean_input($_POST);

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'CSRF token tidak valid. Silakan refresh halaman.';
    }

    // Required fields sesuai schema
    $required = [
        'pengirim_nama','pengirim_telepon','pengirim_alamat','pengirim_kota','pengirim_kecamatan','pengirim_kelurahan','pengirim_kodepos',
        'penerima_nama','penerima_telepon','penerima_alamat','penerima_kota','penerima_kecamatan','penerima_kelurahan','penerima_kodepos',
        'barang_jenis','barang_berat_kg','jenis_layanan','metode_pembayaran','status_pembayaran','tanggal_kirim'
    ];

    foreach ($required as $field) {
        if (!isset($old[$field]) || trim((string)$old[$field]) === '') {
            $errors[$field] = 'Wajib diisi.';
        }
    }

    // Format validations
    if (empty($errors['pengirim_telepon']) && !validate_phone($old['pengirim_telepon'] ?? '')) {
        $errors['pengirim_telepon'] = 'Format telepon tidak valid (contoh: 08xxxxxxxxxx).';
    }
    if (empty($errors['penerima_telepon']) && !validate_phone($old['penerima_telepon'] ?? '')) {
        $errors['penerima_telepon'] = 'Format telepon tidak valid (contoh: 08xxxxxxxxxx).';
    }

    if (empty($errors['pengirim_kodepos']) && !validate_kodepos($old['pengirim_kodepos'] ?? '')) {
        $errors['pengirim_kodepos'] = 'Kode pos harus 5 digit.';
    }
    if (empty($errors['penerima_kodepos']) && !validate_kodepos($old['penerima_kodepos'] ?? '')) {
        $errors['penerima_kodepos'] = 'Kode pos harus 5 digit.';
    }

    $berat = (float)($old['barang_berat_kg'] ?? 0);
    if ($berat <= 0) {
        $errors['barang_berat_kg'] = 'Berat harus lebih dari 0.';
    }

    $asuransi = isset($old['asuransi']) && $old['asuransi'] === '1';
    $packing = isset($old['packing']) && $old['packing'] === '1';

    if ($asuransi) {
        $nilai = (float)($old['barang_nilai'] ?? 0);
        if ($nilai <= 0) $errors['barang_nilai'] = 'Nilai barang wajib diisi jika asuransi dicentang.';
    }

    // Jika valid, hitung ulang biaya server-side sesuai tarif_dasar + pengaturan
    if (count($errors) === 0) {
        $calcInput = [
            'kota_asal' => $old['pengirim_kota'],
            'kota_tujuan' => $old['penerima_kota'],
            'jenis_layanan' => $old['jenis_layanan'],
            'berat_kg' => (float)$old['barang_berat_kg'],
            'panjang_cm' => (float)($old['barang_panjang_cm'] ?? 0),
            'lebar_cm' => (float)($old['barang_lebar_cm'] ?? 0),
            'tinggi_cm' => (float)($old['barang_tinggi_cm'] ?? 0),
            'asuransi' => $asuransi,
            'packing' => $packing,
            'nilai_barang' => (float)($old['barang_nilai'] ?? 0),
            'biaya_tambahan' => (float)($old['biaya_tambahan'] ?? 0),
            'tanggal_kirim' => $old['tanggal_kirim'],
        ];

        $hasil = hitung_biaya_pengiriman($db, $calcInput);
        if (!$hasil['ok']) {
            $errors['general'] = $hasil['message'];
        } else {
            // Insert DB
            $nomor_resi = generate_nomor_resi($db);
            $admin_id = (int)($_SESSION['admin_id'] ?? 1);

            $tanggal_kirim = $old['tanggal_kirim'];
            $estimasi_sampai = $hasil['estimasi_sampai'];

            $status = 'Diproses';

            $insertId = $db->insert("
                INSERT INTO pengiriman (
                    nomor_resi, admin_id, tanggal_kirim, status,

                    pengirim_nama, pengirim_telepon, pengirim_alamat, pengirim_kota, pengirim_kecamatan, pengirim_kelurahan, pengirim_kodepos,
                    penerima_nama, penerima_telepon, penerima_alamat, penerima_kota, penerima_kecamatan, penerima_kelurahan, penerima_kodepos,

                    barang_jenis, barang_berat_kg, barang_panjang_cm, barang_lebar_cm, barang_tinggi_cm, barang_nilai, barang_catatan,

                    jenis_layanan,
                    biaya_pengiriman, biaya_asuransi, biaya_packing, biaya_tambahan, total_biaya,
                    metode_pembayaran, status_pembayaran,

                    estimasi_sampai
                ) VALUES (
                    ?, ?, ?, ?,

                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,

                    ?, ?, ?, ?, ?, ?, ?,

                    ?,
                    ?, ?, ?, ?, ?,
                    ?, ?,

                    ?
                )
            ", [
                $nomor_resi, $admin_id, $tanggal_kirim, $status,

                $old['pengirim_nama'], $old['pengirim_telepon'], $old['pengirim_alamat'], $old['pengirim_kota'], $old['pengirim_kecamatan'], $old['pengirim_kelurahan'], $old['pengirim_kodepos'],
                $old['penerima_nama'], $old['penerima_telepon'], $old['penerima_alamat'], $old['penerima_kota'], $old['penerima_kecamatan'], $old['penerima_kelurahan'], $old['penerima_kodepos'],

                $old['barang_jenis'], (float)$old['barang_berat_kg'],
                (float)($old['barang_panjang_cm'] ?? 0), (float)($old['barang_lebar_cm'] ?? 0), (float)($old['barang_tinggi_cm'] ?? 0),
                (float)($old['barang_nilai'] ?? 0),
                $old['barang_catatan'] ?? null,

                $old['jenis_layanan'],
                (float)$hasil['biaya_pengiriman'], (float)$hasil['biaya_asuransi'], (float)$hasil['biaya_packing'], (float)$hasil['biaya_tambahan'], (float)$hasil['total_biaya'],
                $old['metode_pembayaran'], $old['status_pembayaran'],

                $estimasi_sampai
            ]);

            if (!$insertId) {
                $errors['general'] = 'Gagal menyimpan data pengiriman.';
            } else {
                // Insert tracking awal
                $lokasi = $old['pengirim_kota'] . ' Hub SoftSend';
                $db->insert("
                    INSERT INTO tracking_history (pengiriman_id, status, lokasi, keterangan)
                    VALUES (?, ?, ?, ?)
                ", [
                    $insertId,
                    'Paket Diterima di Gudang',
                    $lokasi,
                    'Paket telah diterima dan siap diproses'
                ]);

                redirect(BASE_URL . '?page=resi/preview&resi='.$nomor_resi, 'Pengiriman berhasil disimpan. Resi: ' . $nomor_resi, 'success');
            }
        }
    }
}

$pageTitle = "Input Pengiriman - " . APP_NAME;

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-700 dark:text-slate-300">Input Pengiriman</h1>
                <p class="text-slate-600 dark:text-slate-300">Masukkan data pengiriman baru dan sistem akan menghitung ongkir otomatis.</p>
            </div>
            <a href="<?= BASE_URL ?>?page=dashboard"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:shadow-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="mb-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                <div class="font-semibold">Terjadi kesalahan</div>
                <div><?= $errors['general'] ?></div>
            </div>
        <?php endif; ?>

        <form id="formPengiriman" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= csrf_token(); ?>">

            <!-- LEFT: FORM -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Section 1: Pengirim -->
                <div class="w-full rounded-xl px-4 py-3
                bg-white dark:bg-slate-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-400
                border border-gray-300 dark:border-slate-600
                focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900 dark:text-slate-100">1. Data Pengirim</h2>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Nama Pengirim</label>
                            <input name="pengirim_nama" id="pengirim_nama" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['pengirim_nama'] ?? '' ?>">
                            <?php if (!empty($errors['pengirim_nama'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_nama'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Telepon *</label>
                            <input name="pengirim_telepon" id="pengirim_telepon" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['pengirim_telepon'] ?? '' ?>" placeholder="08xxxxxxxxxx">
                            <?php if (!empty($errors['pengirim_telepon'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_telepon'] ?></p><?php endif; ?>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-gray-700 dark:text-gray-200">Alamat Lengkap *</label>
                            <textarea name="pengirim_alamat" id="pengirim_alamat" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2"><?= $old['pengirim_alamat'] ?? '' ?></textarea>
                            <?php if (!empty($errors['pengirim_alamat'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_alamat'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Provinsi *</label>
                            <select name="pengirim_kota" id="kota_asal" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <option value="">-- Pilih Provinsi --</option>
                                <?php foreach ($kotaList as $k): ?>
                                    <option value="<?= htmlspecialchars($k) ?>" <?= (($old['pengirim_kota'] ?? '') === $k) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['pengirim_kota'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_kota'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kota/Kabupaten *</label>
                            <input name="pengirim_kecamatan" id="pengirim_kecamatan" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['pengirim_kecamatan'] ?? '' ?>">
                            <?php if (!empty($errors['pengirim_kecamatan'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_kecamatan'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kecamatan *</label>
                            <input name="pengirim_kelurahan" id="pengirim_kelurahan" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['pengirim_kelurahan'] ?? '' ?>">
                            <?php if (!empty($errors['pengirim_kelurahan'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_kelurahan'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kode Pos *</label>
                            <input name="pengirim_kodepos" id="pengirim_kodepos" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['pengirim_kodepos'] ?? '' ?>" placeholder="5 digit">
                            <?php if (!empty($errors['pengirim_kodepos'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['pengirim_kodepos'] ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Penerima -->
                <div class="w-full rounded-xl px-4 py-3
                bg-white dark:bg-slate-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-400
                border border-gray-300 dark:border-slate-600
                focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <h2 class="font-semibold text-slate-900 dark:text-slate-100">2. Data Penerima</h2>
                        <button type="button" id="btn_copy_pengirim"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:shadow-sm">
                            <i class="bi bi-files"></i> Salin dari Pengirim
                        </button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Nama Penerima *</label>
                            <input name="penerima_nama" id="penerima_nama" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['penerima_nama'] ?? '' ?>">
                            <?php if (!empty($errors['penerima_nama'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_nama'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Telepon *</label>
                            <input name="penerima_telepon" id="penerima_telepon" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['penerima_telepon'] ?? '' ?>" placeholder="08xxxxxxxxxx">
                            <?php if (!empty($errors['penerima_telepon'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_telepon'] ?></p><?php endif; ?>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-gray-700 dark:text-gray-200">Alamat Lengkap *</label>
                            <textarea name="penerima_alamat" id="penerima_alamat" rows="3" class="mt-1 w-full rounded-xl border px-3 py-2"><?= $old['penerima_alamat'] ?? '' ?></textarea>
                            <?php if (!empty($errors['penerima_alamat'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_alamat'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Provinsi *</label>
                            <select name="penerima_kota" id="kota_tujuan" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <option value="">-- Pilih Provinsi --</option>
                                <?php foreach ($kotaList as $k): ?>
                                    <option value="<?= htmlspecialchars($k) ?>" <?= (($old['penerima_kota'] ?? '') === $k) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['penerima_kota'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_kota'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kota/Kabupaten *</label>
                            <input name="penerima_kecamatan" id="penerima_kecamatan" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['penerima_kecamatan'] ?? '' ?>">
                            <?php if (!empty($errors['penerima_kecamatan'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_kecamatan'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kecamatan *</label>
                            <input name="penerima_kelurahan" id="penerima_kelurahan" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['penerima_kelurahan'] ?? '' ?>">
                            <?php if (!empty($errors['penerima_kelurahan'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_kelurahan'] ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Kode Pos *</label>
                            <input name="penerima_kodepos" id="penerima_kodepos" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['penerima_kodepos'] ?? '' ?>" placeholder="5 digit">
                            <?php if (!empty($errors['penerima_kodepos'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['penerima_kodepos'] ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Barang -->
                <div class="w-full rounded-xl px-4 py-3
                bg-white dark:bg-slate-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-400
                border border-gray-300 dark:border-slate-600
                focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <h2 class="font-semibold text-slate-900 dark:text-slate-100">3. Detail Barang</h2>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Jenis Barang *</label>
                            <select name="barang_jenis" id="barang_jenis" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <?php
                                $jenis = ['Elektronik','Pakaian','Makanan','Dokumen','Furniture','Kosmetik','Sparepart','Lainnya'];
                                $selectedJenis = $old['barang_jenis'] ?? '';
                                ?>
                                <option value="">-- Pilih Jenis --</option>
                                <?php foreach ($jenis as $j): ?>
                                    <option value="<?= $j ?>" <?= ($selectedJenis === $j) ? 'selected' : '' ?>><?= $j ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['barang_jenis'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['barang_jenis'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Berat Aktual (kg) *</label>
                            <input type="number" step="0.01" min="0.01" name="barang_berat_kg" id="barang_berat_kg"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['barang_berat_kg'] ?? '' ?>">
                            <?php if (!empty($errors['barang_berat_kg'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['barang_berat_kg'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Panjang (cm)</label>
                            <input type="number" step="0.01" min="0" name="barang_panjang_cm" id="barang_panjang_cm"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['barang_panjang_cm'] ?? '' ?>">
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Lebar (cm)</label>
                            <input type="number" step="0.01" min="0" name="barang_lebar_cm" id="barang_lebar_cm"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['barang_lebar_cm'] ?? '' ?>">
                        </div>
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Tinggi (cm)</label>
                            <input type="number" step="0.01" min="0" name="barang_tinggi_cm" id="barang_tinggi_cm"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['barang_tinggi_cm'] ?? '' ?>">
                        </div>

                        <div class="hidden" id="wrap_nilai_barang">
                            <label class="text-gray-700 dark:text-gray-200">Nilai Barang (Rp) *</label>
                            <input type="number" step="1" min="0" name="barang_nilai" id="barang_nilai"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['barang_nilai'] ?? '' ?>">
                            <?php if (!empty($errors['barang_nilai'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['barang_nilai'] ?></p><?php endif; ?>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-gray-700 dark:text-gray-200">Catatan</label>
                            <textarea name="barang_catatan" id="barang_catatan" rows="3"
                                      class="mt-1 w-full rounded-xl border px-3 py-2"><?= $old['barang_catatan'] ?? '' ?></textarea>
                        </div>

                        <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30">
                                <div class="text-gray-700 dark:text-gray-200" class="text-xs text-slate-500">Berat Dimensional</div>
                                <div class="text-lg font-semibold"><span id="out_berat_dim">0</span> kg</div>
                            </div>
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30">
                                <div class="text-gray-700 dark:text-gray-200" class="text-xs text-slate-500">Berat Akhir (Charge)</div>
                                <div class="text-lg font-semibold"><span id="out_berat_akhir">0</span> kg</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Layanan -->
                <div class="w-full rounded-xl px-4 py-3
                bg-white dark:bg-slate-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-400
                border border-gray-300 dark:border-slate-600
                focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <h2 class="font-semibold text-slate-900 dark:text-slate-100">4. Jenis Layanan</h2>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Jenis Layanan *</label>
                            <select name="jenis_layanan" id="jenis_layanan" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <?php
                                $layanan = ['Regular','Express','Same Day','Cargo'];
                                $selectedLayanan = $old['jenis_layanan'] ?? 'Regular';
                                ?>
                                <?php foreach ($layanan as $l): ?>
                                    <option value="<?= $l ?>" <?= ($selectedLayanan === $l) ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['jenis_layanan'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['jenis_layanan'] ?></p><?php endif; ?>
                        </div>

                        <div class="flex items-end gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" id="chk_asuransi" name="asuransi" value="1" <?= (!empty($old['asuransi']) && $old['asuransi'] === '1') ? 'checked' : '' ?>>
                                <span class="text-gray-700 dark:text-gray-200">Asuransi</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" id="chk_packing" name="packing" value="1" <?= (!empty($old['packing']) && $old['packing'] === '1') ? 'checked' : '' ?>>
                                <span class="text-gray-700 dark:text-gray-200">Packing</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Pembayaran & Estimasi -->
                <div class="w-full rounded-xl px-4 py-3
                bg-white dark:bg-slate-800
                text-gray-800 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-400
                border border-gray-300 dark:border-slate-600
                focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <h2 class="font-semibold text-slate-900 dark:text-slate-100">5. Pembayaran & Estimasi</h2>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Metode Pembayaran *</label>
                            <select name="metode_pembayaran" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <?php
                                $metode = ['Non COD','COD'];
                                $selMetode = $old['metode_pembayaran'] ?? 'Non COD';
                                foreach ($metode as $m):
                                ?>
                                    <option value="<?= $m ?>" <?= ($selMetode === $m) ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['metode_pembayaran'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['metode_pembayaran'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Status Pembayaran *</label>
                            <select name="status_pembayaran" class="mt-1 w-full rounded-xl border px-3 py-2">
                                <?php
                                $statusBayar = ['Belum Dibayar','Lunas'];
                                $selSB = $old['status_pembayaran'] ?? 'Belum Dibayar';
                                foreach ($statusBayar as $s):
                                ?>
                                    <option value="<?= $s ?>" <?= ($selSB === $s) ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['status_pembayaran'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['status_pembayaran'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Tanggal Kirim *</label>
                            <input type="date" name="tanggal_kirim" id="tanggal_kirim" class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['tanggal_kirim'] ?? date('Y-m-d') ?>">
                            <?php if (!empty($errors['tanggal_kirim'])): ?><p class="text-red-600 text-sm mt-1"><?= $errors['tanggal_kirim'] ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label class="text-gray-700 dark:text-gray-200">Estimasi Sampai</label>
                            <input type="date" name="estimasi_sampai" id="estimasi_sampai" class="mt-1 w-full rounded-xl border px-3 py-2 bg-slate-50 dark:bg-slate-900/30"
                                   value="<?= $old['estimasi_sampai'] ?? '' ?>" readonly>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-gray-700 dark:text-gray-200">Biaya Tambahan (Rp)</label>
                            <input type="number" step="1" min="0" name="biaya_tambahan" id="biaya_tambahan"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   value="<?= $old['biaya_tambahan'] ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" id="btn_submit"
                            class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold
                                   bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
                        <i class="bi bi-save"></i> Simpan & Print Resi
                    </button>

                    <button type="reset"
                            class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl font-semibold
                                   bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

            </div>

            <!-- RIGHT: STICKY BIAYA -->
            <aside class="lg:col-span-4">
                <div class="lg:sticky lg:top-24 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">Preview Biaya</h3>
                        <div id="calc_loader" class="hidden text-sm text-slate-500">Menghitung...</div>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Biaya Pengiriman</span>
                            <span class="text-gray-900 dark:text-white font-semibold" id="out_biaya_pengiriman"><?= format_rupiah(0) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Biaya Asuransi</span>
                            <span class="text-gray-900 dark:text-white font-semibold" id="out_biaya_asuransi"><?= format_rupiah(0) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Biaya Packing</span>
                            <span class="text-gray-900 dark:text-white font-semibold" id="out_biaya_packing"><?= format_rupiah(0) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Biaya Tambahan</span>
                            <span class="text-gray-900 dark:text-white font-semibold" id="out_biaya_tambahan"><?= format_rupiah((float)($old['biaya_tambahan'] ?? 0)) ?></span>
                        </div>

                        <hr class="border-slate-200 dark:border-slate-700">

                        <div class="flex items-center justify-between">
                            <span class="text-slate-900 dark:text-slate-100 font-semibold">TOTAL</span>
                            <span class="text-xl font-bold text-slate-900 dark:text-white" id="out_total_biaya"><?= format_rupiah(0) ?></span>
                        </div>

                        <p id="calc_message" class="text-slate-600 dark:text-slate-300"></p>
                    </div>

                    <!-- Hidden fields (untuk disimpan, tetap dihitung ulang server-side) -->
                    <input type="hidden" name="hidden_biaya_pengiriman" id="hidden_biaya_pengiriman" value="0">
                    <input type="hidden" name="hidden_biaya_asuransi" id="hidden_biaya_asuransi" value="0">
                    <input type="hidden" name="hidden_biaya_packing" id="hidden_biaya_packing" value="0">
                    <input type="hidden" name="hidden_total_biaya" id="hidden_total_biaya" value="0">

                </div>
            </aside>
        </form>
    </div>
</main>

<script src="<?= BASE_URL ?>assets/js/pengiriman-create.js" defer></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
