<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Validasi data POST
if (!isset($_POST['id_produk'], $_POST['jumlah'], $_POST['harga_satuan']) || empty($_POST['id_produk']) || !is_numeric($_POST['jumlah']) || $_POST['jumlah'] <= 0) {
    die("Data input tidak valid.");
}

$id_produk = $_POST['id_produk'];
$jumlah = $_POST['jumlah'];
$harga_satuan = $_POST['harga_satuan'];
$catatan = $_POST['catatan'] ?? '';
$tanggal_sekarang = date('Y-m-d H:i:s');
$bentuk_kontrak = $_POST['bentuk_kontrak'] ?? null;
$nama_penyedia = $_POST['nama_penyedia'] ?? null;
$nomor_faktur = $_POST['nomor_faktur'] ?? null;
$sumber_anggaran = $_POST['sumber_anggaran'] ?? null;

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // Langkah 1: Catat transaksi ke tabel `penerimaan`
    $sql1 = "INSERT INTO penerimaan (id_produk, jumlah, harga_satuan, catatan, tanggal_penerimaan, bentuk_kontrak, nama_penyedia, nomor_faktur, sumber_anggaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt1 = $koneksi->prepare($sql1);
    $stmt1->bind_param("iidssssss", $id_produk, $jumlah, $harga_satuan, $catatan, $tanggal_sekarang, $bentuk_kontrak, $nama_penyedia, $nomor_faktur, $sumber_anggaran);
    $stmt1->execute();
    $id_penerimaan_baru = $koneksi->insert_id;

    // Langkah 2: Masukkan data sebagai batch baru ke tabel `stok_batch`
    $stmt_batch = $koneksi->prepare("INSERT INTO stok_batch (id_produk, id_penerimaan, jumlah_awal, sisa_stok, harga_beli, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_batch->bind_param("iiiids", $id_produk, $id_penerimaan_baru, $jumlah, $jumlah, $harga_satuan, $tanggal_sekarang);
    $stmt_batch->execute();
    
    // Langkah 3: Update (tambah) stok total di tabel `produk`
    $stmt_update_stok = $koneksi->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
    $stmt_update_stok->bind_param("ii", $jumlah, $id_produk);
    $stmt_update_stok->execute();

    // <<-- LOGIKA BARU: UPDATE HARGA JIKA STOK SEBELUMNYA KOSONG -->>
    // Ambil total stok saat ini SETELAH diupdate
    $stmt_get_stok = $koneksi->prepare("SELECT stok FROM produk WHERE id = ?");
    $stmt_get_stok->bind_param("i", $id_produk);
    $stmt_get_stok->execute();
    $stok_saat_ini = $stmt_get_stok->get_result()->fetch_assoc()['stok'];

    // Jika stok saat ini sama dengan jumlah yang baru diterima, artinya stok sebelumnya 0
    if ($stok_saat_ini == $jumlah) {
        // Maka, jadikan harga dari penerimaan ini sebagai harga utama produk
        $stmt_update_harga = $koneksi->prepare("UPDATE produk SET harga = ? WHERE id = ?");
        $stmt_update_harga->bind_param("di", $harga_satuan, $id_produk);
        $stmt_update_harga->execute();
    }
    // <<-- AKHIR LOGIKA BARU -->>
    
    // Jika semua berhasil, commit transaksi
    $koneksi->commit();

    // Arahkan kembali ke halaman daftar penerimaan
    header('Location: penerimaan.php?status=sukses');
    exit;

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $koneksi->rollback();
    die("GAGAL TOTAL. Transaksi dibatalkan. Error: " . $e->getMessage());
}
?>