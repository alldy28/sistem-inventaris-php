<?php
session_start();
require 'koneksi.php';

// Hanya admin yang bisa melakukan aksi
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Anda bukan admin.");
}

// Aksi untuk TAMBAH dan EDIT (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    
    // Ambil data dari form
    $nusp_id = $_POST['nusp_id'];
    $nama_barang = $_POST['nama_barang'];
    $satuan = $_POST['satuan'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];

    // Jika aksinya adalah 'tambah'
    if ($_POST['aksi'] == 'tambah') {
        $sql = "INSERT INTO produk (nusp_id, nama_barang, satuan, stok, harga) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssid", $nusp_id, $nama_barang, $satuan, $stok, $harga);
        if ($stmt->execute()) {
            header('Location: produk.php?status=sukses_tambah');
        } else {
            header('Location: produk.php?status=gagal');
        }
        $stmt->close();
    }
    // Jika aksinya adalah 'edit'
    elseif ($_POST['aksi'] == 'edit' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $sql = "UPDATE produk SET nusp_id = ?, nama_barang = ?, satuan = ?, stok = ?, harga = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssidi", $nusp_id, $nama_barang, $satuan, $stok, $harga, $id);
        if ($stmt->execute()) {
            header('Location: produk.php?status=sukses_edit');
        } else {
            header('Location: produk.php?status=gagal');
        }
        $stmt->close();
    }
}

// Aksi untuk HAPUS (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $sql = "DELETE FROM produk WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header('Location: produk.php?status=sukses_hapus');
        } else {
            header('Location: produk.php?status=gagal');
        }
        $stmt->close();
    }
}

$koneksi->close();
exit;