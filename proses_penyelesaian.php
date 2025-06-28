<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Hanya admin yang login via POST dan menekan tombol 'selesaikan'
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['selesaikan'])) { 
    die("Akses ditolak."); 
}

// Ambil semua data dari form input manual
$id_perbaikan = $_POST['id_perbaikan'];
$nama_komponen = $_POST['nama_komponen'];
$jumlah = $_POST['jumlah'];
$harga_satuan = $_POST['harga_satuan'];
$catatan_admin = $_POST['catatan_admin'];
$tanggal_proses = date('Y-m-d H:i:s');

// Validasi dasar untuk data yang diinput
if (empty($nama_komponen) || !is_numeric($jumlah) || !is_numeric($harga_satuan) || $jumlah <= 0) {
    die("Data input untuk komponen tidak valid.");
}

// Hitung total harga untuk komponen yang dicatat
$total_harga = $jumlah * $harga_satuan;

// Mulai transaksi database untuk memastikan kedua query berhasil
$koneksi->begin_transaction();

try {
    // Langkah 1: Masukkan data komponen ke tabel baru `komponen_perbaikan`
    $stmt1 = $koneksi->prepare(
        "INSERT INTO komponen_perbaikan (id_perbaikan, nama_komponen, jumlah, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt1->bind_param("isidd", $id_perbaikan, $nama_komponen, $jumlah, $harga_satuan, $total_harga);
    $stmt1->execute();

    // Langkah 2: Update status laporan perbaikan utama menjadi 'Selesai'
    $stmt2 = $koneksi->prepare(
        "UPDATE perbaikan_aset SET status_perbaikan = 'Selesai', catatan_admin = ?, tanggal_selesai = ? WHERE id = ?"
    );
    $stmt2->bind_param("ssi", $catatan_admin, $tanggal_proses, $id_perbaikan);
    $stmt2->execute();

    // Jika semua langkah berhasil, simpan permanen perubahan
    $koneksi->commit();

    // Arahkan kembali ke daftar laporan kerusakan dengan notifikasi sukses
    header('Location: daftar_kerusakan.php?status_update=sukses');
    exit;

} catch (Exception $e) {
    // Jika ada error di salah satu langkah, batalkan semua perubahan
    $koneksi->rollback();
    
    // Tampilkan pesan error yang informatif
    die("Gagal menyelesaikan perbaikan. Terjadi error pada database. Pesan: " . $e->getMessage());
}
?>