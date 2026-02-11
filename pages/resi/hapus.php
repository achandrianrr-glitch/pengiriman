<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_POST['resi'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$resi = $_POST['resi'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("DELETE FROM pengiriman WHERE nomor_resi = ?");
    $stmt->bind_param("s", $resi);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error']);
}
