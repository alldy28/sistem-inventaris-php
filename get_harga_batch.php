<?php
header('Content-Type: application/json');
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$id_produk = $_GET['id'];
$response_data = [
    'harga_saat_ini' => 0,
    'data_batch' => []
];

// 1. Ambil harga produk saat ini dari tabel produk
$stmt_produk = $koneksi->prepare("SELECT harga FROM produk WHERE id = ?");
$stmt_produk->bind_param("i", $id_produk);
$stmt_produk->execute();
$produk_result = $stmt_produk->get_result();
if ($produk_row = $produk_result->fetch_assoc()) {
    $response_data['harga_saat_ini'] = (float) $produk_row['harga'];
}
$stmt_produk->close();

// 2. Ambil semua batch yang masih memiliki sisa stok
$stmt_batch = $koneksi->prepare("
    SELECT sisa_stok, harga_beli, tanggal_masuk 
    FROM stok_batch 
    WHERE id_produk = ? AND sisa_stok > 0 
    ORDER BY tanggal_masuk ASC
");
$stmt_batch->bind_param("i", $id_produk);
$stmt_batch->execute();
$result_batch = $stmt_batch->get_result();

while ($row = $result_batch->fetch_assoc()) {
    $response_data['data_batch'][] = $row;
}

$stmt_batch->close();
$koneksi->close();

// Kirim data gabungan dalam format JSON
echo json_encode($response_data);
?>