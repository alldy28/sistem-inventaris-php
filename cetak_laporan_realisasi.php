<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['loggedin'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Beri tanda bahwa kita hanya butuh logikanya, bukan HTML dari file utama.
define('IS_LOGIC_CALL', true);
// Panggil file utama untuk mendapatkan akses ke fungsinya.
require_once 'laporan_realisasi.php';

// Panggil fungsi yang BENAR untuk mengambil data laporan.
$laporan_data = getInventoryRealizationReport($koneksi);

// Inisialisasi variabel untuk Grand Total
$total_saldo_awal_nilai = 0;
$total_penerimaan_nilai = 0;
$total_pengeluaran_nilai = 0;
$total_saldo_akhir_nilai = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Realisasi Persediaan</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; }
        .container { width: 100%; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        h1 { margin: 0; font-size: 14pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        tbody tr:nth-child(even) { background-color: #fafafa; }
        tfoot tr td { background-color: #e9e9e9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }

        @media print {
            @page { size: A4 landscape; margin: 1cm; }
            body { margin: 0; -webkit-print-color-adjust: exact; }
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
                    <th rowspan="2" class="nowrap">Tgl Perolehan Terakhir</th>
                    <th rowspan="2">Bentuk Kontrak</th>
                    <th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                    <th>Jml</th><th class="nowrap">Nilai Total</th><th class="nowrap">Harga Batch Aktif</th>
                    <th>Jml</th><th class="nowrap">Nilai Total</th><th class="nowrap">Harga Keluar Terakhir</th>
                    <th>Jml</th><th>Nilai</th><th class="nowrap">Harga Satuan Avg.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr><td colspan="19" class="text-center">Tidak ada data untuk ditampilkan.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($laporan_data as $item): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($item['nama_kategori']); ?></td>
                            <td><?= htmlspecialchars($item['spesifikasi']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($item['satuan']); ?></td>

                            <td class="text-center"><?= number_format($item['saldo_awal_jumlah']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['saldo_awal_harga']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['saldo_awal_nilai']); ?></td>

                            <td class="text-center"><?= number_format($item['penerimaan_jumlah_total']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['penerimaan_nilai_total']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['harga_batch_aktif']); ?></td>

                            <td class="text-center"><?= number_format($item['pengeluaran_jumlah']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['pengeluaran_nilai']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['pengeluaran_harga_terakhir']); ?></td>

                            <td class="text-center"><?= number_format($item['saldo_akhir_jumlah']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['saldo_akhir_nilai']); ?></td>
                            <td class="text-right nowrap">Rp <?= number_format($item['saldo_akhir_harga']); ?></td>
                            
                            <td class="text-center nowrap"><?= ($item['tgl_perolehan'] != '-') ? date('d-m-Y', strtotime($item['tgl_perolehan'])) : '-'; ?></td>
                            <td><?= htmlspecialchars($item['bentuk_kontrak']); ?></td>
                            <td><?= htmlspecialchars($item['nama_penyedia']); ?></td>
                        </tr>
                        <?php
                            // Akumulasi nilai untuk Grand Total
                            $total_saldo_awal_nilai += $item['saldo_awal_nilai'];
                            $total_penerimaan_nilai += $item['penerimaan_nilai_total'];
                            $total_pengeluaran_nilai += $item['pengeluaran_nilai'];
                            $total_saldo_akhir_nilai += $item['saldo_akhir_nilai'];
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-center"><strong>GRAND TOTAL</strong></td>
                    <td class="text-right nowrap"><strong>Rp <?= number_format($total_saldo_awal_nilai); ?></strong></td>
                    <td colspan="2"></td> <td class="text-right nowrap"><strong>Rp <?= number_format($total_penerimaan_nilai); ?></strong></td>
                    <td colspan="2"></td> <td class="text-right nowrap"><strong>Rp <?= number_format($total_pengeluaran_nilai); ?></strong></td>
                    <td colspan="2"></td> <td class="text-right nowrap"><strong>Rp <?= number_format($total_saldo_akhir_nilai); ?></strong></td>
                    <td colspan="3"></td> </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>