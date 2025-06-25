<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['set_saldo_awal'])) {
    die("Akses ditolak.");
}

// Cek apakah saldo awal sudah pernah di-set sebelumnya (untuk mencegah penggunaan berulang)
$stmt_check = $koneksi->prepare("SELECT SUM(stok_awal) as total_stok_awal FROM produk");
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();

if ($result_check['total_stok_awal'] > 0) {
    // Jika total stok awal lebih dari 0, berarti sudah pernah di-set. Hentikan proses.
    header('Location: penerimaan.php?status=saldo_awal_gagal');
    exit;
}

// Jika belum pernah di-set, lanjutkan proses
$sql = "UPDATE produk SET stok_awal = stok, harga_awal = harga";

if ($koneksi->query($sql) === TRUE) {
    header('Location: penerimaan.php?status=saldo_awal_sukses');
    exit;
} else {
    die("Gagal mengatur Saldo Awal. Error: " . $koneksi->error);
}
?>