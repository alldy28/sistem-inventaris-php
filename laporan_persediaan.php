<?php
$page_title = 'Laporan Persediaan';
$active_page = 'laporan_persediaan'; 
require_once 'template_header.php';

// Pastikan hanya admin yang bisa mengakses
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

$tgl_mulai_str = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai_str = $_GET['tgl_selesai'] ?? date('Y-m-t');
$tgl_mulai = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai = $tgl_selesai_str . ' 23:59:59';

// --- LOGIKA BARU BERBASIS SALDO AWAL ---

// 1. Ambil semua produk beserta data saldo awalnya
$semua_produk = [];
$result_produk = $koneksi->query("SELECT * FROM produk ORDER BY nama_barang ASC");
while($p = $result_produk->fetch_assoc()) {
    $semua_produk[$p['id']] = $p;
}

// 2. Ambil data PENERIMAAN dalam periode, kelompokkan per id_produk
$penerimaan_data = [];
$stmt_penerimaan = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt_penerimaan->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt_penerimaan->execute();
$result_penerimaan = $stmt_penerimaan->get_result();
while($p = $result_penerimaan->fetch_assoc()) {
    $penerimaan_data[$p['id_produk']] = $p;
}

// 3. Ambil data PENGELUARAN dalam periode, kelompokkan per id_produk
$pengeluaran_data = [];
$stmt_pengeluaran = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.jumlah * dp.harga_saat_minta) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt_pengeluaran->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt_pengeluaran->execute();
$result_pengeluaran = $stmt_pengeluaran->get_result();
while($p = $result_pengeluaran->fetch_assoc()) {
    $pengeluaran_data[$p['id_produk']] = $p;
}

// 4. Siapkan data laporan dengan MENGHITUNG MAJU
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    
    // Ambil Saldo Awal langsung dari DB
    $saldo_awal_jumlah = $produk['stok_awal'];
    $saldo_awal_harga_satuan = $produk['harga_awal'];
    $saldo_awal_nilai = $saldo_awal_jumlah * $saldo_awal_harga_satuan;

    $penerimaan_jumlah = $penerimaan_data[$id_produk]['total_terima'] ?? 0;
    $penerimaan_nilai = $penerimaan_data[$id_produk]['total_nilai_terima'] ?? 0;
    
    $pengeluaran_jumlah = $pengeluaran_data[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_data[$id_produk]['total_nilai_keluar'] ?? 0;

    // Hitung Saldo Akhir dengan rumus: Saldo Awal + Penerimaan - Pengeluaran
    $saldo_akhir_jumlah = $saldo_awal_jumlah + $penerimaan_jumlah - $pengeluaran_jumlah;
    // Nilai saldo akhir menggunakan harga terbaru dari produk
    $saldo_akhir_nilai = $saldo_akhir_jumlah * $produk['harga'];

    // Hanya tampilkan jika ada pergerakan atau stok
    if ($saldo_awal_jumlah != 0 || $penerimaan_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [
            'nama_barang' => $produk['nama_barang'],
            'saldo_awal_jumlah' => $saldo_awal_jumlah,
            'saldo_awal_harga_satuan' => $saldo_awal_harga_satuan,
            'saldo_awal_nilai' => $saldo_awal_nilai,
            'penerimaan_jumlah' => $penerimaan_jumlah,
            'penerimaan_nilai' => $penerimaan_nilai,
            'pengeluaran_jumlah' => $pengeluaran_jumlah,
            'pengeluaran_nilai' => $pengeluaran_nilai,
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah,
            'saldo_akhir_harga_satuan' => $produk['harga'], // Harga terbaru
            'saldo_akhir_nilai' => $saldo_akhir_nilai,
        ];
    }
}
?>

<header class="main-header">
    <h1>Laporan Persediaan Barang</h1>
    <p>Laporan pergerakan stok barang berdasarkan periode.</p>
</header>
<section class="content-section">
    <div class="action-bar" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
        <?php
            // Buat query string untuk tombol cetak dan download
            $report_params = http_build_query([
                'tgl_mulai' => $tgl_mulai_str,
                'tgl_selesai' => $tgl_selesai_str
                // Kita tidak perlu status untuk laporan persediaan
            ]);
        ?>
        <a href="cetak_laporan_persediaan.php?<?php echo $report_params; ?>" target="_blank" class="btn btn-secondary">Cetak Laporan</a>
        <a href="download_laporan_persediaan.php?<?php echo $report_params; ?>" class="btn btn-primary">Download (CSV)</a>
    </div>
    <div class="search-form-container">
        <form action="laporan_persediaan.php" method="GET">
            <div class="form-group" style="flex:1;"><label for="tgl_mulai">Dari Tanggal</label><input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" value="<?php echo htmlspecialchars($tgl_mulai_str); ?>"></div>
            <div class="form-group" style="flex:1;"><label for="tgl_selesai">Sampai Tanggal</label><input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control" value="<?php echo htmlspecialchars($tgl_selesai_str); ?>"></div>
            <div class="form-actions" style="margin-top:28px;"><button type="submit" class="btn btn-primary">Tampilkan Laporan</button><a href="laporan_persediaan.php" class="btn btn-secondary">Reset Bulan Ini</a></div>
        </form>
    </div>
    <div class="table-container">
        <p><strong>Menampilkan laporan untuk periode: <?php echo date('d M Y', strtotime($tgl_mulai_str)) . ' s/d ' . date('d M Y', strtotime($tgl_selesai_str)); ?></strong></p>
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle;">No.</th>
                    <th rowspan="2" style="vertical-align: middle;">Nama Barang</th>
                    <th colspan="3">Saldo Awal</th>
                    <th colspan="3">Penerimaan</th>
                    <th colspan="3">Pengeluaran</th>
                    <th colspan="3">Saldo Akhir</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga Satuan</th><th>Total Nilai</th>
                    <th>Jml</th><th>Harga Satuan</th><th>Total Nilai</th>
                    <th>Jml</th><th>Harga Satuan</th><th>Total Nilai</th>
                    <th>Jml</th><th>Harga Satuan</th><th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($laporan_data) > 0): $no = 1; ?>
                    <?php foreach ($laporan_data as $item): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                        <td><?php echo $item['saldo_awal_jumlah']; ?></td>
                        <td>Rp <?php echo number_format($item['saldo_awal_harga_satuan'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['saldo_awal_nilai'], 0, ',', '.'); ?></td>
                        <?php 
                            $avg_harga_terima = ($item['penerimaan_jumlah'] > 0) ? $item['penerimaan_nilai'] / $item['penerimaan_jumlah'] : 0;
                            $avg_harga_keluar = ($item['pengeluaran_jumlah'] > 0) ? $item['pengeluaran_nilai'] / $item['pengeluaran_jumlah'] : 0;
                        ?>
                        <td><?php echo $item['penerimaan_jumlah']; ?></td>
                        <td>Rp <?php echo number_format($avg_harga_terima, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['penerimaan_nilai'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['pengeluaran_jumlah']; ?></td>
                        <td>Rp <?php echo number_format($avg_harga_keluar, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['pengeluaran_nilai'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['saldo_akhir_jumlah']; ?></td>
                        <td>Rp <?php echo number_format($item['saldo_akhir_harga_satuan'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['saldo_akhir_nilai'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="14" style="text-align:center;">Tidak ada data pergerakan barang pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>