<?php
$page_title = 'Laporan Realisasi Persediaan';
$active_page = 'laporan_realisasi';

// PERBAIKAN: Fungsi sekarang didefinisikan di lingkup global agar bisa dipanggil dari file lain.
function getInventoryRealizationReport(mysqli $koneksi): array
{
    $aggregate_data = getAggregateData($koneksi);
    $special_prices = getSpecialPrices($koneksi);
    $laporan_data = [];

    foreach ($aggregate_data as $row) {
        $id_produk = $row['id_produk'];
        
        $harga_acuan_batch_aktif = $special_prices['aktif'][$id_produk] 
                                    ?? $special_prices['penerimaan_terakhir'][$id_produk] 
                                    ?? $row['harga_awal'] 
                                    ?? 0;

        $saldo_awal_nilai = $row['saldo_awal_jumlah'] * $row['saldo_awal_harga'];
        $saldo_akhir_jumlah = $row['total_penerimaan_jumlah'] - $row['total_pengeluaran_jumlah'];
        $saldo_akhir_nilai = $row['total_penerimaan_nilai'] - $row['total_pengeluaran_nilai'];
        
        $laporan_data[] = [
            'id_produk' => $id_produk, 'nama_kategori' => $row['nama_kategori'],
            'spesifikasi' => $row['spesifikasi'], 'satuan' => $row['satuan'],
            'saldo_awal_jumlah' => $row['saldo_awal_jumlah'], 'saldo_awal_harga' => $row['saldo_awal_harga'],
            'saldo_awal_nilai' => $saldo_awal_nilai,
            'penerimaan_jumlah_total' => $row['total_penerimaan_jumlah'], 'penerimaan_nilai_total' => $row['total_penerimaan_nilai'],
            'harga_batch_aktif' => $harga_acuan_batch_aktif,
            'pengeluaran_jumlah' => $row['total_pengeluaran_jumlah'],
            'pengeluaran_harga_terakhir' => $special_prices['keluar_terakhir'][$id_produk] ?? 0,
            'pengeluaran_nilai' => $row['total_pengeluaran_nilai'],
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga' => $harga_acuan_batch_aktif,
            'saldo_akhir_nilai' => $saldo_akhir_nilai,
            'tgl_perolehan' => $row['tanggal_penerimaan'] ?? '-',
            'bentuk_kontrak' => $row['bentuk_kontrak'] ?? '-', 'nama_penyedia' => $row['nama_penyedia'] ?? '-',
        ];
    }
    return $laporan_data;
}

// Bagian Tampilan HTML hanya dieksekusi jika file tidak di-include
if (!defined('IS_LOGIC_CALL')) {
    require_once 'template_header.php';
    if ($_SESSION['role'] !== 'admin') {
        echo "<p class='content-section'>Akses ditolak.</p>";
        require_once 'template_footer.php';
        exit;
    }
    $laporan_data = getInventoryRealizationReportData($koneksi);
?>

<header class="main-header">
    <h1>Laporan Realisasi Persediaan</h1>
    <p>Laporan ini menampilkan ringkasan total pergerakan barang dari awal hingga saat ini.</p>
</header>

<section class="content-section">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="action-bar" style="display:flex; justify-content:flex-end; gap: 10px; margin-bottom: 20px;">
        <form action="proses_tutup_buku.php" method="POST" onsubmit="return confirm('PERINGATAN!\n\nProses ini akan MENGHAPUS SEMUA riwayat transaksi dan membuat saldo awal baru.\nTINDAKAN INI TIDAK BISA DIBATALKAN.\n\nAnda yakin ingin melanjutkan?');" style="margin-right: auto;">
            <button type="submit" class="btn btn-danger">Tutup Buku</button>
        </form>
        <a href="cetak_laporan_realisasi.php" target="_blank" class="btn btn-secondary">Cetak</a>
        <a href="download_laporan_realisasi.php" class="btn btn-primary">Download (CSV)</a>
    </div>

    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Nama Barang</th>
                    <th rowspan="2">Spesifikasi</th>
                    <th rowspan="2">Satuan</th>
                    <th colspan="3">Saldo Awal</th>
                    <th colspan="3">Penerimaan (Total)</th>
                    <th colspan="3">Pengeluaran</th>
                    <th colspan="3">Saldo Akhir</th>
                    <th rowspan="2">Tgl Perolehan Terakhir</th>
                    <th rowspan="2">Bentuk Kontrak</th>
                    <th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Jml</th>
                    <th>Harga Batch Aktif</th>
                    <th>Nilai Total</th>
                    <th>Jml</th>
                    <th>Harga Keluar Terakhir</th>
                    <th>Nilai Total</th>
                    <th>Jml</th>
                    <th>Harga Batch Aktif</th>
                    <th>Nilai Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                <tr>
                    <td colspan="19" class="text-center">Tidak ada data.</td>
                </tr>
                <?php else: $no = 1; foreach ($laporan_data as $item): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><a href="kartu_stok.php?id_produk=<?php echo $item['id_produk']; ?>"
                            target="_blank"><?php echo htmlspecialchars($item['nama_kategori']); ?></a></td>
                    <td><?php echo htmlspecialchars($item['spesifikasi']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_awal_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['penerimaan_jumlah_total']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['harga_batch_aktif']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_nilai_total']); ?></td>
                    <td class="text-center"><?php echo number_format($item['pengeluaran_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_harga_terakhir']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_akhir_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_nilai']); ?></td>
                    <td><?php echo $item['tgl_perolehan'] != '-' ? date('d-m-Y', strtotime($item['tgl_perolehan'])) : '-'; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['bentuk_kontrak']); ?></td>
                    <td><?php echo htmlspecialchars($item['nama_penyedia']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>
<?php } // Penutup IS_LOGIC_CALL ?>