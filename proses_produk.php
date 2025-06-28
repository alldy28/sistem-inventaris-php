<?php
require_once 'koneksi.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { die("Akses ditolak."); }

// Logika untuk Aksi Tambah (dengan logika batch baru)
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $id_kategori = $_POST['id_kategori'];
    $spesifikasi = $_POST['spesifikasi'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    // Mulai transaksi untuk memastikan semua query berhasil
    $koneksi->begin_transaction();
    try {
        // Langkah 1: Masukkan data produk utama (tanpa stok awal lagi)
        $stmt_produk = $koneksi->prepare("INSERT INTO produk (id_kategori, spesifikasi, satuan, stok, harga) VALUES (?, ?, ?, ?, ?)");
        $stmt_produk->bind_param("issid", $id_kategori, $spesifikasi, $satuan, $stok, $harga);
        $stmt_produk->execute();
        $id_produk_baru = $koneksi->insert_id;

        // Langkah 2: BUAT BATCH PERTAMA untuk produk ini
        // Kita buat "Penerimaan" virtual untuk stok awal ini
        $catatan_penerimaan_awal = "Stok Awal untuk produk baru.";
        $tanggal_awal = date('Y-m-d H:i:s');
        
        $stmt_penerimaan = $koneksi->prepare("INSERT INTO penerimaan (id_produk, jumlah, harga_satuan, catatan, tanggal_penerimaan) VALUES (?, ?, ?, ?, ?)");
        $stmt_penerimaan->bind_param("iidss", $id_produk_baru, $stok, $harga, $catatan_penerimaan_awal, $tanggal_awal);
        $stmt_penerimaan->execute();
        $id_penerimaan_baru = $koneksi->insert_id;

        $stmt_batch = $koneksi->prepare("INSERT INTO stok_batch (id_produk, id_penerimaan, jumlah_awal, sisa_stok, harga_beli, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_batch->bind_param("iiiids", $id_produk_baru, $id_penerimaan_baru, $stok, $stok, $harga, $tanggal_awal);
        $stmt_batch->execute();
        
        // Jika semua berhasil, commit
        $koneksi->commit();
        header('Location: produk.php?status=sukses_tambah');

    } catch (Exception $e) {
        $koneksi->rollback();
        die("Gagal menambah produk. Error: " . $e->getMessage());
    }
}

// Logika untuk Aksi Edit (tetap sama)
elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    // ... (kode edit Anda tidak berubah) ...
}

// Logika untuk Aksi Hapus (tetap sama)
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    // ... (kode hapus Anda tidak berubah) ...
}

else {
    header('Location: produk.php');
}

exit;
?>