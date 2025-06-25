<?php
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// --- LOGIKA UNTUK HAPUS DATA ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_penerimaan = $_GET['id'];

    $koneksi->begin_transaction();
    try {
        // 1. Ambil dulu data penerimaan yang akan dihapus untuk tahu jumlah & id produknya
        $stmt_get = $koneksi->prepare("SELECT id_produk, jumlah FROM penerimaan WHERE id = ?");
        $stmt_get->bind_param("i", $id_penerimaan);
        $stmt_get->execute();
        $penerimaan_to_delete = $stmt_get->get_result()->fetch_assoc();

        if ($penerimaan_to_delete) {
            $id_produk = $penerimaan_to_delete['id_produk'];
            $jumlah_dihapus = $penerimaan_to_delete['jumlah'];

            // 2. Kurangi stok di tabel produk (mengembalikan stok)
            $stmt_update_stok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $stmt_update_stok->bind_param("ii", $jumlah_dihapus, $id_produk);
            $stmt_update_stok->execute();

            // 3. Hapus batch stok yang terkait
            $stmt_delete_batch = $koneksi->prepare("DELETE FROM stok_batch WHERE id_penerimaan = ?");
            $stmt_delete_batch->bind_param("i", $id_penerimaan);
            $stmt_delete_batch->execute();

            // 4. Hapus data penerimaan itu sendiri
            $stmt_delete_penerimaan = $koneksi->prepare("DELETE FROM penerimaan WHERE id = ?");
            $stmt_delete_penerimaan->bind_param("i", $id_penerimaan);
            $stmt_delete_penerimaan->execute();
        }

        $koneksi->commit();
        header('Location: penerimaan.php?status=hapus_sukses');
        exit;

    } catch (Exception $e) {
        $koneksi->rollback();
        die("Gagal menghapus data penerimaan. Error: " . $e->getMessage());
    }
}


// --- LOGIKA UNTUK TAMBAH DATA (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];
    $harga_satuan = $_POST['harga_satuan'];
    $catatan = $_POST['catatan'] ?? '';
    $tanggal_sekarang = date('Y-m-d H:i:s');
    $bentuk_kontrak = $_POST['bentuk_kontrak'] ?? null;
    $nama_penyedia = $_POST['nama_penyedia'] ?? null;
    $nomor_faktur = $_POST['nomor_faktur'] ?? null;
    $sumber_anggaran = $_POST['sumber_anggaran'] ?? null;

    if (empty($id_produk) || !is_numeric($jumlah) || $jumlah <= 0) {
        die("Data input tidak valid.");
    }
    
    $koneksi->begin_transaction();
    try {
        $sql1 = "INSERT INTO penerimaan (id_produk, jumlah, harga_satuan, catatan, tanggal_penerimaan, bentuk_kontrak, nama_penyedia, nomor_faktur, sumber_anggaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $koneksi->prepare($sql1);
        $stmt1->bind_param("iidssssss", $id_produk, $jumlah, $harga_satuan, $catatan, $tanggal_sekarang, $bentuk_kontrak, $nama_penyedia, $nomor_faktur, $sumber_anggaran);
        $stmt1->execute();
        $id_penerimaan_baru = $koneksi->insert_id;

        $stmt_batch = $koneksi->prepare("INSERT INTO stok_batch (id_produk, id_penerimaan, jumlah_awal, sisa_stok, harga_beli, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_batch->bind_param("iiiids", $id_produk, $id_penerimaan_baru, $jumlah, $jumlah, $harga_satuan, $tanggal_sekarang);
        $stmt_batch->execute();
        
        $stmt2 = $koneksi->prepare("UPDATE produk SET stok = stok + ?, harga = ? WHERE id = ?");
        $stmt2->bind_param("idi", $jumlah, $harga_satuan, $id_produk);
        $stmt2->execute();
        
        $koneksi->commit();
        header('Location: penerimaan.php?status=sukses');
        exit;
    } catch (mysqli_sql_exception $exception) {
        $koneksi->rollback();
        die("Gagal mencatat penerimaan. Error: " . $exception->getMessage());
    }
}

// Jika tidak ada aksi yang cocok, redirect
header('Location: penerimaan.php');
exit;