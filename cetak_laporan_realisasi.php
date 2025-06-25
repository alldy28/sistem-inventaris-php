<?php
require_once 'koneksi.php';
session_start();

// Keamanan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['tgl_mulai'])) {
    die("Akses ditolak atau parameter tidak lengkap.");
}

// Logika pengambilan data sama persis dengan laporan_realisasi.php
$tgl_mulai_str = $_GET['tgl_mulai'];
$tgl_selesai_str = $_GET['tgl_selesai'];
$tgl_mulai_datetime = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai_datetime = $tgl_selesai_str . ' 23:59:59';
// ... (Salin seluruh blok logika PHP dari laporan_realisasi.php untuk menghitung $laporan_data) ...
$semua_produk = [];
$result_produk = $koneksi->query("SELECT pr.*, kp.nama_kategori, kp.nusp_id FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
while($p = $result_produk->fetch_assoc()) { $semua_produk[$p['id']] = $p; }
$penerimaan_sebelum = [];
$stmt_penerimaan_sebelum = $koneksi->prepare("SELECT id_produk, SUM(jumlah) as total FROM penerimaan WHERE tanggal_penerimaan < ? GROUP BY id_produk");
$stmt_penerimaan_sebelum->bind_param("s", $tgl_mulai_str);
$stmt_penerimaan_sebelum->execute();
$result_penerimaan_sebelum = $stmt_penerimaan_sebelum->get_result();
while($p = $result_penerimaan_sebelum->fetch_assoc()) { $penerimaan_sebelum[$p['id_produk']] = $p['total']; }
$pengeluaran_sebelum = [];
$stmt_pengeluaran_sebelum = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) as total FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses < ? GROUP BY dp.id_produk");
$stmt_pengeluaran_sebelum->bind_param("s", $tgl_mulai_str);
$stmt_pengeluaran_sebelum->execute();
$result_pengeluaran_sebelum = $stmt_pengeluaran_sebelum->get_result();
while($p = $result_pengeluaran_sebelum->fetch_assoc()) { $pengeluaran_sebelum[$p['id_produk']] = $p['total']; }
$penerimaan_periode = [];
$stmt_penerimaan_periode = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt_penerimaan_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt_penerimaan_periode->execute();
$result_penerimaan_periode = $stmt_penerimaan_periode->get_result();
while($p = $result_penerimaan_periode->fetch_assoc()) { $penerimaan_periode[$p['id_produk']] = $p; }
$pengeluaran_periode = [];
$stmt_pengeluaran_periode = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.nilai_keluar_fifo) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt_pengeluaran_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt_pengeluaran_periode->execute();
$result_pengeluaran_periode = $stmt_pengeluaran_periode->get_result();
while($p = $result_pengeluaran_periode->fetch_assoc()) { $pengeluaran_periode[$p['id_produk']] = $p; }
$penerimaan_terakhir = [];
$sql_terakhir = "SELECT p1.id_produk, p1.tanggal_penerimaan, p1.bentuk_kontrak, p1.nama_penyedia FROM penerimaan p1 INNER JOIN (SELECT id_produk, MAX(tanggal_penerimaan) as max_tanggal FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk) p2 ON p1.id_produk = p2.id_produk AND p1.tanggal_penerimaan = p2.max_tanggal";
$stmt_terakhir = $koneksi->prepare($sql_terakhir);
$stmt_terakhir->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt_terakhir->execute(); $res5 = $stmt_terakhir->get_result();
while($p = $res5->fetch_assoc()) { $penerimaan_terakhir[$p['id_produk']] = $p; }
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
        $laporan_data[] = [ 'nama_kategori' => $produk['nama_kategori'], 'spesifikasi' => $produk['spesifikasi'], 'satuan' => $produk['satuan'], 'saldo_awal_jumlah' => $saldo_awal_jumlah, 'saldo_awal_harga' => $saldo_awal_harga, 'saldo_awal_total' => $saldo_awal_total, 'penerimaan_jumlah' => $penerimaan_jumlah, 'penerimaan_harga' => $avg_harga_terima, 'penerimaan_total' => $penerimaan_nilai, 'pengeluaran_jumlah' => $pengeluaran_jumlah, 'pengeluaran_harga' => $avg_harga_keluar, 'pengeluaran_total' => $pengeluaran_nilai, 'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga' => $saldo_akhir_harga, 'saldo_akhir_total' => $saldo_akhir_total, 'tgl_perolehan' => $penerimaan_terakhir[$id_produk]['tanggal_penerimaan'] ?? '-', 'bentuk_kontrak' => $penerimaan_terakhir[$id_produk]['bentuk_kontrak'] ?? '-', 'nama_penyedia' => $penerimaan_terakhir[$id_produk]['nama_penyedia'] ?? '-', ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Realisasi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; } .container { width: 100%; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        h1 { margin: 0; font-size: 14pt; } p {margin: 2px 0;}
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; word-wrap:break-word; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; } .text-center { text-align: center; }
        .footer { margin-top: 25px; font-size: 8pt; }
        @media print { @page { size: A4 landscape; margin: 1cm; } body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>LAPORAN REALISASI PERSEDIAAN</h1>
            <p>Periode: <?php echo date('d M Y', strtotime($tgl_mulai_str)) . ' s/d ' . date('d M Y', strtotime($tgl_selesai_str)); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">No</th><th rowspan="2">Nama Barang</th><th rowspan="2">Spesifikasi</th><th rowspan="2">Satuan</th>
                    <th colspan="3">Saldo Awal</th><th colspan="3">Penerimaan</th>
                    <th colspan="3">Pengeluaran</th><th colspan="3">Saldo Akhir</th>
                    <th rowspan="2">Tgl Perolehan</th><th rowspan="2">Bentuk Kontrak</th><th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Total</th><th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga</th><th>Total</th><th>Jml</th><th>Harga</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr><td colspan="19" class="text-center">Tidak ada data pada periode ini.</td></tr>
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
                    <td class="text-center"><?php echo $item['tgl_perolehan'] != '-' ? date('d-m-Y', strtotime($item['tgl_perolehan'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($item['bentuk_kontrak']); ?></td>
                    <td><?php echo htmlspecialchars($item['nama_penyedia']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>