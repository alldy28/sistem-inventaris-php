<?php
$page_title = 'Rincian Pengeluaran';
$active_page = 'laporan_realisasi'; // Atau sesuaikan dengan menu aktif Anda
require_once 'template_header.php';

// BAGIAN 1: FUNGSI PENGAMBILAN DATA

function getPermintaanHeader(mysqli $koneksi, int $id_permintaan): ?array {
    $stmt = $koneksi->prepare("SELECT p.tanggal_diproses, u.nama_lengkap as nama_peminta FROM permintaan p JOIN users u ON p.id_user = u.id WHERE p.id = ?");
    $stmt->bind_param("i", $id_permintaan);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPermintaanItems(mysqli $koneksi, int $id_permintaan): array {
    $stmt = $koneksi->prepare("
        SELECT dp.jumlah_disetujui as jumlah_keluar, dp.nilai_keluar_fifo, pr.spesifikasi, kp.nama_kategori, pr.satuan
        FROM detail_permintaan dp
        JOIN produk pr ON dp.id_produk = pr.id
        JOIN kategori_produk kp ON pr.id_kategori = kp.id
        WHERE dp.id_permintaan = ? AND dp.jumlah_disetujui > 0
    ");
    $stmt->bind_param("i", $id_permintaan);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


// BAGIAN 2: LOGIKA UTAMA HALAMAN

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id_permintaan'])) {
    echo "<p class='content-section'>Akses ditolak atau parameter tidak lengkap.</p>";
    require_once 'template_footer.php';
    exit;
}

$id_permintaan = (int)$_GET['id_permintaan'];
$permintaan = getPermintaanHeader($koneksi, $id_permintaan);
$rincian_items = getPermintaanItems($koneksi, $id_permintaan);

if (!$permintaan) {
    echo "<p class='content-section'>Data permintaan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
?>

<head>
    <style>
        /* Gaya normal untuk tampilan di browser */
        .receipt-container {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            max-width: 800px;
            margin: 20px auto;
        }
        /* Tambahkan gaya lain jika perlu */

        /* ======================================================= */
        /* PERBAIKAN: CSS KHUSUS UNTUK VERSI CETAK                 */
        /* ======================================================= */
        @media print {
            /* Sembunyikan semua elemen yang tidak perlu saat cetak */
            body > .dashboard-container > .sidebar,
            body > .dashboard-container > .main-content > .main-header,
            .form-actions {
                display: none !important;
            }

            /* Atur ulang layout agar konten utama memenuhi halaman cetak */
            body {
                background-color: #fff;
            }
            .dashboard-container {
                display: block;
                margin: 0;
                padding: 0;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .content-section {
                padding: 0 !important;
            }
            .receipt-header h1 {
                font-size: 18pt;
            }
            .receipt-header p {
                font-size: 12pt;
            }
            table {
                font-size: 10pt;
            }
        }
    </style>
</head>

<div class="receipt-container">
    <div class="receipt-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
        <h1>Bukti Pengeluaran Barang</h1>
        <p>Permintaan #<?php echo $id_permintaan; ?></p>
    </div>
    
    <p><strong>Peminta:</strong> <?php echo htmlspecialchars($permintaan['nama_peminta']); ?></p>
    <p><strong>Tanggal Proses:</strong> <?php echo date('d F Y', strtotime($permintaan['tanggal_diproses'])); ?></p>

    <div class="table-container" style="margin-top: 20px;">
        <table class="product-table">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th class="text-center">Jumlah Keluar</th>
                    <th class="text-right">Harga Satuan (FIFO)</th>
                    <th class="text-right">Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rincian_items)): ?>
                    <?php 
                        $grand_total = 0;
                        foreach($rincian_items as $item):
                        // Gunakan jumlah_disetujui dari query
                        $harga_satuan = ($item['jumlah_keluar'] > 0) ? $item['nilai_keluar_fifo'] / $item['jumlah_keluar'] : 0;
                        $grand_total += $item['nilai_keluar_fifo'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nama_kategori'] . ' ' . $item['spesifikasi']); ?></td>
                        <td class="text-center"><?php echo $item['jumlah_keluar']; ?> <?php echo htmlspecialchars($item['satuan']); ?></td>
                        <td class="text-right">Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['nilai_keluar_fifo'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">Tidak ada data rincian pengeluaran untuk permintaan ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($rincian_items)): ?>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Grand Total</th>
                    <th class="text-right">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div> 
</div>

<div class="form-actions" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print();" class="btn btn-primary">Cetak Bukti Ini</button>
    <a href="daftar_permintaan.php" class="btn btn-secondary">Kembali ke Daftar Permintaan</a>
</div>


<?php require_once 'template_footer.php'; ?>