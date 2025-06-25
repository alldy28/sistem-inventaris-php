<?php
require_once 'koneksi.php';
session_start();

// Keamanan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['tgl_mulai'])) {
    die("Akses ditolak atau parameter tidak lengkap.");
}

// Mengambil parameter filter dari URL
$tgl_mulai_str = $_GET['tgl_mulai'];
$tgl_selesai_str = $_GET['tgl_selesai'];

// <<-- PERBAIKAN KUNCI: Siapkan variabel tanggal-waktu SEBELUM digunakan -->>
$tgl_mulai_datetime = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai_datetime = $tgl_selesai_str . ' 23:59:59';

// 1. Ambil semua produk
$semua_produk = [];
$result_produk = $koneksi->query("SELECT pr.*, kp.nama_kategori, kp.nusp_id FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
while($p = $result_produk->fetch_assoc()) { $semua_produk[$p['id']] = $p; }

// 2. Hitung pergerakan SEBELUM periode filter
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

// 3. Hitung pergerakan DI DALAM periode filter
$penerimaan_periode = [];
$stmt_penerimaan_periode = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
// Gunakan variabel yang sudah disiapkan
$stmt_penerimaan_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt_penerimaan_periode->execute();
$result_penerimaan_periode = $stmt_penerimaan_periode->get_result();
while($p = $result_penerimaan_periode->fetch_assoc()) { $penerimaan_periode[$p['id_produk']] = $p; }

$pengeluaran_periode = [];
$stmt_pengeluaran_periode = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.nilai_keluar_fifo) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
// Gunakan variabel yang sudah disiapkan
$stmt_pengeluaran_periode->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt_pengeluaran_periode->execute();
$result_pengeluaran_periode = $stmt_pengeluaran_periode->get_result();
while($p = $result_pengeluaran_periode->fetch_assoc()) { $pengeluaran_periode[$p['id_produk']] = $p; }

// 4. Gabungkan semua data
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $terima_sblm = $penerimaan_sebelum[$id_produk] ?? 0;
    $keluar_sblm = $pengeluaran_sebelum[$id_produk] ?? 0;
    $saldo_awal_jumlah = ($produk['stok_awal'] + $terima_sblm) - $keluar_sblm;
    $saldo_awal_harga_satuan = $produk['harga_awal'];
    $saldo_awal_nilai = $saldo_awal_jumlah * $saldo_awal_harga_satuan;
    $penerimaan_periode_jumlah = $penerimaan_periode[$id_produk]['total_terima'] ?? 0;
    $penerimaan_periode_nilai = $penerimaan_periode[$id_produk]['total_nilai_terima'] ?? 0;
    $pengeluaran_jumlah = $pengeluaran_periode[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_periode[$id_produk]['total_nilai_keluar'] ?? 0;
    $tersedia_jumlah = $saldo_awal_jumlah + $penerimaan_periode_jumlah;
    $tersedia_nilai = $saldo_awal_nilai + $penerimaan_periode_nilai;
    $tersedia_harga_satuan = ($tersedia_jumlah > 0) ? $tersedia_nilai / $tersedia_jumlah : 0;
    $saldo_akhir_jumlah = $tersedia_jumlah - $pengeluaran_jumlah;
    $saldo_akhir_nilai = $saldo_akhir_jumlah * $produk['harga'];
    if ($saldo_awal_jumlah != 0 || $penerimaan_periode_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [ 'nama_lengkap_produk' => $produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')', 'saldo_awal_jumlah' => $saldo_awal_jumlah, 'saldo_awal_harga_satuan' => $saldo_awal_harga_satuan, 'saldo_awal_nilai' => $saldo_awal_nilai, 'penerimaan_periode_jumlah' => $penerimaan_periode_jumlah, 'penerimaan_periode_nilai' => $penerimaan_periode_nilai, 'tersedia_jumlah' => $tersedia_jumlah, 'tersedia_harga_satuan' => $tersedia_harga_satuan, 'tersedia_nilai' => $tersedia_nilai, 'pengeluaran_jumlah' => $pengeluaran_jumlah, 'pengeluaran_nilai' => $pengeluaran_nilai, 'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga_satuan' => $produk['harga'], 'saldo_akhir_nilai' => $saldo_akhir_nilai, ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Persediaan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; } .container { width: 100%; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 14pt; } h2 { font-size: 12pt; }
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
            <h1>LAPORAN PERSEDIAAN BARANG</h1>
            <p>Periode: <?php echo date('d M Y', strtotime($tgl_mulai_str)) . ' - ' . date('d M Y', strtotime($tgl_selesai_str)); ?></p>
        </div>
        <table>
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
                        <td><?php echo htmlspecialchars($item['nama_lengkap_produk']); ?></td>
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
        <div class="footer">Dicetak pada: <?php echo date('d M Y, H:i:s'); ?> oleh <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
    </div>
</body>
</html>