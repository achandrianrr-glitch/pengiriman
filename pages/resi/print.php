<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$resi = clean_input($_GET['resi'] ?? '');

if ($resi === '') die('Resi tidak valid.');

$data = $db->select("SELECT * FROM pengiriman WHERE nomor_resi = ? LIMIT 1", [$resi]);
if (!$data) die('Data tidak ditemukan.');

$p = $data[0];

// isi QR: arahkan ke tracking page (kalau belum ada, tetap bisa scan untuk lihat URL)
$qrValue = BASE_URL . '?page=tracking&resi=' . urlencode($p['nomor_resi']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resi <?= htmlspecialchars($p['nomor_resi']) ?></title>

  <style>
    .scan-box {
  border: 1px dashed #111;
  border-radius: 10px;
  padding: 10px;
  margin: 10px 0;
}

.scan-title {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 8px;
}

.scan-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 10px;
  align-items: stretch;
  min-height: 160px;
}

.scan-item {
  text-align: center;
}

.qrcode-wrap {
  height: 100%;
}

#qrcode {
  width: 100% !important;
  height: 100% !important;
  display: block;
}

.barcode-wrap {
  height: 100%;
}

#barcode {
  width: 100% !important;
  height: 100% !important;
  display: block;
}

.scan-label {
  font-size: 10px;
  margin-top: 4px;
}
    /* ==== PRINT SETTINGS ==== */
    @page { size: A6; margin: 6mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: #111; }
    .no-print { display: none; }
    .sheet { width: 100%; }

    /* ==== LAYOUT ==== */
    .card {
      border: 1px solid #111;
      border-radius: 10px;
      padding: 10px;
    }
    .row { display: flex; gap: 10px; }
    .col { flex: 1; }
    .muted { color: #444; font-size: 11px; }
    .bold { font-weight: 700; }
    .h1 { font-size: 16px; font-weight: 800; }
    .h2 { font-size: 13px; font-weight: 800; }

    .topbar {
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid #111; padding-bottom: 8px; margin-bottom: 8px;
    }
    .brand { display: flex; flex-direction: column; line-height: 1.1; }
    .brand small { font-size: 11px; }

    .box {
      border: 1px solid #111; border-radius: 8px;
      padding: 8px; min-height: 64px;
    }
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    .metaGrid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 10px;
    }
    .metaItem {
      border: 1px dashed #111;
      border-radius: 8px;
      padding: 6px;
      font-size: 11px;
    }
    .metaItem b { font-size: 12px; }

    .footer {
      margin-top: 10px;
      border-top: 1px solid #111;
      padding-top: 8px;
      font-size: 10px;
      display: flex; justify-content: space-between; gap: 10px;
    }

    /* preview mode: tampilkan tombol */
    <?php if (!empty($_GET['preview'])): ?>
    .no-print { display: flex; gap: 8px; padding: 10px; }
    @page { margin: 10mm; }
    body { background: #f5f5f5; }
    .sheet { max-width: 520px; margin: 10px auto; background: #fff; padding: 10px; border-radius: 12px; }
    <?php endif; ?>

    @media print {
      .no-print { display: none !important; }
      body { background: #fff; }
      .sheet { margin: 0; padding: 0; }
    }
  </style>
</head>

<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:10px 14px;border-radius:10px;border:1px solid #111;background:#111;color:#fff;">
      Print / Save PDF
    </button>
  </div>

  <div class="sheet">
    <div class="card">
      <!-- TOP -->
      <div class="topbar">
        <div class="brand">
          <div class="h1"><?= htmlspecialchars(APP_NAME) ?></div>
          <small class="muted">Resi Pengiriman</small>
        </div>
        <div class="codeWrap">
          <div class="h2"><?= htmlspecialchars($p['nomor_resi']) ?></div>
          <div class="muted">Kirim: <?= htmlspecialchars($p['tanggal_kirim']) ?></div>
        </div>
      </div>

      <div class="scan-box">
  <div class="scan-title">
    Scan barcode / QR untuk tracking
  </div>

  <div class="scan-grid">
    <div class="scan-item">
      <div id="qrcode"></div>
      <div class="scan-label"></div>
    </div>

    <div class="scan-item">
      <svg id="barcode"></svg>
      <div class="scan-label"></div>
    </div>
  </div>
</div>

      <!-- SENDER / RECEIVER -->
      <div class="grid2" style="margin-top: 10px;">
        <div class="box">
          <div class="bold" style="font-size:12px;">PENGIRIM</div>
          <div style="font-size:12px;margin-top:2px;">
            <?= htmlspecialchars($p['pengirim_nama']) ?> (<?= htmlspecialchars($p['pengirim_telepon']) ?>)
          </div>
          <div class="muted" style="margin-top:2px;">
            <?= htmlspecialchars($p['pengirim_alamat']) ?><br>
            <?= htmlspecialchars($p['pengirim_kelurahan']) ?>, <?= htmlspecialchars($p['pengirim_kecamatan']) ?><br>
            <?= htmlspecialchars($p['pengirim_kota']) ?> - <?= htmlspecialchars($p['pengirim_kodepos']) ?>
          </div>
        </div>

        <div class="box">
          <div class="bold" style="font-size:12px;">PENERIMA</div>
          <div style="font-size:12px;margin-top:2px;">
            <?= htmlspecialchars($p['penerima_nama']) ?> (<?= htmlspecialchars($p['penerima_telepon']) ?>)
          </div>
          <div class="muted" style="margin-top:2px;">
            <?= htmlspecialchars($p['penerima_alamat']) ?><br>
            <?= htmlspecialchars($p['penerima_kelurahan']) ?>, <?= htmlspecialchars($p['penerima_kecamatan']) ?><br>
            <?= htmlspecialchars($p['penerima_kota']) ?> - <?= htmlspecialchars($p['penerima_kodepos']) ?>
          </div>
        </div>
      </div>

      <!-- META -->
      <div class="metaGrid">
        <div class="metaItem">
          <div class="muted">Layanan</div>
          <b><?= htmlspecialchars($p['jenis_layanan']) ?></b>
        </div>
        <div class="metaItem">
          <div class="muted">Status</div>
          <b><?= htmlspecialchars($p['status']) ?></b>
        </div>
        <div class="metaItem">
          <div class="muted">Barang</div>
          <b><?= htmlspecialchars($p['barang_jenis']) ?></b>
          <div class="muted">Berat: <?= htmlspecialchars($p['barang_berat_kg']) ?> kg</div>
        </div>
        <div class="metaItem">
          <div class="muted">Pembayaran</div>
          <b><?= htmlspecialchars($p['metode_pembayaran']) ?></b>
          <div class="muted"><?= htmlspecialchars($p['status_pembayaran']) ?></div>
        </div>
      </div>

      <div class="footer">
        <div>
          <div class="muted">Estimasi sampai</div>
          <div class="bold"><?= htmlspecialchars($p['estimasi_sampai'] ?? '-') ?></div>
        </div>
        <div style="text-align:right;">
          <div class="muted">Total</div>
          <div class="bold"><?= format_rupiah($p['total_biaya']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- BARCODE -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<!-- QR CODE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

  // BARCODE
  JsBarcode("#barcode", "<?= addslashes($p['nomor_resi']) ?>", {
  format: "CODE128",
  width: 3,
  height: 140,
  displayValue: true,
  fontSize: 16,
  margin: 0,
  textMargin: 6
});

  // QR CODE
  new QRCode(document.getElementById("qrcode"), {
    text: "<?= BASE_URL ?>?page=tracking&resi=<?= addslashes($p['nomor_resi']) ?>",
    width: 120,
    height: 120,
    correctLevel: QRCode.CorrectLevel.M
  });

});
</script>
</body>
</html>
