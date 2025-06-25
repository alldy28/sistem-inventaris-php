<?php
ob_start(); // Mulai output buffering untuk mencegah error header
require_once 'koneksi.php';
session_start();

// Keamanan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['tgl_mulai'])) {
    die("Akses ditolak atau parameter tidak lengkap.");
}

// ===================================================================
// BAGIAN 1: PENGAMBILAN DAN PERHITUNGAN DATA (YANG HILANG SEBELUMNYA)
// ===================================================================

$tgl_mulai_str = $_GET['tgl_mulai'];
$tgl_selesai_str = $_GET['tgl_selesai'];
$tgl_mulai_datetime = $tgl_mulai_str . ' 00:00:00';
$tgl_selesai_datetime = $tgl_selesai_str . ' 23:59:59';

// Ambil semua produk
$semua_produk = [];
$result_produk = $koneksi->query("SELECT pr.*, kp.nama_kategori, kp.nusp_id FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
while($p = $result_produk->fetch_assoc()) { $semua_produk[$p['id']] = $p; }

// Hitung pergerakan SEBELUM periode
$penerimaan_sebelum = [];
$stmt1 = $koneksi->prepare("SELECT id_produk, SUM(jumlah) as total FROM penerimaan WHERE tanggal_penerimaan < ? GROUP BY id_produk");
$stmt1->bind_param("s", $tgl_mulai_str); $stmt1->execute(); $res1 = $stmt1->get_result();
while($p = $res1->fetch_assoc()) { $penerimaan_sebelum[$p['id_produk']] = $p['total']; }

$pengeluaran_sebelum = [];
$stmt2 = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) as total FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses < ? GROUP BY dp.id_produk");
$stmt2->bind_param("s", $tgl_mulai_str); $stmt2->execute(); $res2 = $stmt2->get_result();
while($p = $res2->fetch_assoc()) { $pengeluaran_sebelum[$p['id_produk']] = $p['total']; }

// Hitung pergerakan DI DALAM periode
$penerimaan_periode = [];
$stmt3 = $koneksi->prepare("SELECT id_produk, SUM(jumlah) AS total_terima, SUM(jumlah * harga_satuan) AS total_nilai_terima FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk");
$stmt3->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt3->execute(); $res3 = $stmt3->get_result();
while($p = $res3->fetch_assoc()) { $penerimaan_periode[$p['id_produk']] = $p; }

$pengeluaran_periode = [];
$stmt4 = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) AS total_keluar, SUM(dp.nilai_keluar_fifo) AS total_nilai_keluar FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND p.tanggal_diproses BETWEEN ? AND ? GROUP BY dp.id_produk");
$stmt4->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt4->execute(); $res4 = $stmt4->get_result();
while($p = $res4->fetch_assoc()) { $pengeluaran_periode[$p['id_produk']] = $p; }

// Ambil detail penerimaan TERAKHIR dalam periode
$penerimaan_terakhir = [];
$sql_terakhir = "SELECT p1.id_produk, p1.tanggal_penerimaan, p1.bentuk_kontrak, p1.nama_penyedia FROM penerimaan p1 INNER JOIN (SELECT id_produk, MAX(tanggal_penerimaan) as max_tanggal FROM penerimaan WHERE tanggal_penerimaan BETWEEN ? AND ? GROUP BY id_produk) p2 ON p1.id_produk = p2.id_produk AND p1.tanggal_penerimaan = p2.max_tanggal";
$stmt5 = $koneksi->prepare($sql_terakhir);
$stmt5->bind_param("ss", $tgl_mulai_datetime, $tgl_selesai_datetime);
$stmt5->execute(); $res5 = $stmt5->get_result();
while($p = $res5->fetch_assoc()) { $penerimaan_terakhir[$p['id_produk']] = $p; }

