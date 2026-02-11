<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();

function h($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rupiah_local($n)
{
  if (function_exists('format_rupiah')) return format_rupiah((float)$n);
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function tanggal_local($tgl)
{
  if (!$tgl) return '—';
  if (function_exists('format_tanggal')) return format_tanggal($tgl);
  return h($tgl);
}

/**
 * Cek apakah tabel tracking_pengiriman ada
 */
function tracking_table_exists($db)
{
  $cek = $db->select(
    "SELECT COUNT(*) AS c
     FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = ?",
    ['tracking_pengiriman'],
    "s"
  );
  return !empty($cek) && (int)$cek[0]['c'] > 0;
}

/**
 * Mapping status pengiriman -> index step
 */
function status_to_step($status)
{
  $s = strtolower(trim((string)$status));
  if ($s === '') return 0;

  if (str_contains($s, 'selesai') || str_contains($s, 'delivered') || str_contains($s, 'diterima')) return 5;
  if (str_contains($s, 'antar') || str_contains($s, 'delivery') || str_contains($s, 'dikirim ke penerima')) return 4;
  if (str_contains($s, 'tiba') || str_contains($s, 'tujuan') || str_contains($s, 'hub tujuan')) return 3;
  if (str_contains($s, 'perjalanan') || str_contains($s, 'transit') || str_contains($s, 'dalam perjalanan')) return 2;
  if (str_contains($s, 'pickup') || str_contains($s, 'kurir') || str_contains($s, 'diserahkan')) return 1;
  if (str_contains($s, 'proses') || str_contains($s, 'diproses') || str_contains($s, 'input')) return 0;

  return 0;
}

/**
 * Badge warna status
 */
function status_badge_class($status)
{
  $s = strtolower(trim((string)$status));
  return match (true) {
    str_contains($s, 'selesai'),
    str_contains($s, 'delivered'),
    str_contains($s, 'diterima') => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',

    str_contains($s, 'antar'),
    str_contains($s, 'delivery') => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200',

    str_contains($s, 'perjalanan'),
    str_contains($s, 'transit') => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',

    str_contains($s, 'proses'),
    str_contains($s, 'diproses') => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',

    default => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-200'
  };
}

$errors = [];
$resi = trim($_POST['resi'] ?? ($_GET['resi'] ?? ''));
$detail = null;
$events = [];
$useTrackingTable = tracking_table_exists($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $resi !== '') {
  if ($resi === '') {
    $errors[] = 'Nomor resi wajib diisi.';
  } else {
    $data = $db->select(
      "SELECT * FROM pengiriman WHERE nomor_resi = ? LIMIT 1",
      [$resi],
      "s"
    );

    if (!$data) {
      $errors[] = "Data pengiriman dengan resi <b>" . h($resi) . "</b> tidak ditemukan.";
    } else {
      $detail = $data[0];

      if ($useTrackingTable) {
        $events = $db->select(
          "SELECT status, lokasi, keterangan, waktu
           FROM tracking_pengiriman
           WHERE nomor_resi = ?
           ORDER BY waktu DESC",
          [$resi],
          "s"
        );
      }
    }
  }
}

$currentStatus = is_array($detail)
  ? ($detail['status_pengiriman'] ?? $detail['status'] ?? $detail['status_kirim'] ?? 'Diproses')
  : 'Diproses';

$asal   = is_array($detail) ? ($detail['pengirim_kota'] ?? ($detail['kota_asal'] ?? '—')) : '—';
$tujuan = is_array($detail) ? ($detail['penerima_kota'] ?? ($detail['kota_tujuan'] ?? '—')) : '—';
$layanan = is_array($detail) ? ($detail['jenis_layanan'] ?? ($detail['layanan'] ?? '—')) : '—';

$createdAt = is_array($detail) ? ($detail['created_at'] ?? ($detail['tanggal'] ?? ($detail['tgl_input'] ?? null))) : null;
$totalBiaya = is_array($detail) ? ($detail['total_biaya'] ?? 0) : 0;

$stepIndex = status_to_step($currentStatus);

$defaultSteps = [
  ['title' => 'Paket Diproses', 'desc' => 'Data pengiriman telah masuk sistem.'],
  ['title' => 'Diserahkan ke Kurir', 'desc' => 'Paket siap dijemput / diserahkan ke kurir.'],
  ['title' => 'Dalam Perjalanan', 'desc' => 'Paket sedang transit menuju kota tujuan.'],
  ['title' => 'Tiba di Kota Tujuan', 'desc' => 'Paket sudah sampai di hub kota tujuan.'],
  ['title' => 'Dalam Pengantaran', 'desc' => 'Kurir sedang mengantar paket ke penerima.'],
  ['title' => 'Selesai', 'desc' => 'Paket telah diterima penerima.'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
  /* Guard supaya isi halaman tidak "ketekan" layout sidebar / parent flex */
  .main-content {
    width: 100%;
    min-width: 0;
  }
</style>

<main class="main-content w-full min-w-0">
  <div class="w-full max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">
        Tracking Pengiriman
      </h1>
      <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
        Masukkan nomor resi untuk melihat status & riwayat pengiriman.
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        <div class="font-semibold mb-2">Terjadi kesalahan:</div>
        <ul class="list-disc pl-5 space-y-1">
          <?php foreach ($errors as $e): ?>
            <li><?= $e ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- GRID UTAMA: kiri (form+ringkasan), kanan (timeline+tips) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- KIRI -->
      <div class="lg:col-span-1 space-y-6 min-w-0">

        <!-- FORM -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 whitespace-nowrap">
            <i class="bi bi-geo-alt text-sky-500"></i>
            Cek Status
          </h2>

          <form method="POST" class="space-y-4" autocomplete="off">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                Nomor Resi
              </label>
              <input
                type="text"
                name="resi"
                id="resi"
                value="<?= h($resi) ?>"
                placeholder="contoh: SS-260211-0004"
                class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-3 py-3 focus:outline-none focus:ring-2 focus:ring-sky-500"
                required>
              <div class="text-xs text-gray-500 dark:text-gray-300 mt-1">
                Tips: copy nomor resi dari menu Resi/Data Pengiriman.
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <button type="submit"
                class="inline-flex w-full justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
                <i class="bi bi-search"></i>
                Tracking
              </button>

              <a href="?page=tracking"
                class="inline-flex w-full justify-center items-center gap-2 px-5 py-3 rounded-2xl font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                <i class="bi bi-arrow-clockwise"></i>
                Reset
              </a>
            </div>
          </form>
        </div>

        <!-- RINGKASAN -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 whitespace-nowrap">
            <i class="bi bi-info-circle text-sky-500"></i>
            Ringkasan
          </h2>

          <?php if (!$detail): ?>
            <div class="text-sm text-gray-600 dark:text-gray-300">
              Belum ada data. Silakan input nomor resi lalu klik <b>Tracking</b>.
            </div>
          <?php else: ?>
            <div class="space-y-3 text-sm">
              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Resi</span>
                <span class="font-semibold text-gray-900 dark:text-white text-right break-all">
                  <?= h($detail['nomor_resi'] ?? $resi) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Status</span>
                <span class="text-xs font-semibold px-3 py-1 rounded-full <?= status_badge_class($currentStatus) ?>">
                  <?= h($currentStatus) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Asal</span>
                <span class="font-semibold text-gray-900 dark:text-white text-right break-words">
                  <?= h($asal) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Tujuan</span>
                <span class="font-semibold text-gray-900 dark:text-white text-right break-words">
                  <?= h($tujuan) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Layanan</span>
                <span class="font-semibold text-gray-900 dark:text-white text-right break-words">
                  <?= h($layanan) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Total</span>
                <span class="font-extrabold text-sky-600 dark:text-sky-400">
                  <?= rupiah_local($totalBiaya) ?>
                </span>
              </div>

              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600 dark:text-gray-300">Tanggal</span>
                <span class="font-semibold text-gray-900 dark:text-white">
                  <?= tanggal_local($createdAt) ?>
                </span>
              </div>

              <div class="pt-2">
                <a href="?page=resi/detail&resi=<?= urlencode($detail['nomor_resi'] ?? $resi) ?>"
                  class="inline-flex w-full justify-center items-center gap-2 px-4 py-2 rounded-2xl font-semibold border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                  <i class="bi bi-receipt"></i>
                  Lihat Detail Resi
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- KANAN -->
      <div class="lg:col-span-2 space-y-6 min-w-0">

        <!-- TIMELINE -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 whitespace-nowrap">
            <i class="bi bi-clock-history text-sky-500"></i>
            Riwayat Tracking
          </h2>

          <?php if (!$detail): ?>
            <div class="text-sm text-gray-600 dark:text-gray-300">
              Riwayat tracking akan muncul setelah resi ditemukan.
            </div>
          <?php else: ?>

            <?php if ($useTrackingTable && !empty($events)): ?>
              <ol class="relative border-s border-gray-200 dark:border-slate-700 ms-3">
                <?php foreach ($events as $ev): ?>
                  <?php
                  $waktu  = $ev['waktu'] ?? null;
                  $status = $ev['status'] ?? 'Update';
                  $lokasi = $ev['lokasi'] ?? '';
                  $ket    = $ev['keterangan'] ?? '';
                  ?>
                  <li class="mb-8 ms-6">
                    <span class="absolute -start-3 flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/40">
                      <i class="bi bi-dot text-sky-600 dark:text-sky-300"></i>
                    </span>

                    <div class="flex items-center justify-between gap-3 flex-wrap">
                      <span class="text-xs font-semibold px-3 py-1 rounded-full <?= status_badge_class($status) ?>">
                        <?= h($status) ?>
                      </span>
                      <span class="text-xs text-gray-500 dark:text-gray-300">
                        <?= $waktu ? tanggal_local($waktu) : '—' ?>
                      </span>
                    </div>

                    <?php if ($lokasi !== ''): ?>
                      <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white break-words">
                        <?= h($lokasi) ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($ket !== ''): ?>
                      <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words">
                        <?= h($ket) ?>
                      </div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ol>

            <?php else: ?>
              <div class="text-xs text-gray-500 dark:text-gray-300 mb-4">
                <?php if (!$useTrackingTable): ?>
                  Catatan: tabel <b>tracking_pengiriman</b> belum ada, jadi sistem menampilkan timeline default berdasarkan status pengiriman.
                <?php else: ?>
                  Catatan: belum ada event tracking untuk resi ini, jadi sistem menampilkan timeline default.
                <?php endif; ?>
              </div>

              <ol class="space-y-5">
                <?php foreach ($defaultSteps as $idx => $st): ?>
                  <?php $done = $idx <= $stepIndex; ?>
                  <li class="flex gap-4">
                    <div class="flex flex-col items-center">
                      <div class="h-10 w-10 rounded-full flex items-center justify-center
                        <?= $done ? 'bg-sky-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-200' ?>">
                        <i class="bi <?= $done ? 'bi-check2' : 'bi-circle' ?>"></i>
                      </div>
                      <?php if ($idx < count($defaultSteps) - 1): ?>
                        <div class="w-px flex-1 mt-2 <?= $done ? 'bg-sky-300 dark:bg-sky-700' : 'bg-gray-200 dark:bg-slate-700' ?>"></div>
                      <?php endif; ?>
                    </div>

                    <div class="pt-1 min-w-0">
                      <div class="font-semibold text-gray-900 dark:text-white break-words">
                        <?= h($st['title']) ?>
                      </div>
                      <div class="text-sm text-gray-600 dark:text-gray-300 break-words">
                        <?= h($st['desc']) ?>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>

          <?php endif; ?>
        </div>

        <!-- TIPS -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2 whitespace-nowrap">
            <i class="bi bi-lightbulb text-sky-500"></i>
            Tips
          </h2>
          <ul class="text-sm text-gray-600 dark:text-gray-300 list-disc pl-5 space-y-1">
            <li>Pastikan nomor resi sesuai format yang dibuat sistem.</li>
            <li>Untuk tracking real-time, kamu bisa menambahkan tabel <b>tracking_pengiriman</b> (opsional).</li>
            <li>Menu <b>Lihat Detail Resi</b> menampilkan data lengkap pengiriman.</li>
          </ul>
        </div>

      </div>

    </div>
  </div>
</main>

<script>
  (function() {
    const resi = document.getElementById('resi');
    if (!resi) return;
    resi.addEventListener('input', function() {
      this.value = this.value.toUpperCase();
    });
  })();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>