<?php
$page_title = 'Laporan Realisasi Persediaan';
$active_page = 'laporan_realisasi'; 
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

$tgl_mulai_str = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai_str = $_GET['tgl_selesai'] ?? date('Y-m-t');

// <<<--- PERBAIKAN KUNCI ADA DI SINI ---<<<
// Siapkan variabel tanggal DAN waktu secara terpisah SEBELUM digunakan
$tgl_mulai_datetime = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai_datetime = $tgl_selesai_str . ' 23:59:59';


// --- LOGIKA PENGAMBILAN DATA ---

// 1. Ambil semua produk
$semua_produk = [];
$result_produk = $koneksi->query("SELECT pr.*, kp.nama_kategori, kp.nusp_id FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
while($p = $result_produk->fetch_assoc()) { $semua_produk[$p['id']] = $p; }

// 2. Hitung pergerakan SEBELUM periode filter
$penerimaan_sebelum = [];
$stmt1 = $koneksi->prepare("SELECT id_produk, SUM(jumlah) as total FROM penerimaan WHERE tanggal_penerimaan < ? GROUP BY id_produk");
$stmt1->bind_param("s", $tgl_mulai_str); $stmt1->execute(); $res1 = $stmt1->get_result();
while($p = $res1->fetch_assoc()) { $penerimaan_sebelum[$p['id_produk']] = $p['total']; }

$pengeluaran_sebelum = [];
$stmt2 = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) as total FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses < ? GROUP BY dp.id_produk");
$stmt2->bind_param("s", $tgl_mulai_str); $stmt2->execute(); $res2 = $stmt2->get_result();
while($p = $res2->fetch_assoc()) { $pengeluaran_sebelum[$p['id_produk']] = $p['total']; }

// 3. Hitung pergerakan DI DALAM periode filter
$penerimaan_periode = [];
$stmt3 = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt3->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime); // Gunakan variabel yang sudah disiapkan
$stmt3->execute(); $res3 = $stmt3->get_result();
while($p = $res3->fetch_assoc()) { $penerimaan_periode[$p['id_produk']] = $p; }

$pengeluaran_periode = [];
$stmt4 = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.nilai_keluar_fifo) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt4->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime); // Gunakan variabel yang sudah disiapkan
$stmt4->execute(); $res4 = $stmt4->get_result();
while($p = $res4->fetch_assoc()) { $pengeluaran_periode[$p['id_produk']] = $p; }

// 4. Ambil detail penerimaan TERAKHIR dalam periode
$penerimaan_terakhir = [];
$sql_terakhir = "SELECT p1.id_produk, p1.tanggal_penerimaan, p1.bentuk_kontrak, p1.nama_penyedia FROM penerimaan p1 INNER JOIN (SELECT id_produk, MAX(tanggal_penerimaan) as max_tanggal FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk) p2 ON p1.id_produk = p2.id_produk AND p1.tanggal_penerimaan = p2.max_tanggal";
$stmt5 = $koneksi->prepare($sql_terakhir);
$stmt5->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime); // Gunakan variabel yang sudah disiapkan
$stmt5->execute(); $res5 = $stmt5->get_result();
while($p = $res5->fetch_assoc()) { $penerimaan_terakhir[$p['id_produk']] = $p; }

