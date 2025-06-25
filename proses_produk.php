<?php
require_once 'koneksi.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { die("Akses ditolak."); }

// Logika untuk Aksi Tambah
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $id_kategori = $_POST['id_kategori'];
    $spesifikasi = $_POST['spesifikasi'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    $stmt = $koneksi->prepare("INSERT INTO produk (id_kategori, spesifikasi, satuan, harga, stok, stok_awal, harga_awal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // Saat menambah, stok awal dan harga awal sama dengan stok dan harga saat ini
    $stmt->bind_param("isssidd", $id_kategori, $spesifikasi, $satuan, $harga, $stok, $stok, $harga);
    
    if ($stmt->execute()) { header('Location: produk.php?status=sukses_tambah'); } 
    else { die("Error menambah produk: " . $stmt->error); }
    $stmt->close();
}

// Logika untuk Aksi Edit
elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $id = $_POST['id'];
    $id_kategori = $_POST['id_kategori'];
    $spesifikasi = $_POST['spesifikasi'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    
    // Saat edit, kita tidak mengubah stok secara langsung dari form ini. Stok hanya berubah dari penerimaan/pengeluaran.
    $stmt = $koneksi->prepare("UPDATE produk SET id_kategori = ?, spesifikasi = ?, satuan = ?, harga = ? WHERE id = ?");
    $stmt->bind_param("issdi", $id_kategori, $spesifikasi, $satuan, $harga, $id);

    if ($stmt->execute()) { header('Location: produk.php?status=sukses_edit'); } 
    else { die("Error mengupdate produk: " . $stmt->error); }
    $stmt->close();
}

// Logika untuk Aksi Hapus (tetap sama)
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = $_GET['id'];
    $stmt = $koneksi->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { header('Location: produk.php?status=sukses_hapus'); } 
    else { die("Error menghapus produk: " . $stmt->error); }
    $stmt->close();
}

else {
    header('Location: produk.php');
}

exit;
?>