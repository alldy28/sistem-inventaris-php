<?php
require_once 'koneksi.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['tahun'])) {
    die("Akses ditolak.");
}

$tahun = (int)$_GET['tahun'];

$sql = "
    SELECT 
        hp.tanggal_penerimaan AS tanggal,
        'PENERIMAAN' AS jenis_transaksi,
        p.spesifikasi,
        hp.jumlah,
        hp.harga_satuan,
        (hp.jumlah * hp.harga_satuan) AS nilai,
        hp.nama_penyedia AS keterangan
    FROM histori_penerimaan hp
    JOIN produk p ON hp.id_produk = p.id
    WHERE hp.tahun_tutup_buku = $tahun

    UNION ALL

    SELECT 
        hper.tanggal_permintaan AS tanggal,
        'PENGELUARAN' AS jenis_transaksi,
        p.spesifikasi,
        hdp.jumlah_disetujui AS jumlah,
        
        /* * PERBAIKAN UTAMA: Menggunakan CAST untuk memastikan format angka benar.
         * Ini akan mengubah hasil pembagian menjadi format DECIMAL(15, 2) yang aman.
         */
        CASE 
            WHEN hdp.jumlah_disetujui > 0 
            THEN CAST((hdp.nilai_keluar_fifo / hdp.jumlah_disetujui) AS DECIMAL(15, 2)) 
            ELSE 0 
        END AS harga_satuan,
        
        hdp.nilai_keluar_fifo AS nilai,
        u.username AS keterangan
    FROM histori_detail_permintaan hdp
    JOIN histori_permintaan hper ON hdp.id_permintaan = hper.id AND hdp.tahun_tutup_buku = hper.tahun_tutup_buku
    JOIN produk p ON hdp.id_produk = p.id
    JOIN users u ON hper.id_user = u.id
    WHERE hdp.tahun_tutup_buku = $tahun AND hdp.jumlah_disetujui > 0
    
    ORDER BY tanggal ASC
";

$result = $koneksi->query($sql);
if (!$result) {
    die("Query Gagal: " . $koneksi->error);
}

// Proses pembuatan file CSV
$filename = "Histori_Laporan_" . $tahun . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');

fputcsv($output, ['Tanggal', 'Jenis Transaksi', 'Spesifikasi Barang', 'Jumlah', 'Harga Satuan', 'Total Nilai', 'Keterangan']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>