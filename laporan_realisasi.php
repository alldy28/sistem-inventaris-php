<?php
// Bagian 1: Inisialisasi dan Definisi Fungsi (selalu berjalan)
$page_title = 'Laporan Realisasi Persediaan';
$active_page = 'laporan_realisasi';

// Pindahkan require_once 'template_header.php' ke dalam blok kondisional
// agar tidak dieksekusi saat file ini di-include oleh script lain.

// Fungsi ini sekarang menjadi pusat logika dan berada di file utama.
function getInventoryRealizationReportData(mysqli $koneksi): array
{
    // ... (Isi fungsi sama persis seperti sebelumnya, tidak perlu diubah) ...
    // Query efisien yang menggabungkan semua kebutuhan data
    $sql = "
        SELECT
            p.id AS id_produk, p.spesifikasi, p.satuan, kp.nama_kategori,
            sa.jumlah_awal, sa.harga_beli AS harga_awal,
            penerimaan.total_jumlah AS total_penerimaan_jumlah,
            penerimaan.total_nilai AS total_penerimaan_nilai,
            pengeluaran.total_jumlah AS total_pengeluaran_jumlah,
            pengeluaran.total_nilai AS total_pengeluaran_nilai,
            penerimaan_terakhir.tanggal_penerimaan, penerimaan_terakhir.bentuk_kontrak,
            penerimaan_terakhir.nama_penyedia, penerimaan_terakhir.harga_satuan AS harga_penerimaan_terakhir
        FROM produk p
        JOIN kategori_produk kp ON p.id_kategori = kp.id
        LEFT JOIN (
            SELECT sb.id_produk, sb.jumlah_awal, sb.harga_beli FROM stok_batch sb
            INNER JOIN (SELECT id_produk, MIN(id) AS first_batch_id FROM stok_batch GROUP BY id_produk) first_batches ON sb.id = first_batches.first_batch_id
        ) AS sa ON p.id = sa.id_produk
        LEFT JOIN (
            SELECT id_produk, SUM(jumlah) AS total_jumlah, SUM(jumlah * harga_satuan) AS total_nilai FROM penerimaan GROUP BY id_produk
        ) AS penerimaan ON p.id = penerimaan.id_produk
        LEFT JOIN (
            SELECT dp.id_produk, SUM(dp.jumlah) AS total_jumlah, SUM(dp.nilai_keluar_fifo) AS total_nilai FROM detail_permintaan dp
            JOIN permintaan per ON dp.id_permintaan = per.id WHERE per.status = 'Disetujui' GROUP BY dp.id_produk
        ) AS pengeluaran ON p.id = pengeluaran.id_produk
        LEFT JOIN (
            SELECT p1.id_produk, p1.tanggal_penerimaan, p1.bentuk_kontrak, p1.nama_penyedia, p1.harga_satuan FROM penerimaan p1
            INNER JOIN (SELECT id_produk, MAX(tanggal_penerimaan) AS max_tanggal FROM penerimaan GROUP BY id_produk) p2 ON p1.id_produk = p2.id_produk AND p1.tanggal_penerimaan = p2.max_tanggal
        ) AS penerimaan_terakhir ON p.id = penerimaan_terakhir.id_produk
        HAVING total_penerimaan_jumlah > 0 OR total_pengeluaran_jumlah > 0
        ORDER BY kp.nama_kategori, p.spesifikasi ASC
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan_data = [];
    while ($row = $result->fetch_assoc()) {
        $saldo_awal_jumlah = $row['jumlah_awal'] ?? 0;
        $saldo_awal_harga  = $row['harga_awal'] ?? 0;
        $pengeluaran_jumlah = $row['total_pengeluaran_jumlah'] ?? 0;
        $pengeluaran_nilai  = $row['total_pengeluaran_nilai'] ?? 0;
        $saldo_akhir_jumlah = ($row['total_penerimaan_jumlah'] ?? 0) - $pengeluaran_jumlah;
        $saldo_akhir_nilai  = ($row['total_penerimaan_nilai'] ?? 0) - $pengeluaran_nilai;
        $saldo_akhir_harga_avg = ($saldo_akhir_jumlah > 0) ? $saldo_akhir_nilai / $saldo_akhir_jumlah : 0;
        
        $laporan_data[] = [
            'id_produk'               => $row['id_produk'], 'nama_kategori'           => $row['nama_kategori'],
            'spesifikasi'             => $row['spesifikasi'], 'satuan'                  => $row['satuan'],
            'saldo_awal_jumlah'       => $saldo_awal_jumlah, 'saldo_awal_harga'        => $saldo_awal_harga,
            'saldo_awal_nilai'        => $saldo_awal_jumlah * $saldo_awal_harga,
            'penerimaan_jumlah_total' => $row['total_penerimaan_jumlah'] ?? 0,
            'penerimaan_nilai_total'  => $row['total_penerimaan_nilai'] ?? 0,
            'penerimaan_harga_acuan'  => $row['harga_penerimaan_terakhir'] ?? 0,
            'pengeluaran_jumlah'      => $pengeluaran_jumlah, 'pengeluaran_nilai'       => $pengeluaran_nilai,
            'saldo_akhir_jumlah'      => $saldo_akhir_jumlah, 'saldo_akhir_harga'       => $saldo_akhir_harga_avg,
            'saldo_akhir_nilai'       => $saldo_akhir_nilai,
            'tgl_perolehan'           => $row['tanggal_penerimaan'] ?? '-', 'bentuk_kontrak'          => $row['bentuk_kontrak'] ?? '-',
            'nama_penyedia'           => $row['nama_penyedia'] ?? '-',
        ];
    }
    $stmt->close();
    return $laporan_data;
}


