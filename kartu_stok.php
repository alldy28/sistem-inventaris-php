<?php
$page_title = 'Kartu Stok';
$active_page = 'laporan_realisasi';
require_once 'template_header.php';

// Keamanan dasar dan pengambilan data produk
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    echo "<p>Akses ditolak atau ID produk tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_produk = $_GET['id_produk'];
$stmt_produk = $koneksi->prepare("SELECT pr.*, kp.nama_kategori FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id WHERE pr.id = ?");
$stmt_produk->bind_param("i", $id_produk);
$stmt_produk->execute();
$produk = $stmt_produk->get_result()->fetch_assoc();
if (!$produk) {
    echo "<p>Produk tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}

// --- PENGUMPULAN SEMUA TRANSAKSI ---
$transactions = [];
$saldo_awal_data = null;

// 1. Ambil batch paling pertama sebagai Saldo Awal
$stmt_awal = $koneksi->prepare("SELECT tanggal_penerimaan as tanggal, jumlah as jumlah_awal, harga_satuan as harga_awal, id as ref_id FROM penerimaan WHERE id_produk = ? ORDER BY tanggal_penerimaan ASC, id ASC LIMIT 1");
$stmt_awal->bind_param("i", $id_produk);
$stmt_awal->execute();
$result_awal = $stmt_awal->get_result();
if ($result_awal->num_rows > 0) {
    $saldo_awal_data = $result_awal->fetch_assoc();
}

// 2. Ambil semua PENERIMAAN (selain batch saldo awal)
if ($saldo_awal_data) {
    $stmt_penerimaan = $koneksi->prepare("SELECT tanggal_penerimaan as tanggal, jumlah, harga_satuan, id as ref_id, 'PENERIMAAN' as tipe FROM penerimaan WHERE id_produk = ? AND id != ?");
    $stmt_penerimaan->bind_param("ii", $id_produk, $saldo_awal_data['ref_id']);
} else {
    $stmt_penerimaan = $koneksi->prepare("SELECT tanggal_penerimaan as tanggal, jumlah, harga_satuan, id as ref_id, 'PENERIMAAN' as tipe FROM penerimaan WHERE id_produk = ?");
    $stmt_penerimaan->bind_param("i", $id_produk);
}
$stmt_penerimaan->execute();
$result_penerimaan = $stmt_penerimaan->get_result();
while ($row = $result_penerimaan->fetch_assoc()) { $transactions[] = $row; }

// 3. Ambil semua PENGELUARAN (satu per satu)
$stmt_pengeluaran = $koneksi->prepare("SELECT p.tanggal_diproses as tanggal, dp.jumlah, dp.nilai_keluar_fifo, p.id as ref_id, 'PENGELUARAN' as tipe FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE dp.id_produk = ? AND p.status = 'Disetujui'");
$stmt_pengeluaran->bind_param("i", $id_produk);
$stmt_pengeluaran->execute();
$result_pengeluaran = $stmt_pengeluaran->get_result();
while ($row = $result_pengeluaran->fetch_assoc()) { $transactions[] = $row; }

// 4. Urutkan semua transaksi berdasarkan tanggal
usort($transactions, function($a, $b) {
    return strtotime($a['tanggal']) <=> strtotime($b['tanggal']);
});
?>

<header class="main-header">
    <h1>Kartu Stok: <?php echo htmlspecialchars($produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')'); ?></h1>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2">Tanggal</th>
                    <th rowspan="2">Keterangan</th>
                    <th colspan="3">Masuk (Penerimaan)</th>
                    <th colspan="3">Keluar (Pengeluaran)</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                    <th>Jml</th><th>Aksi</th><th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Inisialisasi variabel untuk grand total dari Saldo Awal
                $total_masuk_jml = $saldo_awal_data['jumlah_awal'] ?? 0;
                $total_masuk_nilai = $total_masuk_jml * ($saldo_awal_data['harga_awal'] ?? 0);
                $total_keluar_jml = 0;
                $total_keluar_nilai = 0;
                ?>
                <tr>
                    <td><?php echo $saldo_awal_data ? date('d M Y', strtotime($saldo_awal_data['tanggal'])) : '-'; ?></td>
                    <td><strong>Saldo Awal (Batch Pertama)</strong></td>
                    <td class="text-center"><strong><?php echo $total_masuk_jml; ?></strong></td>
                    <td class="text-right"><strong>Rp <?php echo number_format($saldo_awal_data['harga_awal'] ?? 0); ?></strong></td>
                    <td class="text-right"><strong>Rp <?php echo number_format($total_masuk_nilai); ?></strong></td>
                    <td colspan="3">-</td> 
                </tr>

                <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?php echo date('d M Y H:i', strtotime($trans['tanggal'])); ?></td>
                        <?php
                            $keterangan = '';
                            if ($trans['tipe'] == 'PENERIMAAN') {
                                $keterangan = 'Penerimaan #' . $trans['ref_id'];
                                $total_masuk_jml += $trans['jumlah'];
                                $total_masuk_nilai += $trans['jumlah'] * $trans['harga_satuan'];
                            } else { // PENGELUARAN
                                $keterangan = 'Permintaan #' . $trans['ref_id'];
                                $total_keluar_jml += $trans['jumlah'];
                                $total_keluar_nilai += $trans['nilai_keluar_fifo'];
                            }
                        ?>
                        <td><?php echo htmlspecialchars($keterangan); ?></td>
                        <td class="text-center"><?php echo ($trans['tipe'] == 'PENERIMAAN') ? $trans['jumlah'] : '-'; ?></td>
                        <td class="text-right"><?php echo ($trans['tipe'] == 'PENERIMAAN') ? 'Rp '.number_format($trans['harga_satuan']) : '-'; ?></td>
                        <td class="text-right"><?php echo ($trans['tipe'] == 'PENERIMAAN') ? 'Rp '.number_format($trans['jumlah'] * $trans['harga_satuan']) : '-'; ?></td>
                        <td class="text-center"><?php echo ($trans['tipe'] == 'PENGELUARAN') ? $trans['jumlah'] : '-'; ?></td>
                        <td class="text-center">
                            <?php if($trans['tipe'] == 'PENGELUARAN'): ?>
                                <a href="rincian_pengeluaran.php?id_permintaan=<?php echo $trans['ref_id']; ?>" class="btn btn-primary btn-sm" target="_blank">Rincian</a>
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td class="text-right"><?php echo ($trans['tipe'] == 'PENGELUARAN') ? 'Rp '.number_format($trans['nilai_keluar_fifo']) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($saldo_awal_data) && empty($transactions)): ?>
                    <tr><td colspan="8" class="text-center">Belum ada stok atau transaksi untuk produk ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-center"><?php echo $total_masuk_jml; ?></td>
                    <td></td>
                    <td class="text-right">Rp <?php echo number_format($total_masuk_nilai, 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $total_keluar_jml; ?></td>
                    <td></td>
                    <td class="text-right">Rp <?php echo number_format($total_keluar_nilai, 0, ',', '.'); ?></td>
                </tr>
                <tr style="background-color: #e8f4fd; font-weight: bold;">
                    <td colspan="5" class="text-right">SALDO AKHIR (Jumlah)</td>
                    <td colspan="3" class="text-center"><?php echo ($total_masuk_jml - $total_keluar_jml); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>