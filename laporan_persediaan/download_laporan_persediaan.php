<?php
ob_start(); // Menampung output untuk mencegah error header
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Logika pengambilan data (sama persis seperti sebelumnya)
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

// Siapkan data laporan
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $penerimaan_jumlah = $penerimaan_data[$id_produk]['total_terima'] ?? 0;
    $penerimaan_nilai = $penerimaan_data[$id_produk]['total_nilai_terima'] ?? 0;
    $pengeluaran_jumlah = $pengeluaran_data[$id_produk]['total_keluar'] ?? 0;
    $pengeluaran_nilai = $pengeluaran_data[$id_produk]['total_nilai_keluar'] ?? 0;
    $saldo_awal_jumlah = $produk['stok_awal'];
    $saldo_awal_nilai = $saldo_awal_jumlah * $produk['harga_awal'];
    $saldo_akhir_jumlah = $saldo_awal_jumlah + $penerimaan_jumlah - $pengeluaran_jumlah;

    if ($saldo_akhir_jumlah != 0 || $penerimaan_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_awal_jumlah != 0 ) {
        // <<-- PERUBAHAN #1: Tambahkan nusp_id dan satuan ke dalam array data -->>
        $laporan_data[] = [
            'nusp_id' => $produk['nusp_id'],
            'nama_barang' => $produk['nama_barang'],
            'satuan' => $produk['satuan'],
            'saldo_awal_jumlah' => $saldo_awal_jumlah,
            'saldo_awal_harga_satuan' => $produk['harga_awal'],
            'saldo_awal_nilai' => $saldo_awal_nilai,
            'penerimaan_jumlah' => $penerimaan_jumlah,
            'penerimaan_nilai' => $penerimaan_nilai,
            'pengeluaran_jumlah' => $pengeluaran_jumlah,
            'pengeluaran_nilai' => $pengeluaran_nilai,
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah,
            'saldo_akhir_harga_satuan' => $produk['harga'],
            'saldo_akhir_nilai' => $saldo_akhir_jumlah * $produk['harga'],
        ];
    }
}

// --- LOGIKA UNTUK MEMBUAT FILE CSV ---

$filename = "Laporan_Persediaan_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$output = fopen('php://output', 'w');

// <<-- PERUBAHAN #2: Tambahkan header kolom baru di sini -->>
fputcsv($output, [
    'No', 'ID NUSP', 'Nama Barang', 'Satuan',
    'Saldo Awal Jml', 'Saldo Awal Harga Satuan', 'Saldo Awal Total Nilai',
    'Penerimaan Jml', 'Penerimaan Harga Satuan (Avg)', 'Penerimaan Total Nilai',
    'Pengeluaran Jml', 'Pengeluaran Harga Satuan (Avg)', 'Pengeluaran Total Nilai',
    'Saldo Akhir Jml', 'Saldo Akhir Harga Satuan', 'Saldo Akhir Total Nilai'
]);

$no = 1;
foreach ($laporan_data as $item) {
    $avg_harga_terima = ($item['penerimaan_jumlah'] > 0) ? $item['penerimaan_nilai'] / $item['penerimaan_jumlah'] : 0;
    $avg_harga_keluar = ($item['pengeluaran_jumlah'] > 0) ? $item['pengeluaran_nilai'] / $item['pengeluaran_jumlah'] : 0;

    // <<-- PERUBAHAN #3: Tambahkan data baru ke setiap baris CSV -->>
    $row = [
        $no++,
        $item['nusp_id'],
        $item['nama_barang'],
        $item['satuan'],
        $item['saldo_awal_jumlah'],
        $item['saldo_awal_harga_satuan'],
        $item['saldo_awal_nilai'],
        $item['penerimaan_jumlah'],
        $avg_harga_terima,
        $item['penerimaan_nilai'],
        $item['pengeluaran_jumlah'],
        $avg_harga_keluar,
        $item['pengeluaran_nilai'],
        $item['saldo_akhir_jumlah'],
        $item['saldo_akhir_harga_satuan'],
        $item['saldo_akhir_nilai'],
    ];
    fputcsv($output, $row);
}

fclose($output);
exit;
?>