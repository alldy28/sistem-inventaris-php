<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Logika untuk Aksi Tambah (via POST)
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $nusp_id = $_POST['nusp_id'];
    $nama_kategori = $_POST['nama_kategori'];
    
    $stmt = $koneksi->prepare("INSERT INTO kategori_produk (nusp_id, nama_kategori) VALUES (?, ?)");
    $stmt->bind_param("ss", $nusp_id, $nama_kategori);
    
    if ($stmt->execute()) {
        header('Location: kategori.php?status=sukses_tambah');
    } else {
        die("Error menambah kategori: " . $stmt->error);
    }
    $stmt->close();
}

// Logika untuk Aksi Edit (via POST)
elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $id = $_POST['id'];
    $nusp_id = $_POST['nusp_id'];
    $nama_kategori = $_POST['nama_kategori'];

    $stmt = $koneksi->prepare("UPDATE kategori_produk SET nusp_id = ?, nama_kategori = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nusp_id, $nama_kategori, $id);

    if ($stmt->execute()) {
        header('Location: kategori.php?status=sukses_edit');
    } else {
        die("Error mengupdate kategori: " . $stmt->error);
    }
    $stmt->close();
}

// Logika untuk Aksi Hapus (via GET)
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = $_GET['id'];

    // Karena kita memakai ON DELETE CASCADE, menghapus kategori akan otomatis menghapus semua produk di bawahnya.
    $stmt = $koneksi->prepare("DELETE FROM kategori_produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: kategori.php?status=sukses_hapus');
    } else {
        die("Error menghapus kategori: " . $stmt->error);
    }
    $stmt->close();
}

// Jika tidak ada aksi yang cocok
else {
    header('Location: kategori.php');
}

exit;
?>