// Gabungkan semua data
$laporan_data = [];
foreach ($semua_produk as $id_produk => $produk) {
    $saldo_awal_jumlah = ($produk['stok_awal'] + ($penerimaan_sebelum[$id_produk] ?? 0)) - ($pengeluaran_sebelum[$id_produk] ?? 0);
    $penerimaan_jumlah = $penerimaan_periode[$id_produk]['total_terima'] ?? 0;
    $pengeluaran_jumlah = $pengeluaran_periode[$id_produk]['total_keluar'] ?? 0;
    $saldo_akhir_jumlah = $saldo_awal_jumlah + $penerimaan_jumlah - $pengeluaran_jumlah;

    if ($saldo_awal_jumlah != 0 || $penerimaan_jumlah != 0 || $pengeluaran_jumlah != 0 || $saldo_akhir_jumlah != 0) {
        $laporan_data[] = [
            'nusp_id' => $produk['nusp_id'],
            'nama_kategori' => $produk['nama_kategori'],
            'spesifikasi' => $produk['spesifikasi'],
            'satuan' => $produk['satuan'],
            'saldo_awal_jumlah' => $saldo_awal_jumlah,
            'saldo_awal_harga' => $produk['harga_awal'],
            'saldo_awal_total' => $saldo_awal_jumlah * $produk['harga_awal'],
            'penerimaan_jumlah' => $penerimaan_jumlah,
            'penerimaan_nilai' => $penerimaan_periode[$id_produk]['total_nilai_terima'] ?? 0,
            'pengeluaran_jumlah' => $pengeluaran_jumlah,
            'pengeluaran_nilai' => $pengeluaran_periode[$id_produk]['total_nilai_keluar'] ?? 0,
            'saldo_akhir_jumlah' => $saldo_akhir_jumlah,
            'saldo_akhir_harga' => $produk['harga'],
            'saldo_akhir_total' => $saldo_akhir_jumlah * $produk['harga'],
            'tgl_perolehan' => $penerimaan_terakhir[$id_produk]['tanggal_penerimaan'] ?? '-',
            'bentuk_kontrak' => $penerimaan_terakhir[$id_produk]['bentuk_kontrak'] ?? '-',
            'nama_penyedia' => $penerimaan_terakhir[$id_produk]['nama_penyedia'] ?? '-',
        ];
    }
}


// ===================================================================
// BAGIAN 2: LOGIKA UNTUK MEMBUAT FILE CSV
// ===================================================================

$filename = "Laporan_Realisasi_" . date('Y-m-d') . ".csv";

// Atur header HTTP
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$output = fopen('php://output', 'w');

// Tulis header kolom untuk file CSV
fputcsv($output, [
    'No', 'ID NUSP', 'Nama Barang', 'Spesifikasi', 'Satuan',
    'Saldo Awal Jml', 'Saldo Awal Harga', 'Saldo Awal Total',
    'Penerimaan Jml', 'Penerimaan Harga Avg', 'Penerimaan Total',
    'Pengeluaran Jml', 'Pengeluaran Harga Avg', 'Pengeluaran Total',
    'Saldo Akhir Jml', 'Saldo Akhir Harga', 'Saldo Akhir Total',
    'Tgl Perolehan Terakhir', 'Bentuk Kontrak Terakhir', 'Nama Penyedia Terakhir'
]);

$no = 1;
foreach ($laporan_data as $item) {
    // Hitung harga rata-rata
    $avg_harga_terima = ($item['penerimaan_jumlah'] > 0) ? $item['penerimaan_nilai'] / $item['penerimaan_jumlah'] : 0;
    $avg_harga_keluar = ($item['pengeluaran_jumlah'] > 0) ? $item['pengeluaran_nilai'] / $item['pengeluaran_jumlah'] : 0;

    $row = [
        $no++,
        $item['nusp_id'],
        $item['nama_kategori'],
        $item['spesifikasi'],
        $item['satuan'],
        $item['saldo_awal_jumlah'],
        $item['saldo_awal_harga'],
        $item['saldo_awal_total'],
        $item['penerimaan_jumlah'],
        $avg_harga_terima,
        $item['penerimaan_nilai'],
        $item['pengeluaran_jumlah'],
        $avg_harga_keluar,
        $item['pengeluaran_nilai'],
        $item['saldo_akhir_jumlah'],
        $item['saldo_akhir_harga'],
        $item['saldo_akhir_total'],
        $item['tgl_perolehan'],
        $item['bentuk_kontrak'],
        $item['nama_penyedia']
    ];
    fputcsv($output, $row);
}

fclose($output);
ob_end_flush();
exit;
?>