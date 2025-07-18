<?php
session_start();
require_once 'koneksi.php'; // Gunakan require_once agar tidak di-load berulang

// Cek sesi login
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

// Ambil data user dari sesi
$nama_lengkap = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];
$inisial = strtoupper(substr($nama_lengkap, 0, 1));

// <<-- PERBAIKAN #1: Tambahkan baris ini untuk menghitung item keranjang -->>
$jumlah_item_keranjang = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;

// Variabel $active_page akan didefinisikan di halaman yang memanggil template ini
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>AppDashboard</h3>
            </div>
            <div class="user-profile">
                <div class="avatar"><?php echo htmlspecialchars($inisial); ?></div>
                <h3><?php echo htmlspecialchars($nama_lengkap); ?></h3>
                <p><?php echo ucfirst(htmlspecialchars($role)); ?></p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"
                            class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="produk.php"
                            class="<?php echo ($active_page == 'produk') ? 'active' : ''; ?>">Produk</a></li>

                    <li><a href="daftar_permintaan.php"
                            class="<?php echo ($active_page == 'permintaan') ? 'active' : ''; ?>">Permintaan Barang</a>
                    </li>

                    <?php if ($role == 'user'): ?>
                    <li>
                        <a href="keranjang.php" class="<?php echo ($active_page == 'keranjang') ? 'active' : ''; ?>">
                            Keranjang Permintaan
                            <?php if ($jumlah_item_keranjang > 0): ?>
                            <span class="badge"><?php echo $jumlah_item_keranjang; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li><a href="#" class="<?php echo ($active_page == 'pengguna') ? 'active' : ''; ?>">Pengguna</a>
                    </li>

                    <?php if ($role == 'admin'): ?>
                    <li><a href="penerimaan.php"
                            class="<?php echo ($active_page == 'penerimaan') ? 'active' : ''; ?>">Penerimaan Barang</a>
                    </li>
                    <li><a href="laporan_persediaan.php" class="<?php echo ($active_page == 'laporan_persediaan') ? 'active' : ''; ?>">Laporan Persediaan</a></li>
                    <li><a href="#" class="<?php echo ($active_page == 'pengaturan') ? 'active' : ''; ?>">Pengaturan</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="logout-link">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="main-content">