<?php
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses dan requestnya adalah POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Pastikan semua data POST yang dibutuhkan ada
if (!isset($_POST['id_produk'], $_POST['jumlah'], $_POST['harga_satuan'])) {
    die("Data tidak lengkap.");
}

$id_produk = $_POST['id_produk'];
$jumlah = $_POST['jumlah'];
$harga_satuan = $_POST['harga_satuan'];
$catatan = $_POST['catatan'] ?? '';

// Validasi dasar
if (empty($id_produk) || !is_numeric($jumlah) || $jumlah <= 0) {
    die("Data input tidak valid.");
}

// Mulai transaksi database untuk memastikan kedua query berhasil
$koneksi->begin_transaction();

try {
    // 1. Catat transaksi ke tabel `penerimaan`
    $stmt1 = $koneksi->prepare("INSERT INTO penerimaan (id_produk, jumlah, harga_satuan, catatan) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("iids", $id_produk, $jumlah, $harga_satuan, $catatan);
    $stmt1->execute();

    // 2. Update (tambah) stok di tabel `produk`
    // Selain stok, kita juga update harga satuan produk dengan harga terbaru
    $stmt2 = $koneksi->prepare("UPDATE produk SET stok = stok + ?, harga = ? WHERE id = ?");
    $stmt2->bind_param("idi", $jumlah, $harga_satuan, $id_produk);
    $stmt2->execute();

    // Jika semua berhasil, commit (simpan permanen) perubahan
    $koneksi->commit();

    // Redirect kembali ke halaman daftar penerimaan dengan notifikasi sukses
    header('Location: penerimaan.php?status=sukses');
    exit;

} catch (mysqli_sql_exception $exception) {
    // Jika ada error di salah satu query, batalkan semua perubahan
    $koneksi->rollback();
    
    // Tampilkan pesan error yang informatif
    die("Gagal mencatat penerimaan. Error: " . $exception->getMessage());
}
?>