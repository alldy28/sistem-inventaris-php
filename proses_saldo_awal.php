<?php
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses dan tombol sudah ditekan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['set_saldo_awal'])) {
    die("Akses ditolak.");
}

// Query untuk menyalin stok dan harga saat ini ke kolom saldo_awal dan harga_awal
$sql = "UPDATE produk SET stok_awal = stok, harga_awal = harga";

if ($koneksi->query($sql) === TRUE) {
    // Redirect kembali ke halaman penerimaan dengan notifikasi sukses
    header('Location: penerimaan.php?status=saldo_awal_sukses');
    exit;
} else {
    die("Gagal mengatur Saldo Awal. Error: " . $koneksi->error);
}
?>