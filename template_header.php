<?php
// Selalu mulai sesi di baris paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';

// Cek sesi login
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

// Panggil file data_dashboard untuk akses ke fungsi notifikasi
// Gunakan @ untuk menekan error jika file tidak ada (misalnya di halaman login yang mungkin tidak butuh ini)
@include_once 'data_dashboard.php';

// Ambil data user dari sesi
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'user';
$inisial = strtoupper(substr($nama_lengkap, 0, 1));

// Hitung item keranjang untuk user
$jumlah_item_keranjang = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;

// Hitung permintaan masuk untuk admin
$jumlah_permintaan_masuk = 0;
// Pastikan fungsi ada dan role adalah admin sebelum memanggilnya
if (function_exists('getJumlahPermintaanMasuk') && $role === 'admin') {
    $jumlah_permintaan_masuk = getJumlahPermintaanMasuk($koneksi);
}

// Variabel $active_page akan didefinisikan di setiap halaman yang memanggil template ini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Sistem Inventaris'; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>InventarisApp</h3>
            </div>
            <div class="user-profile">
                <div class="avatar"><?= htmlspecialchars($inisial); ?></div>
                <h3><?= htmlspecialchars($nama_lengkap); ?></h3>
                <p><?= ucfirst(htmlspecialchars($role)); ?></p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?= ($active_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="produk.php" class="<?= ($active_page == 'produk') ? 'active' : ''; ?>">Produk</a></li>
                    
                    <li>
                        <a href="daftar_permintaan.php" class="<?= ($active_page == 'permintaan') ? 'active' : ''; ?>">
                            Permintaan Barang
                            <?php if ($role == 'admin' && $jumlah_permintaan_masuk > 0): ?>
                                <span class="badge"><?= $jumlah_permintaan_masuk; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if ($role == 'user'): ?>
                        <li>
                            <a href="keranjang.php" class="<?= ($active_page == 'keranjang') ? 'active' : ''; ?>">
                                Keranjang
                                <?php if ($jumlah_item_keranjang > 0): ?>
                                    <span class="badge"><?= $jumlah_item_keranjang; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="perbaikan.php" class="<?= ($active_page == 'perbaikan') ? 'active' : ''; ?>">
                                Perbaikan Aset
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role == 'admin'): ?>
                        <li class="menu-divider">Master Data</li>
                        <li><a href="kategori.php" class="<?= ($active_page == 'kategori') ? 'active' : ''; ?>">Kategori</a></li>
                        <li><a href="pengguna.php" class="<?= ($active_page == 'pengguna') ? 'active' : ''; ?>">Pengguna</a></li>
                        
                        <li class="menu-divider">Transaksi</li>
                        <li><a href="penerimaan.php" class="<?= ($active_page == 'penerimaan') ? 'active' : ''; ?>">Penerimaan Barang</a></li>

                        <li class="menu-divider">Laporan</li>
                        <li><a href="daftar_kerusakan.php" class="<?= ($active_page == 'kerusakan') ? 'active' : ''; ?>">Laporan Kerusakan</a></li>
                        <li><a href="laporan_realisasi.php" class="<?= ($active_page == 'laporan_realisasi') ? 'active' : ''; ?>">Laporan Realisasi</a></li>
                        <li><a href="laporan_bulanan.php" class="<?= ($active_page == 'laporan_bulanan') ? 'active' : ''; ?>">Laporan Bulanan</a></li>
                        <li><a href="histori_laporan.php" class="<?= ($active_page == 'histori_laporan') ? 'active' : ''; ?>">Histori Laporan</a></li>
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