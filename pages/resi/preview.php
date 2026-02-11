<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$resi = clean_input($_GET['resi'] ?? '');

if ($resi === '') redirect(BASE_URL . '?page=resi', 'Resi tidak valid.', 'error');

$data = $db->select("SELECT * FROM pengiriman WHERE nomor_resi = ? LIMIT 1", [$resi]);
if (!$data) redirect(BASE_URL . '?page=resi', 'Data resi tidak ditemukan.', 'error');

$p = $data[0];

$pageTitle = "Preview Resi - " . APP_NAME;
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Preview Resi</h1>
        <p class="text-slate-600 dark:text-slate-300">Resi: <b><?= htmlspecialchars($p['nomor_resi']) ?></b></p>
      </div>
      <div class="flex gap-2">
        <a href="<?= BASE_URL ?>?page=resi"
           class="px-4 py-2 rounded-xl border bg-white dark:bg-slate-800 dark:border-slate-700">Kembali</a>
      </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden">
      <iframe
        src="<?= BASE_URL ?>?page=resi/print&resi=<?= urlencode($p['nomor_resi']) ?>&preview=1"
        class="w-full"
        style="height: 80vh; border: 0;"
      ></iframe>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
