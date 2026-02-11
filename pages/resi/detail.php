<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();

// Ambil resi dari URL
$resi = isset($_GET['resi']) ? trim($_GET['resi']) : '';

if ($resi === '') {
    include __DIR__ . '/../../includes/header.php';
    include __DIR__ . '/../../includes/sidebar.php';
?>
    <main class="main-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                Nomor resi tidak ditemukan di URL.
            </div>
            <div class="mt-4">
                <a href="?page=resi" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                    ← Kembali
                </a>
            </div>
        </div>
    </main>
<?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Query detail pengiriman
$data = $db->select("SELECT * FROM pengiriman WHERE nomor_resi = ? LIMIT 1", [$resi]);

if (!$data || count($data) === 0) {
    include __DIR__ . '/../../includes/header.php';
    include __DIR__ . '/../../includes/sidebar.php';
?>
    <main class="main-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                Data pengiriman tidak ditemukan untuk resi: <b><?= htmlspecialchars($resi) ?></b>
            </div>
            <div class="mt-4">
                <a href="?page=resi" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                    ← Kembali
                </a>
            </div>
        </div>
    </main>
<?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

$detail = $data[0];

// Helper aman (hindari notice kalau kolom tidak ada)
$g = function (string $key, $default = '') use ($detail) {
    return isset($detail[$key]) ? $detail[$key] : $default;
};

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header halaman -->
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Detail Pengiriman</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Informasi lengkap pengiriman berdasarkan nomor resi.
                </p>
            </div>

            <a href="?page=resi"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                ← Kembali
            </a>
        </div>

        <!-- Layout utama -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Kolom utama -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Nomor Resi -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <label for="nomor_resi" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Nomor Resi
                    </label>
                    <input id="nomor_resi" type="text" readonly
                        value="<?= htmlspecialchars($g('nomor_resi')) ?>"
                        class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2 focus:outline-none" />
                </div>

                <!-- Data Pengirim -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">1. Data Pengirim</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nama Pengirim</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_nama')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Telepon</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_telepon')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Alamat Pengirim</label>
                            <textarea rows="3" readonly
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars($g('pengirim_alamat')) ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Provinsi</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_provinsi', $g('pengirim_kota'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kota/Kabupaten</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_kota', $g('pengirim_kecamatan'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kecamatan</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_kecamatan', $g('pengirim_kodepos'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kode Pos</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('pengirim_kodepos', $g('pengirim_kelurahan'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>
                    </div>
                </div>

                <!-- Data Penerima -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">2. Data Penerima</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nama Penerima</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_nama')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Telepon</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_telepon')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Alamat Penerima</label>
                            <textarea rows="3" readonly
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars($g('penerima_alamat')) ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Provinsi</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_provinsi', $g('penerima_kota'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kota/Kabupaten</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_kota', $g('penerima_kecamatan'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kecamatan</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_kecamatan', $g('penerima_kodepos'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kode Pos</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('penerima_kodepos', $g('penerima_kelurahan'))) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>
                    </div>
                </div>

                <!-- Detail Barang -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">3. Detail Barang</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Jenis Barang</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('barang_jenis')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Berat Aktual (kg)</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('barang_berat_kg')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Panjang (cm)</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('barang_panjang_cm')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Lebar (cm)</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('barang_lebar_cm')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Tinggi (cm)</label>
                            <input type="text" readonly
                                value="<?= htmlspecialchars($g('barang_tinggi_cm')) ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nilai Barang</label>
                            <input type="text" readonly
                                value="<?= 'Rp ' . number_format((float)$g('barang_nilai', 0), 0, ',', '.') ?>"
                                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2" />
                        </div>

                        <?php if ($g('barang_catatan') !== ''): ?>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Catatan</label>
                                <textarea rows="3" readonly
                                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/40 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars($g('barang_catatan')) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: Ringkasan -->
            <aside class="lg:col-span-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 sticky top-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Ringkasan</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-gray-300">Resi</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($g('nomor_resi')) ?></span>
                        </div>

                        <div class="h-px bg-gray-200 dark:bg-slate-700"></div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-gray-300">Pengirim</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($g('pengirim_nama')) ?></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-gray-300">Penerima</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($g('penerima_nama')) ?></span>
                        </div>

                        <div class="h-px bg-gray-200 dark:bg-slate-700"></div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-gray-300">Total Biaya</span>
                            <span class="font-extrabold text-sky-600 dark:text-sky-400">
                                <?= 'Rp ' . number_format((float)$g('total_biaya', 0), 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>