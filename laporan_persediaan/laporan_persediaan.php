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

// Ambil filter tanggal, beri nilai default jika kosong
$tgl_mulai_str = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai_str = $_GET['tgl_selesai'] ?? date('Y-m-t');

// PERBAIKAN KUNCI: Siapkan variabel tanggal dengan waktu di awal
$tgl_mulai_datetime = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai_datetime = $tgl_selesai_str . ' 23:59:59';

// --- LOGIKA PERHITUNGAN LAPORAN ---

// 1. Ambil semua produk beserta informasi kategorinya
$semua_produk = [];
$result_produk = $koneksi->query("SELECT pr.*, kp.nama_kategori, kp.nusp_id FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
while($p = $result_produk->fetch_assoc()) {
    $semua_produk[$p['id']] = $p;
}

// 2. Hitung total pergerakan SEBELUM periode filter (untuk Saldo Awal)
$penerimaan_sebelum = [];
$stmt_penerimaan_sebelum = $koneksi->prepare("SELECT id_produk, SUM(jumlah) as total FROM penerimaan WHERE tanggal_penerimaan < ? GROUP BY id_produk");
$stmt_penerimaan_sebelum->bind_param("s", $tgl_mulai_str);
$stmt_penerimaan_sebelum->execute();
$result_penerimaan_sebelum = $stmt_penerimaan_sebelum->get_result();
while($p = $result_penerimaan_sebelum->fetch_assoc()) {
    $penerimaan_sebelum[$p['id_produk']] = $p['total'];
}

$pengeluaran_sebelum = [];
$stmt_pengeluaran_sebelum = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) as total FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses < ? GROUP BY dp.id_produk");
$stmt_pengeluaran_sebelum->bind_param("s", $tgl_mulai_str);
$stmt_pengeluaran_sebelum->execute();
$result_pengeluaran_sebelum = $stmt_pengeluaran_sebelum->get_result();
while($p = $result_pengeluaran_sebelum->fetch_assoc()) {
    $pengeluaran_sebelum[$p['id_produk']] = $p['total'];
}

// 3. Hitung pergerakan DI DALAM periode filter
$penerimaan_periode = [];
$stmt_penerimaan_periode = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt_penerimaan_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime); // Gunakan variabel yang sudah disiapkan
$stmt_penerimaan_periode->execute();
$result_penerimaan_periode = $stmt_penerimaan_periode->get_result();
while($p = $result_penerimaan_periode->fetch_assoc()) {
    $penerimaan_periode[$p['id_produk']] = $p;
}

$pengeluaran_periode = [];
$stmt_pengeluaran_periode = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.nilai_keluar_fifo) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt_pengeluaran_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime); // Gunakan variabel yang sudah disiapkan
$stmt_pengeluaran_periode->execute();
$result_pengeluaran_periode = $stmt_pengeluaran_periode->get_result();
while($p = $result_pengeluaran_periode->fetch_assoc()) {
    $pengeluaran_periode[$p['id_produk']] = $p;
}

// 4. Gabungkan semua data untuk ditampilkan
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $terima_sblm = $penerimaan_sebelum[$id_produk] ?? 0;
    $keluar_sblm = $pengeluaran_sebelum[$id_produk] ?? 0;
    $saldo_awal_jumlah = ($produk['stok_awal'] + $terima_sblm) - $keluar_sblm;
    $saldo_awal_harga_satuan = $produk['harga_awal'];
    $saldo_awal_nilai = $saldo_awal_jumlah * $saldo_awal_harga_satuan;

    $penerimaan_periode_jumlah = $penerimaan_periode[$id_produk]['total_terima'] ?? 0;
    $penerimaan_periode_nilai = $penerimaan_periode[$id_produk]['total_nilai_terima'] ?? 0;
    
    $tersedia_jumlah = $saldo_awal_jumlah + $penerimaan_periode_jumlah;
    $tersedia_nilai = $saldo_awal_nilai + $penerimaan_periode_nilai;
    $tersedia_harga_satuan = ($tersedia_jumlah > 0) ? $tersedia_nilai / $tersedia_jumlah : 0;
    
    $pengeluaran_jumlah = $pengeluaran_periode[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_periode[$id_produk]['total_nilai_keluar'] ?? 0;
    
    $saldo_akhir_jumlah = $tersedia_jumlah - $pengeluaran_jumlah;
    $saldo_akhir_nilai = $saldo_akhir_jumlah * $produk['harga'];

    if ($saldo_awal_jumlah != 0 || $penerimaan_periode_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [ 
            'id_produk' => $id_produk, 'nama_lengkap_produk' => $produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')',
            'saldo_awal_jumlah' => $saldo_awal_jumlah, 'saldo_awal_harga_satuan' => $saldo_awal_harga_satuan, 'saldo_awal_nilai' => $saldo_awal_nilai, 
            'penerimaan_periode_jumlah' => $penerimaan_periode_jumlah, 'penerimaan_periode_nilai' => $penerimaan_periode_nilai,
            'tersedia_jumlah' => $tersedia_jumlah, 'tersedia_harga_satuan' => $tersedia_harga_satuan, 'tersedia_nilai' => $tersedia_nilai,
            'pengeluaran_jumlah' => $pengeluaran_jumlah, 'pengeluaran_nilai' => $pengeluaran_nilai, 
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga_satuan' => $produk['harga'], 'saldo_akhir_nilai' => $saldo_akhir_nilai, 
        ];
    }
}
?>

