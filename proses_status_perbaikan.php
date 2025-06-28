<?php
require_once 'koneksi.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['update_status'])) {
    die("Akses ditolak.");
}

$id_perbaikan = $_POST['id_perbaikan'];
$status_baru = $_POST['status_perbaikan'];
$catatan_admin = $_POST['catatan_admin'];

// File ini hanya boleh mengubah ke status selain 'Selesai'
if ($status_baru == 'Selesai') {
    die("Gunakan tombol 'Selesaikan & Catat Penggunaan Komponen' untuk menyelesaikan perbaikan.");
}

$tanggal_selesai = ($status_baru == 'Ditolak') ? date('Y-m-d H:i:s') : null;

$stmt = $koneksi->prepare("UPDATE perbaikan_aset SET status_perbaikan = ?, catatan_admin = ?, tanggal_selesai = ? WHERE id = ?");
$stmt->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_selesai, $id_perbaikan);

if($stmt->execute()){
    header('Location: daftar_kerusakan.php?status_update=sukses');
    exit;
} else {
    die("Gagal mengupdate status.");
}
?>