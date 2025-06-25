<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Hanya user yang login via POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'user' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil data dari form
$id_user = $_SESSION['user_id'];
$nama_aset = $_POST['nama_aset'];
$komponen_rusak = $_POST['komponen_rusak'];
$deskripsi_kerusakan = $_POST['deskripsi_kerusakan'];

// Validasi dasar
if (empty($nama_aset) || empty($komponen_rusak) || empty($deskripsi_kerusakan)) {
    die("Semua field wajib diisi.");
}

// Siapkan dan jalankan query INSERT
$stmt = $koneksi->prepare("INSERT INTO perbaikan_aset (id_user, nama_aset, komponen_rusak, deskripsi_kerusakan) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $id_user, $nama_aset, $komponen_rusak, $deskripsi_kerusakan);

if ($stmt->execute()) {
    // Jika berhasil, arahkan kembali ke halaman perbaikan dengan notifikasi sukses
    header('Location: perbaikan.php?status=sukses');
    exit;
} else {
    die("Gagal mengirim laporan. Error: " . $stmt->error);
}

$stmt->close();
$koneksi->close();
?>