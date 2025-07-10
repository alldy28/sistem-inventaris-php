<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || !isset($_GET['bulan']) || !isset($_GET['tahun'])) {
    die("Akses ditolak atau parameter tidak lengkap.");
}

// Ambil parameter bulan dan tahun dari URL
$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];

// Panggil file fungsi yang sudah kita pisahkan
require_once 'laporan_functions.php';

// Ambil data laporan menggunakan fungsi
$laporan_data = getMonthlyInventoryReport($koneksi, $bulan, $tahun);
$nama_bulan_arr = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$nama_bulan = $nama_bulan_arr[$bulan] ?? 'Bulan';

// Siapkan nama file
$filename = "Laporan_Bulanan_{$nama_bulan}_{$tahun}.csv";

// Atur HTTP Headers untuk memicu download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream PHP untuk menulis file CSV
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk kompatibilitas Excel dengan karakter UTF-8
fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

// Tulis baris header ke file CSV
fputcsv($output, [
    'No', 'Nama Barang', 'Spesifikasi',
    'Saldo Awal (Jml)', 'Saldo Awal (Nilai)',
    'Penerimaan (Jml)', 'Penerimaan (Nilai)',
    'Pengeluaran (Jml)', 'Pengeluaran (Nilai)',
    'Saldo Akhir (Jml)', 'Saldo Akhir (Nilai)'
]);

// Tulis setiap baris data ke file CSV
$no = 1;
if (!empty($laporan_data)) {
    foreach ($laporan_data as $item) {
        $row = [
            $no++,
            $item['nama_kategori'],
            $item['spesifikasi'],
            (int) $item['saldo_awal_jumlah'],
            (float) $item['saldo_awal_nilai'],
            (int) $item['penerimaan_jumlah'],
            (float) $item['penerimaan_nilai'],
            (int) $item['pengeluaran_jumlah'],
            (float) $item['pengeluaran_nilai'],
            (int) $item['saldo_akhir_jumlah'],
            (float) $item['saldo_akhir_nilai'],
        ];
        fputcsv($output, $row);
    }
}

fclose($output);
exit;