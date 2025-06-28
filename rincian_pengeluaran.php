<?php
$page_title = 'Rincian Pengeluaran';
$active_page = 'laporan_realisasi';
require_once 'template_header.php';

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id_permintaan'])) {
    echo "<p>Akses ditolak atau parameter tidak lengkap.</p>";
    require_once 'template_footer.php';
    exit;
}

$id_permintaan = $_GET['id_permintaan'];

// Ambil data permintaan
$stmt_permintaan = $koneksi->prepare("SELECT p.*, u.nama_lengkap as nama_peminta FROM permintaan p JOIN users u ON p.id_user = u.id WHERE p.id = ?");
$stmt_permintaan->bind_param("i", $id_permintaan);
$stmt_permintaan->execute();
$permintaan = $stmt_permintaan->get_result()->fetch_assoc();

// Ambil rincian barang dari permintaan tersebut
$stmt_rincian = $koneksi->prepare("
    SELECT dp.jumlah, dp.nilai_keluar_fifo, pr.spesifikasi, kp.nama_kategori
    FROM detail_permintaan dp
    JOIN produk pr ON dp.id_produk = pr.id
    JOIN kategori_produk kp ON pr.id_kategori = kp.id
    WHERE dp.id_permintaan = ?
");
$stmt_rincian->bind_param("i", $id_permintaan);
$stmt_rincian->execute();
$rincian_result = $stmt_rincian->get_result();
?>

<header class="main-header">
    <h1>Rincian Pengeluaran untuk Permintaan #<?php echo $id_permintaan; ?></h1>
    <p>Peminta: <strong><?php echo htmlspecialchars($permintaan['nama_peminta']); ?></strong> | Tanggal Proses: <strong><?php echo date('d F Y', strtotime($permintaan['tanggal_diproses'])); ?></strong></p>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Jumlah Keluar</th>
                    <th>Harga Satuan (FIFO)</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rincian_result && $rincian_result->num_rows > 0): ?>
                    <?php while($row = $rincian_result->fetch_assoc()): ?>
                    <?php $harga_satuan = ($row['jumlah'] > 0) ? $row['nilai_keluar_fifo'] / $row['jumlah'] : 0; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nama_kategori'] . ' (' . $row['spesifikasi'] . ')'); ?></td>
                        <td class="text-center"><?php echo $row['jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($row['nilai_keluar_fifo'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">Tidak ada data rincian pengeluaran.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="form-actions" style="margin-top: 20px;">
            <button onclick="window.print();" class="btn btn-primary">Cetak</button>
            <button onclick="window.close();" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>