<header class="main-header">
    <h1>Laporan Persediaan Barang</h1>
    <p>Laporan pergerakan stok barang berdasarkan periode.</p>
</header>
<section class="content-section">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'tutup_buku_sukses'): ?>
    <div class="alert alert-success">
        Proses Tutup Buku Tahunan berhasil! Data transaksi telah direset dan Saldo Awal untuk periode baru telah ditetapkan.
    </div>
    <?php endif; ?>

    <div class="search-form-container">
        <form action="laporan_persediaan.php" method="GET">
            <div class="form-group" style="flex:1;"><label for="tgl_mulai">Dari Tanggal</label><input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" value="<?php echo htmlspecialchars($tgl_mulai_str); ?>"></div>
            <div class="form-group" style="flex:1;"><label for="tgl_selesai">Sampai Tanggal</label><input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control" value="<?php echo htmlspecialchars($tgl_selesai_str); ?>"></div>
            <div class="form-actions" style="margin-top:28px;"><button type="submit" class="btn btn-primary">Tampilkan Laporan</button><a href="laporan_persediaan.php" class="btn btn-secondary">Reset Bulan Ini</a></div>
        </form>
    </div>

    <div class="action-bar" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <?php $report_params = http_build_query([ 'tgl_mulai' => $tgl_mulai_str, 'tgl_selesai' => $tgl_selesai_str ]); ?>
        <a href="cetak_laporan_persediaan.php?<?php echo $report_params; ?>" target="_blank" class="btn btn-secondary">Cetak Laporan</a>
        <a href="download_laporan_persediaan.php?<?php echo $report_params; ?>" class="btn btn-primary">Download (CSV)</a>
        <form action="proses_tutup_buku.php" method="POST" onsubmit="return confirm('PERINGATAN SUPER PENTING!\n\nAnda akan melakukan proses TUTUP BUKU TAHUNAN.\nProses ini akan:\n1. Menjadikan Saldo Akhir saat ini sebagai Saldo Awal tahun berikutnya.\n2. MENGHAPUS SEMUA data transaksi (penerimaan, pengeluaran, batch).\n\nPASTIKAN ANDA SUDAH MENGUNDUH LAPORAN TAHUN INI SEBELUM MELANJUTKAN.\n\nAksi ini TIDAK BISA DIURUNGKAN. Lanjutkan?');">
            <input type="hidden" name="tgl_akhir_periode" value="<?php echo htmlspecialchars($tgl_selesai_str); ?>">
            <button type="submit" class="btn btn-danger">Tutup Buku Tahunan</button>
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
                    <th colspan="2">Penerimaan (Periode)</th>
                    <th colspan="3">Barang Tersedia</th>
                    <th colspan="3">Pengeluaran (Periode)</th>
                    <th colspan="3">Saldo Akhir</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                    <th>Jml</th><th>Nilai</th>
                    <th>Jml</th><th>Harga Avg.</th><th>Nilai</th>
                    <th>Jml</th><th>Harga Avg.</th><th>Nilai</th>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($laporan_data) > 0): $no = 1; ?>
                    <?php foreach ($laporan_data as $item): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><a href="kartu_stok.php?id_produk=<?php echo $item['id_produk']; ?>" target="_blank"><?php echo htmlspecialchars($item['nama_lengkap_produk']); ?></a></td>
                        <td class="text-center"><?php echo $item['saldo_awal_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga_satuan'], 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_awal_nilai'], 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $item['penerimaan_periode_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['penerimaan_periode_nilai'], 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $item['tersedia_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['tersedia_harga_satuan'], 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['tersedia_nilai'], 2, ',', '.'); ?></td>
                        <?php $avg_harga_keluar = ($item['pengeluaran_jumlah'] > 0) ? $item['pengeluaran_nilai'] / $item['pengeluaran_jumlah'] : 0; ?>
                        <td class="text-center"><?php echo $item['pengeluaran_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($avg_harga_keluar, 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['pengeluaran_nilai'], 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $item['saldo_akhir_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga_satuan'], 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_nilai'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="17" style="text-align:center;">Tidak ada data pergerakan barang pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>