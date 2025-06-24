<?php
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// ===================================================================
// BAGIAN INI SAMA PERSIS DENGAN `laporan_persediaan.php`
// Untuk memastikan data yang dicetak konsisten dengan yang ditampilkan
// ===================================================================

$tgl_mulai_str = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai_str = $_GET['tgl_selesai'] ?? date('Y-m-t');
$tgl_mulai = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai = $tgl_selesai_str . ' 23:59:59';

$semua_produk = [];
$result_produk = $koneksi->query("SELECT * FROM produk ORDER BY nama_barang ASC");
while($p = $result_produk->fetch_assoc()) {
    $semua_produk[$p['id']] = $p;
}
$penerimaan_data = [];
$stmt_penerimaan = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt_penerimaan->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt_penerimaan->execute();
$result_penerimaan = $stmt_penerimaan->get_result();
while($p = $result_penerimaan->fetch_assoc()) {
    $penerimaan_data[$p['id_produk']] = $p;
}
$pengeluaran_data = [];
$stmt_pengeluaran = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.jumlah * dp.harga_saat_minta) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt_pengeluaran->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt_pengeluaran->execute();
$result_pengeluaran = $stmt_pengeluaran->get_result();
while($p = $result_pengeluaran->fetch_assoc()) {
    $pengeluaran_data[$p['id_produk']] = $p;
}

$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $saldo_awal_jumlah = $produk['stok_awal'];
    $saldo_awal_harga_satuan = $produk['harga_awal'];
    $saldo_awal_nilai = $saldo_awal_jumlah * $saldo_awal_harga_satuan;
    $penerimaan_jumlah = $penerimaan_data[$id_produk]['total_terima'] ?? 0;
    $penerimaan_nilai = $penerimaan_data[$id_produk]['total_nilai_terima'] ?? 0;
    $pengeluaran_jumlah = $pengeluaran_data[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_data[$id_produk]['total_nilai_keluar'] ?? 0;
    $saldo_akhir_jumlah = $saldo_awal_jumlah + $penerimaan_jumlah - $pengeluaran_jumlah;
    $saldo_akhir_nilai = $saldo_akhir_jumlah * $produk['harga'];
    if ($saldo_awal_jumlah != 0 || $penerimaan_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [
            'nama_barang' => $produk['nama_barang'],
            'saldo_awal_jumlah' => $saldo_awal_jumlah, 'saldo_awal_harga_satuan' => $saldo_awal_harga_satuan, 'saldo_awal_nilai' => $saldo_awal_nilai,
            'penerimaan_jumlah' => $penerimaan_jumlah, 'penerimaan_nilai' => $penerimaan_nilai,
            'pengeluaran_jumlah' => $pengeluaran_jumlah, 'pengeluaran_nilai' => $pengeluaran_nilai,
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah, 'saldo_akhir_harga_satuan' => $produk['harga'], 'saldo_akhir_nilai' => $saldo_akhir_nilai,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Persediaan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .container {
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16pt;
        }
        .header h2 {
            margin: 0;
            font-size: 12pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 9pt; }

        /* Style untuk mode cetak */
        @media print {
            @page {
                size: A4 landscape; /* Mengatur kertas A4 dengan orientasi landscape */
                margin: 1cm;
            }
            body, .container {
                margin: 0;
                box-shadow: none;
                border: none;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>LAPORAN PERSEDIAAN BARANG</h1>
            <h2>NAMA PERUSAHAAN/INSTANSI ANDA</h2>
            <p>Periode: <?php echo date('d M Y', strtotime($tgl_mulai_str)) . ' - ' . date('d M Y', strtotime($tgl_selesai_str)); ?></p>
        </div>

        <table>
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
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                        
                        <td class="text-center"><?php echo $item['saldo_awal_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga_satuan'], 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_awal_nilai'], 2, ',', '.'); ?></td>

                        <?php 
                            $avg_harga_terima = ($item['penerimaan_jumlah'] > 0) ? $item['penerimaan_nilai'] / $item['penerimaan_jumlah'] : 0;
                            $avg_harga_keluar = ($item['pengeluaran_jumlah'] > 0) ? $item['pengeluaran_nilai'] / $item['pengeluaran_jumlah'] : 0;
                        ?>
                        <td class="text-center"><?php echo $item['penerimaan_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($avg_harga_terima, 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['penerimaan_nilai'], 2, ',', '.'); ?></td>
                        
                        <td class="text-center"><?php echo $item['pengeluaran_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($avg_harga_keluar, 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['pengeluaran_nilai'], 2, ',', '.'); ?></td>
                        
                        <td class="text-center"><?php echo $item['saldo_akhir_jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga_satuan'], 2, ',', '.'); ?></td>
                        <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_nilai'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="14" style="text-align:center;">Tidak ada data pergerakan barang pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            Dicetak pada: <?php echo date('d M Y, H:i:s'); ?> oleh <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
        </div>
    </div>
</body>
</html>