<?php
require_once 'koneksi.php';
session_start();

// Pastikan user sudah login dan keranjang tidak kosong
if (!isset($_SESSION['loggedin']) || empty($_SESSION['keranjang'])) {
    header('Location: produk.php');
    exit;
}

// Ambil id user dari sesi
$id_user = $_SESSION['user_id'];

// Mulai transaksi database untuk memastikan semua query berhasil
$koneksi->begin_transaction();

try {
    // 1. Masukkan data ke tabel `permintaan`
    $stmt1 = $koneksi->prepare("INSERT INTO permintaan (id_user, status) VALUES (?, 'Pending')");
    $stmt1->bind_param("i", $id_user);
    $stmt1->execute();

    // Dapatkan ID dari permintaan yang baru saja dibuat
    $id_permintaan_baru = $koneksi->insert_id;

    // 2. Ambil data produk untuk mendapatkan harga saat ini
    $ids_produk = implode(',', array_keys($_SESSION['keranjang']));
    $sql_produk = "SELECT id, harga FROM produk WHERE id IN ($ids_produk)";
    $result_produk = $koneksi->query($sql_produk);
    $harga_produk = [];
    while ($row = $result_produk->fetch_assoc()) {
        $harga_produk[$row['id']] = $row['harga'];
    }

    // 3. Masukkan setiap item dari keranjang ke tabel `detail_permintaan`
    $stmt2 = $koneksi->prepare("INSERT INTO detail_permintaan (id_permintaan, id_produk, jumlah, harga_saat_minta) VALUES (?, ?, ?, ?)");

    foreach ($_SESSION['keranjang'] as $id_produk => $jumlah) {
        $harga_saat_ini = $harga_produk[$id_produk] ?? 0;
        $stmt2->bind_param("iiid", $id_permintaan_baru, $id_produk, $jumlah, $harga_saat_ini);
        $stmt2->execute();
    }

    // Jika semua query berhasil, commit transaksi
    $koneksi->commit();

    // Kosongkan keranjang
    unset($_SESSION['keranjang']);

    // <<-- INI PERUBAHAN PENTINGNYA -->>
    // Kirim parameter yang spesifik untuk aksi user "mengajukan permintaan"
    header('Location: daftar_permintaan.php?status=permintaan_terkirim');
    exit;

} catch (mysqli_sql_exception $exception) {
    // Jika ada error, batalkan semua perubahan (rollback)
    $koneksi->rollback();
    
    // Tampilkan pesan error atau redirect ke halaman error
    die("Gagal mengajukan permintaan. Silakan coba lagi. Error: " . $exception->getMessage());
}