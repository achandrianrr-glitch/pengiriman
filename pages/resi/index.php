<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();

$q = clean_input($_GET['q'] ?? '');
$params = [];
$sql = "SELECT id, nomor_resi, tanggal_kirim, status, penerima_nama, penerima_kota, total_biaya
        FROM pengiriman";

if ($q !== '') {
    $sql .= " WHERE nomor_resi LIKE ? OR penerima_nama LIKE ? OR penerima_kota LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}

$sql .= " ORDER BY id DESC LIMIT 50";
$rows = $db->select($sql, $params);

$pageTitle = "Resi Pengiriman - " . APP_NAME;
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Data Pengiriman</h1>
        <p class="text-slate-600 dark:text-slate-300">Kelola Data & Print Resi.</p>
      </div>
      <a href="<?= BASE_URL ?>?page=pengiriman/create"
         class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-2xl text-white font-semibold
                                   bg-gradient-to-r from-sky-500 to-pink-400 hover:opacity-95">
        <i class="bi bi-plus-lg"></i> Input Pengiriman
      </a>
    </div>

    <form class="mb-4 flex gap-2">
      <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari resi / penerima / kota..."
             class="w-full rounded-xl border px-3 py-2 bg-white dark:bg-slate-800 dark:border-slate-700">
      <button class="px-4 py-2 rounded-xl border bg-white dark:bg-slate-800 dark:border-slate-700">
        Cari
      </button>
    </form>

    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900/30">
          <tr class="text-left">
            <th class="p-3">Resi</th>
            <th class="p-3">Tanggal</th>
            <th class="p-3">Penerima</th>
            <th class="p-3">Tujuan</th>
            <th class="p-3">Status</th>
            <th class="p-3">Total</th>
            <th class="p-3">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td class="p-4" colspan="7">Belum ada data.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="border-t border-slate-200 dark:border-slate-700">
              <td class="p-3 font-semibold"><?= htmlspecialchars($r['nomor_resi']) ?></td>
              <td class="p-3"><?= format_tanggal($r['tanggal_kirim']) ?></td>
              <td class="p-3"><?= htmlspecialchars($r['penerima_nama']) ?></td>
              <td class="p-3"><?= htmlspecialchars($r['penerima_kota']) ?></td>
              <td class="p-3"><?= htmlspecialchars($r['status']) ?></td>
              <td class="p-3"><?= format_rupiah($r['total_biaya']) ?></td>
              <td class="p-3 flex gap-2 flex-wrap">
                
                <a href="index.php?page=resi/detail&resi=<?= urlencode($r['nomor_resi']) ?>"
     class="inline-flex items-center justify-center w-9 h-9 rounded
            bg-sky-100 text-sky-600 hover:bg-sky-200"
     title="Lihat Detail">
    <i class="bi bi-eye"></i>
  </a>

  <a class="inline-flex items-center justify-center w-9 h-9 rounded
            bg-purple-100 text-purple-600 hover:bg-purple-200"
                   href="<?= BASE_URL ?>?page=resi/preview&resi=<?= urlencode($r['nomor_resi']) ?>">
                 <i class="bi bi-printer"></i>
                </a>

  <button
    class="btn-hapus inline-flex items-center justify-center w-9 h-9 rounded
           bg-red-100 text-red-600 hover:bg-red-200"
    data-resi="<?= $r['nomor_resi'] ?>">
    <i class="bi bi-trash"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.querySelectorAll('.btn-hapus').forEach(btn => {
  btn.addEventListener('click', function () {

    const resi = this.dataset.resi;
    const row  = this.closest('tr');

    Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data pengiriman akan dihapus permanen',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then((result) => {

      if (result.isConfirmed) {

        fetch('index.php?page=resi/hapus', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'resi=' + encodeURIComponent(resi)
        })
        .then(res => res.json())
        .then(data => {

          if (data.status === 'success') {
            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
            row.remove();
          } else {
            Swal.fire('Gagal!', 'Data gagal dihapus.', 'error');
          }

        })
        .catch(() => {
          Swal.fire('Error!', 'Terjadi kesalahan server.', 'error');
        });

      }

    });

  });
});
</script>

              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