// Bagian 2: Logika Tampilan (Hanya berjalan jika file ini diakses langsung)
// Kita cek apakah file ini di-include oleh file lain. Jika tidak, tampilkan HTML.
if (!defined('IS_LOGIC_CALL')) {
    require_once 'template_header.php';

    // Keamanan: Pastikan peran pengguna adalah admin.
    if ($_SESSION['role'] !== 'admin') {
        echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
        require_once 'template_footer.php';
        exit;
    }

    // Panggil fungsi untuk mendapatkan data
    $laporan_data = getInventoryRealizationReportData($koneksi);
?>

<header class="main-header">
    <h1>Laporan Realisasi Persediaan</h1>
    <p>Laporan ini menampilkan ringkasan total pergerakan barang dari awal hingga saat ini.</p>
</header>
<section class="content-section">
    <div class="action-bar" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;">
        <a href="cetak_laporan_realisasi.php" target="_blank" class="btn btn-secondary">Cetak Laporan</a>
        <a href="download_laporan_realisasi.php" class="btn btn-primary">Download (CSV)</a>
    </div>

    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th><th rowspan="2">Nama Barang</th><th rowspan="2">Spesifikasi</th><th rowspan="2">Satuan</th>
                    <th colspan="3">Saldo Awal</th><th colspan="3">Penerimaan (Total)</th>
                    <th colspan="3">Pengeluaran</th><th colspan="3">Saldo Akhir</th>
                    <th rowspan="2">Tgl Perolehan Terakhir</th><th rowspan="2">Bentuk Kontrak</th><th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga</th><th>Nilai Total</th>
                    <th>Jml</th><th>Harga</th><th>Nilai Total</th>
                    <th>Jml</th><th>Harga</th><th>Nilai Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr><td colspan="19" class="text-center">Tidak ada data pergerakan barang.</td></tr>
                <?php else: $no = 1; foreach ($laporan_data as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><a href="kartu_stok.php?id_produk=<?php echo $item['id_produk']; ?>" target="_blank"><?php echo htmlspecialchars($item['nama_kategori']); ?></a></td>
                    <td><?php echo htmlspecialchars($item['spesifikasi']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_awal_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['penerimaan_jumlah_total']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_harga_acuan']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_nilai_total']); ?></td>
                    <td class="text-center"><?php echo number_format($item['pengeluaran_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_harga_acuan']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_akhir_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_nilai']); ?></td>
                    <td><?php echo $item['tgl_perolehan'] != '-' ? date('d-m-Y', strtotime($item['tgl_perolehan'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($item['bentuk_kontrak']); ?></td>
                    <td><?php echo htmlspecialchars($item['nama_penyedia']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
    require_once 'template_footer.php';
} // Akhir dari blok kondisional
?>