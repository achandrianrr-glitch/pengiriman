<?php
require '../../config/database.php';
$db = new Database();

$resi = 'SS' . date('YmdHis');

$db->insert('pengiriman', [
  'nomor_resi' => $resi,
  'pengirim' => $_POST['pengirim'],
  'penerima' => $_POST['penerima'],
  'kota_tujuan' => $_POST['kota_tujuan'],
  'berat' => $_POST['berat'],
  'total_biaya' => $_POST['total_biaya'],
  'status' => 'Diproses'
]);

echo json_encode([
  'status' => 'success',
  'resi' => $resi
]);