// 5. Gabungkan semua data
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $terima_sblm = $penerimaan_sebelum[$id_produk] ?? 0;
    $keluar_sblm = $pengeluaran_sebelum[$id_produk] ?? 0;
    $saldo_awal_jumlah = ($produk['stok_awal'] + $terima_sblm) - $keluar_sblm;
    $saldo_awal_harga = $produk['harga_awal'];
    $saldo_awal_total = $saldo_awal_jumlah * $saldo_awal_harga;

    $penerimaan_jumlah = $penerimaan_periode[$id_produk]['total_terima'] ?? 0;
    $penerimaan_nilai = $penerimaan_periode[$id_produk]['total_nilai_terima'] ?? 0;
    $avg_harga_terima = ($penerimaan_jumlah > 0) ? $penerimaan_nilai / $penerimaan_jumlah : 0;
    
    $pengeluaran_jumlah = $pengeluaran_periode[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_periode[$id_produk]['total_nilai_keluar'] ?? 0;
    $avg_harga_keluar = ($pengeluaran_jumlah > 0) ? $pengeluaran_nilai / $pengeluaran_jumlah : 0;
    
    $saldo_akhir_jumlah = $saldo_awal_jumlah + $penerimaan_jumlah - $pengeluaran_jumlah;
    $saldo_akhir_harga = $produk['harga'];
    $saldo_akhir_total = $saldo_akhir_jumlah * $saldo_akhir_harga;

    if ($saldo_awal_jumlah != 0 || $penerimaan_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [
            'nama_kategori' => $produk['nama_kategori'], 'spesifikasi' => $produk['spesifikasi'], 'satuan' => $produk['satuan'],
            'saldo_awal_jumlah' => $saldo_awal_jumlah, 'saldo_awal_harga' => $saldo_awal_harga, 'saldo_awal_total' => $saldo_awal_total,
            'penerimaan_jumlah' => $penerimaan_jumlah, 'penerimaan_harga' => $avg_harga_terima, 'penerimaan_total' => $penerimaan_nilai,
            'pengeluaran_jumlah' => $pengeluaran_jumlah, 'pengeluaran_harga' => $avg_harga_keluar, 'pengeluaran_total' => $pengeluaran_nilai,
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga' => $saldo_akhir_harga, 'saldo_akhir_total' => $saldo_akhir_total,
            'tgl_perolehan' => $penerimaan_terakhir[$id_produk]['tanggal_penerimaan'] ?? '-',
            'bentuk_kontrak' => $penerimaan_terakhir[$id_produk]['bentuk_kontrak'] ?? '-',
            'nama_penyedia' => $penerimaan_terakhir[$id_produk]['nama_penyedia'] ?? '-',
        ];
    }
}
?>

<header class="main-header">
    <h1>Laporan Realisasi Persediaan</h1>
    <p>Laporan detail pergerakan dan informasi pengadaan barang.</p>
</header>
<section class="content-section">
    <div class="search-form-container">
        <form action="laporan_realisasi.php" method="GET">
            <div class="form-group" style="flex:1;"><label for="tgl_mulai">Dari Tanggal</label><input type="date"
                    name="tgl_mulai" class="form-control" value="<?php echo htmlspecialchars($tgl_mulai_str); ?>"></div>
            <div class="form-group" style="flex:1;"><label for="tgl_selesai">Sampai Tanggal</label><input type="date"
                    name="tgl_selesai" class="form-control" value="<?php echo htmlspecialchars($tgl_selesai_str); ?>">
            </div>
            <div class="form-actions" style="margin-top:28px;"><button type="submit"
                    class="btn btn-primary">Tampilkan</button></div>
        </form>
    </div>

    <div class="action-bar"
        style="text-align: right; display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <?php
        // Buat query string dari filter tanggal yang sedang aktif
        $report_params = http_build_query([
            'tgl_mulai' => $tgl_mulai_str,
            'tgl_selesai' => $tgl_selesai_str
        ]);
    ?>
        <a href="cetak_laporan_realisasi.php?<?php echo $report_params; ?>" target="_blank"
            class="btn btn-secondary">Cetak Laporan</a>
        <a href="download_laporan_realisasi.php?<?php echo $report_params; ?>" class="btn btn-primary">Download
            (CSV)</a>
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
                    <th colspan="3">Penerimaan</th>
                    <th colspan="3">Pengeluaran</th>
                    <th colspan="3">Saldo Akhir</th>
                    <th rowspan="2">Tgl Perolehan</th>
                    <th rowspan="2">Bentuk Kontrak</th>
                    <th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                <tr>
                    <td colspan="19" class="text-center">Tidak ada data pada periode ini.</td>
                </tr>
                <?php else: $no = 1; foreach ($laporan_data as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($item['nama_kategori']); ?></td>
                    <td><?php echo htmlspecialchars($item['spesifikasi']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                    <td class="text-center"><?php echo $item['saldo_awal_jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_total']); ?></td>
                    <td class="text-center"><?php echo $item['penerimaan_jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_total']); ?></td>
                    <td class="text-center"><?php echo $item['pengeluaran_jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_total']); ?></td>
                    <td class="text-center"><?php echo $item['saldo_akhir_jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_total']); ?></td>
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