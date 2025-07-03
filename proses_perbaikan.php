<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Hanya user yang login via POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'user' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil semua data dari form
$id_user = $_SESSION['user_id'];
$nama_aset = trim($_POST['nama_aset']);
$merek = trim($_POST['merek']);
$tipe = trim($_POST['tipe']);
$serial_number = trim($_POST['serial_number']) ?: NULL; // Simpan NULL jika kosong
$jenis_kerusakan = trim($_POST['jenis_kerusakan']);
$deskripsi_kerusakan = trim($_POST['deskripsi_kerusakan']);

// Validasi dasar
if (empty($nama_aset) || empty($merek) || empty($tipe) || empty($jenis_kerusakan) || empty($deskripsi_kerusakan)) {
    // Gunakan session untuk pesan error agar lebih user-friendly
    $_SESSION['error_message'] = "Semua field (kecuali Serial Number) wajib diisi.";
    header('Location: perbaikan.php');
    exit;
}

// PERBAIKAN: Siapkan query INSERT dengan semua kolom baru
$stmt = $koneksi->prepare(
    "INSERT INTO perbaikan_aset 
    (id_user, nama_aset, merek, tipe, serial_number, jenis_kerusakan, deskripsi_kerusakan) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

// PERBAIKAN: Bind semua parameter baru (perhatikan tipe data 's' untuk string)
$stmt->bind_param("issssss", 
    $id_user, 
    $nama_aset, 
    $merek, 
    $tipe, 
    $serial_number, 
    $jenis_kerusakan, 
    $deskripsi_kerusakan
);

if ($stmt->execute()) {
    // Gunakan session untuk pesan sukses
    $_SESSION['success_message'] = "Laporan kerusakan berhasil dikirim dan akan segera diproses oleh admin.";
    header('Location: perbaikan.php');
    exit;
} else {
    // Jika gagal, tampilkan error dan arahkan kembali
    $_SESSION['error_message'] = "Gagal mengirim laporan. Error: " . $stmt->error;
    header('Location: perbaikan.php');
    exit;
}

$stmt->close();
$koneksi->close();
?>