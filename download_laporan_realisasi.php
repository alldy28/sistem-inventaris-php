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

// Panggil fungsi yang sudah benar dari file utama
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
    'Saldo Awal (Jml)', 'Saldo Awal (Harga)', 'Saldo Awal (Total)',
    'Penerimaan (Jml)', 'Penerimaan (Harga Batch Aktif)', 'Penerimaan (Total)',
    'Pengeluaran (Jml)', 'Pengeluaran (Harga Keluar Terakhir)', 'Pengeluaran (Total)',
    'Saldo Akhir (Jml)', 'Saldo Akhir (Harga Avg)', 'Saldo Akhir (Total)',
    'Tgl Perolehan Terakhir', 'Bentuk Kontrak', 'Nama Penyedia'
]);

// Tulis setiap baris data ke file CSV
$no = 1;
if (!empty($laporan_data)) {
    foreach ($laporan_data as $item) {
        // PERBAIKAN FINAL: Casting semua nilai numerik ke float
        // agar Excel tidak salah menginterpretasikan format angka.
        $row = [
            $no++,
            $item['nama_kategori'],
            $item['spesifikasi'],
            $item['satuan'],
            (float) $item['saldo_awal_jumlah'],
            (float) $item['saldo_awal_harga'],
            (float) $item['saldo_awal_nilai'],
            (float) $item['penerimaan_jumlah_total'],
            (float) $item['harga_batch_aktif'],
            (float) $item['penerimaan_nilai_total'],
            (float) $item['pengeluaran_jumlah'],
            (float) $item['pengeluaran_harga_terakhir'],
            (float) $item['pengeluaran_nilai'],
            (float) $item['saldo_akhir_jumlah'],
            (float) $item['saldo_akhir_harga'],
            (float) $item['saldo_akhir_nilai'],
            $item['tgl_perolehan'] != '-' ? date('Y-m-d', strtotime($item['tgl_perolehan'])) : '-',
            $item['bentuk_kontrak'],
            $item['nama_penyedia'],
        ];
        fputcsv($output, $row);
    }
}

// Hentikan eksekusi script setelah file CSV dibuat
exit;