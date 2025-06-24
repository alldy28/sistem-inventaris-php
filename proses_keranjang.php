<?php
session_start();

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}


// --- PERUBAHAN LOGIKA DIMULAI DI SINI ---

// Cek jika tombol 'tambah_ke_keranjang' dari halaman produk diklik
if (isset($_POST['tambah_ke_keranjang'])) {
    
    // Ambil id dan jumlah dari input langsung, bukan dari array
    $id_produk = $_POST['id_produk'];
    $jumlah = $_POST['jumlah'];

    // Validasi dasar untuk memastikan data valid
    if (!empty($id_produk) && is_numeric($id_produk) && !empty($jumlah) && is_numeric($jumlah) && $jumlah > 0) {
        
        // Jika produk sudah ada di keranjang, TAMBAHKAN jumlahnya. Jika belum, buat baru.
        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $jumlah;
        } else {
            $_SESSION['keranjang'][$id_produk] = $jumlah;
        }
    }
    
    // Redirect kembali ke halaman produk setelah berhasil menambah item
    header('Location: produk.php?status=keranjang_update');
    exit;
}


// Aksi hapus item dari halaman keranjang (tetap sama)
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_produk_hapus = $_GET['id'];
    unset($_SESSION['keranjang'][$id_produk_hapus]);

    // Redirect kembali ke halaman KERANJANG setelah menghapus
    header('Location: keranjang.php?status=hapus_sukses');
    exit;
}

// Jika tidak ada aksi yang cocok, kembalikan ke halaman produk
header('Location: produk.php');
exit;