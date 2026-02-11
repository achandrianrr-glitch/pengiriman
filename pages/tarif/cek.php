<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();

$errors = [];
$results = [];
$input = [
    'kota_asal'   => '',
    'kota_tujuan' => '',
    'berat'       => '',
    'panjang'     => '',
    'lebar'       => '',
    'tinggi'      => ''
];

// Ambil list kota (auto dropdown)
$kota_asal_list = $db->select("SELECT DISTINCT kota_asal FROM tarif_dasar WHERE status_aktif = 1 ORDER BY kota_asal ASC");
$kota_tujuan_list = $db->select("SELECT DISTINCT kota_tujuan FROM tarif_dasar WHERE status_aktif = 1 ORDER BY kota_tujuan ASC");

// Helper estimasi hari
function estimasi_hari($layanan)
{
    return match ($layanan) {
        'Regular'  => 3,
        'Express'  => 2,
        'Same Day' => 0,
        'Cargo'    => 4,
        default    => 0
    };
}

// Proses submit
$volume_cm3 = 0.0;
$berat_volumetrik = 0.0;
$berat_pakai = 0.0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['kota_asal']   = trim($_POST['kota_asal'] ?? '');
    $input['kota_tujuan'] = trim($_POST['kota_tujuan'] ?? '');
    $input['berat']       = trim($_POST['berat'] ?? '');

    $input['panjang']     = trim($_POST['panjang'] ?? '');
    $input['lebar']       = trim($_POST['lebar'] ?? '');
    $input['tinggi']      = trim($_POST['tinggi'] ?? '');

    if ($input['kota_asal'] === '')   $errors[] = 'Kota asal wajib dipilih.';
    if ($input['kota_tujuan'] === '') $errors[] = 'Kota tujuan wajib dipilih.';

    $berat = (float)$input['berat'];
    if ($berat <= 0) $errors[] = 'Berat harus lebih dari 0.';

    // Dimensi opsional: kalau salah satu diisi, semuanya wajib diisi
    $p = (float)$input['panjang'];
    $l = (float)$input['lebar'];
    $t = (float)$input['tinggi'];

    $ada_dimensi = ($input['panjang'] !== '' || $input['lebar'] !== '' || $input['tinggi'] !== '');

    if ($ada_dimensi) {
        if ($p <= 0 || $l <= 0 || $t <= 0) {
            $errors[] = 'Jika mengisi dimensi, Panjang, Lebar, dan Tinggi harus diisi dan lebih dari 0.';
        }
        if ($p < 0 || $l < 0 || $t < 0) {
            $errors[] = 'Dimensi tidak boleh negatif.';
        }
    } else {
        // kosong semua -> dianggap tidak pakai volumetrik
        $p = $l = $t = 0.0;
    }

    if (!$errors) {
        // Hitung volume cm3 & berat volumetrik
        $volume_cm3 = ($p > 0 && $l > 0 && $t > 0) ? ($p * $l * $t) : 0.0;
        $berat_volumetrik = $volume_cm3 > 0 ? ($volume_cm3 / 6000) : 0.0;

        // Berat dipakai = max(berat aktual, volumetrik)
        $berat_pakai = max($berat, $berat_volumetrik);

        $layanan_list = ['Regular', 'Express', 'Same Day', 'Cargo'];

        foreach ($layanan_list as $layanan) {
            $tarif = $db->select(
                "SELECT * FROM tarif_dasar
                 WHERE kota_asal = ? AND kota_tujuan = ? AND jenis_layanan = ? AND status_aktif = 1
                 LIMIT 1",
                [$input['kota_asal'], $input['kota_tujuan'], $layanan]
            );

            if (!$tarif) {
                $results[] = [
                    'layanan'     => $layanan,
                    'tersedia'    => false,
                    'berat_final' => round($berat_pakai, 2),
                    'biaya'       => 0,
                    'estimasi'    => null
                ];
                continue;
            }

            $tarif = $tarif[0];

            $minimal_kg    = (float)$tarif['minimal_kg'];
            $harga_per_kg  = (float)$tarif['harga_per_kg'];
            $biaya_minimum = (float)$tarif['biaya_minimum'];

            $berat_final = max($berat_pakai, $minimal_kg);

            // Hitung biaya
            if ($berat_final <= $minimal_kg) {
                $biaya = $biaya_minimum;
            } else {
                $biaya = $berat_final * $harga_per_kg;
                if ($biaya < $biaya_minimum) $biaya = $biaya_minimum;
            }

            $hari_tambah = estimasi_hari($layanan);
            $estimasi = date('Y-m-d', strtotime("+$hari_tambah day"));

            $results[] = [
                'layanan'     => $layanan,
                'tersedia'    => true,
                'berat_final' => round($berat_final, 2),
                'biaya'       => $biaya,
                'estimasi'    => $estimasi
            ];
        }
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cek Tarif Pengiriman</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Masukkan kota asal, kota tujuan, berat, dan dimensi (opsional) untuk melihat ongkir tiap layanan.
                </p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                <div class="font-semibold mb-2">Terjadi kesalahan:</div>
                <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="bi bi-calculator text-sky-500"></i>
                        Form Cek Tarif
                    </h2>

                    <form method="POST" class="space-y-4" autocomplete="off">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kota Asal</label>
                                <select name="kota_asal" id="kota_asal"
                                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                    required>
                                    <option value="">-- Pilih Kota Asal --</option>
                                    <?php foreach ($kota_asal_list as $k): ?>
                                        <?php $val = $k['kota_asal']; ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= ($input['kota_asal'] === $val) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($val) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kota Tujuan</label>
                                <select name="kota_tujuan" id="kota_tujuan"
                                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                    required>
                                    <option value="">-- Pilih Kota Tujuan --</option>
                                    <?php foreach ($kota_tujuan_list as $k): ?>
                                        <?php $val = $k['kota_tujuan']; ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= ($input['kota_tujuan'] === $val) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($val) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Berat (kg)</label>
                                <input type="number" step="0.01" min="0.01" name="berat" id="berat"
                                    value="<?= htmlspecialchars($input['berat']) ?>"
                                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                    placeholder="contoh: 3.5" required>
                                <div class="text-xs text-gray-500 dark:text-gray-300 mt-1">Berat aktual barang.</div>
                            </div>

                            <!-- Placeholder kolom biar sejajar -->
                            <div class="hidden md:block"></div>

                            <!-- DIMENSI (mengganti volume) -->
                            <div class="md:col-span-2">
                                <div class="rounded-2xl border border-gray-200 dark:border-slate-700 p-4 bg-gray-50 dark:bg-slate-900">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <i class="bi bi-box-seam text-sky-500"></i>
                                            Dimensi Barang (Opsional)
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-300">
                                            Volumetrik = (P×L×T)/6000
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Panjang (cm)</label>
                                            <input type="number" step="0.01" min="0" name="panjang" id="panjang"
                                                value="<?= htmlspecialchars($input['panjang']) ?>"
                                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                                placeholder="contoh: 30">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Lebar (cm)</label>
                                            <input type="number" step="0.01" min="0" name="lebar" id="lebar"
                                                value="<?= htmlspecialchars($input['lebar']) ?>"
                                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                                placeholder="contoh: 20">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Tinggi (cm)</label>
                                            <input type="number" step="0.01" min="0" name="tinggi" id="tinggi"
                                                value="<?= htmlspecialchars($input['tinggi']) ?>"
                                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                                placeholder="contoh: 15">
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500 dark:text-gray-300 mt-2">
                                        Jika mengisi salah satu dimensi, isi semua agar volume valid.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit"
                                class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
                                <i class="bi bi-search"></i>
                                Hitung Tarif
                            </button>

                            <a href="?page=tarif/cek"
                                class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <i class="bi bi-arrow-clockwise"></i>
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview perhitungan -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="bi bi-info-circle text-sky-500"></i>
                        Preview Perhitungan
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Berat Aktual</span>
                            <span class="font-semibold text-gray-900 dark:text-white" id="pv_berat">0 kg</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Volume</span>
                            <span class="font-semibold text-gray-900 dark:text-white" id="pv_volume">0 cm³</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Berat Volumetrik</span>
                            <span class="font-semibold text-gray-900 dark:text-white" id="pv_vol">0 kg</span>
                        </div>

                        <div class="h-px bg-gray-200 dark:bg-slate-700"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Berat Dipakai</span>
                            <span class="font-semibold text-sky-600 dark:text-sky-400" id="pv_final">0 kg</span>
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-300">
                            Berat dipakai = MAX(berat aktual, volumetrik).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hasil -->
        <div class="mt-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="bi bi-receipt text-sky-500"></i>
                    Hasil Tarif
                </h2>

                <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($results)): ?>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Silakan isi form lalu klik <b>Hitung Tarif</b> untuk melihat hasil.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($results as $r): ?>
                            <?php
                            $badge = match ($r['layanan']) {
                                'Regular'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
                                'Express'  => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
                                'Same Day' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200',
                                'Cargo'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
                                default    => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-200'
                            };
                            ?>
                            <div class="rounded-2xl border border-gray-200 dark:border-slate-700 p-5 bg-gray-50 dark:bg-slate-900">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $badge ?>">
                                        <?= htmlspecialchars($r['layanan']) ?>
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-300">
                                        <?= $r['tersedia'] ? 'Tersedia' : 'Tidak tersedia' ?>
                                    </span>
                                </div>

                                <div class="text-sm text-gray-600 dark:text-gray-300">Berat Final</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                    <?= number_format((float)$r['berat_final'], 2, ',', '.') ?> kg
                                </div>

                                <div class="text-sm text-gray-600 dark:text-gray-300">Ongkir</div>
                                <div class="text-xl font-extrabold <?= $r['tersedia'] ? 'text-sky-600 dark:text-sky-400' : 'text-gray-400' ?>">
                                    <?= $r['tersedia'] ? format_rupiah($r['biaya']) : '—' ?>
                                </div>

                                <div class="mt-3 text-xs text-gray-500 dark:text-gray-300">
                                    Estimasi: <?= $r['tersedia'] ? format_tanggal($r['estimasi']) : '—' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<script>
    (function() {
        const beratEl = document.getElementById('berat');
        const panjangEl = document.getElementById('panjang');
        const lebarEl = document.getElementById('lebar');
        const tinggiEl = document.getElementById('tinggi');

        const pvBerat = document.getElementById('pv_berat');
        const pvVolume = document.getElementById('pv_volume');
        const pvVol = document.getElementById('pv_vol');
        const pvFinal = document.getElementById('pv_final');

        function toNum(v) {
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function updatePreview() {
            const berat = toNum(beratEl?.value);

            const p = toNum(panjangEl?.value);
            const l = toNum(lebarEl?.value);
            const t = toNum(tinggiEl?.value);

            const volume = (p > 0 && l > 0 && t > 0) ? (p * l * t) : 0;
            const beratVol = volume > 0 ? (volume / 6000) : 0;
            const beratFinal = Math.max(berat, beratVol);

            if (pvBerat) pvBerat.textContent = berat.toFixed(2) + ' kg';
            if (pvVolume) pvVolume.textContent = Math.round(volume).toLocaleString('id-ID') + ' cm³';
            if (pvVol) pvVol.textContent = beratVol.toFixed(2) + ' kg';
            if (pvFinal) pvFinal.textContent = beratFinal.toFixed(2) + ' kg';
        }

        [beratEl, panjangEl, lebarEl, tinggiEl].forEach(el => {
            if (el) el.addEventListener('input', updatePreview);
        });

        updatePreview();
    })();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>