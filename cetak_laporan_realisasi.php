<?php
require_once 'koneksi.php';
session_start();

// Beri tanda bahwa kita hanya butuh logikanya, bukan HTML dari file utama.
define('IS_LOGIC_CALL', true);
// Panggil file utama untuk mendapatkan akses ke fungsinya.
require_once 'laporan_realisasi.php';

// Keamanan: Pastikan pengguna login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Anda harus login sebagai admin.");
}

// Panggil fungsi yang sekarang tersedia dari file utama
$laporan_data = getInventoryRealizationReportData($koneksi);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Realisasi Persediaan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; }
        .container { width: 100%; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        h1 { margin: 0; font-size: 14pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; word-wrap:break-word; }
        th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            @page { size: A4 landscape; margin: 1cm; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>LAPORAN REALISASI PERSEDIAAN</h1>
        </div>
        <table>
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
                    <th rowspan="2">Tgl Perolehan Terakhir</th>
                    <th rowspan="2">Bentuk Kontrak</th>
                    <th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr><td colspan="19" class="text-center">Tidak ada data.</td></tr>
                <?php else: $no = 1; foreach ($laporan_data as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($item['nama_kategori']); ?></td>
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
</body>
</html>