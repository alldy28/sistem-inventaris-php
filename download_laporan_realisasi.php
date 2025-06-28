<?php
require_once 'koneksi.php';
session_start();

// Beri tanda bahwa kita hanya butuh logikanya, bukan HTML dari file utama.
define('IS_LOGIC_CALL', true);
// Panggil file utama untuk mendapatkan akses ke fungsinya.
require_once 'laporan_realisasi.php';

// Keamanan: Pastikan pengguna login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("Akses ditolak. Anda harus login sebagai admin.");
}

// Panggil fungsi yang sekarang tersedia dari file utama
$laporan_data = getInventoryRealizationReportData($koneksi);

// Siapkan nama file dan atur HTTP Headers untuk memicu download
date_default_timezone_set('Asia/Jakarta');
$filename = "Laporan_Realisasi_Persediaan_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream PHP untuk menulis file CSV
$output = fopen('php://output', 'w');

// Tulis baris header ke file CSV
fputcsv($output, [
    'No', 'Nama Barang', 'Spesifikasi', 'Satuan',
    'Saldo Awal (Jml)', 'Saldo Awal (Harga Satuan)', 'Saldo Awal (Nilai Total)',
    'Penerimaan (Jml)', 'Penerimaan (Harga Satuan Terakhir)', 'Penerimaan (Nilai Total)',
    'Pengeluaran (Jml)', 'Pengeluaran (Harga Satuan Acuan)', 'Pengeluaran (Nilai Total)',
    'Saldo Akhir (Jml)', 'Saldo Akhir (Harga Satuan Rata-rata)', 'Saldo Akhir (Nilai Total)',
    'Tgl Perolehan Terakhir', 'Bentuk Kontrak', 'Nama Penyedia'
]);

// Tulis setiap baris data ke file CSV
$no = 1;
if (!empty($laporan_data)) {
    foreach ($laporan_data as $item) {
        $row = [
            $no++,
            $item['nama_kategori'],
            $item['spesifikasi'],
            $item['satuan'],
            $item['saldo_awal_jumlah'],
            $item['saldo_awal_harga'],
            $item['saldo_awal_nilai'],
            $item['penerimaan_jumlah_total'],
            $item['penerimaan_harga_acuan'],
            $item['penerimaan_nilai_total'],
            $item['pengeluaran_jumlah'],
            $item['penerimaan_harga_acuan'], // Sesuai logika yang disepakati
            $item['pengeluaran_nilai'],
            $item['saldo_akhir_jumlah'],
            $item['saldo_akhir_harga'],
            $item['saldo_akhir_nilai'],
            $item['tgl_perolehan'] != '-' ? date('Y-m-d', strtotime($item['tgl_perolehan'])) : '-',
            $item['bentuk_kontrak'],
            $item['nama_penyedia'],
        ];
        fputcsv($output, $row);
    }
}

// Hentikan eksekusi script setelah file CSV dibuat
exit;