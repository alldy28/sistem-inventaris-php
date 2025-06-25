<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_permintaan = $_GET['id'];

// Ambil data utama permintaan (tidak berubah)
$stmt_req = $koneksi->prepare("SELECT p.*, u.nama_lengkap FROM permintaan p JOIN users u ON p.id_user = u.id WHERE p.id = ?");
$stmt_req->bind_param("i", $id_permintaan);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
$permintaan = $result_req->fetch_assoc();

if (!$permintaan) die("Data permintaan tidak ditemukan.");

// Cek otorisasi untuk user (tidak berubah)
if ($_SESSION['role'] == 'user' && $permintaan['id_user'] != $_SESSION['user_id']) {
    die("Akses ditolak.");
}

// <<-- PERBAIKAN: Query diperbarui dengan JOIN 3 tabel (detail, produk, kategori) -->>
$stmt_detail = $koneksi->prepare("
    SELECT dp.*, pr.spesifikasi, pr.satuan, kp.nusp_id, kp.nama_kategori
    FROM detail_permintaan dp 
    JOIN produk pr ON dp.id_produk = pr.id 
    JOIN kategori_produk kp ON pr.id_kategori = kp.id 
    WHERE dp.id_permintaan = ?
");
$stmt_detail->bind_param("i", $id_permintaan);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Permintaan Barang #<?php echo $id_permintaan; ?></title>
    <style>
        /* CSS untuk cetak tetap sama */
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; }
        .page-a4 { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1cm auto; border: 1px #D3D3D3 solid; border-radius: 5px; background: white; box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 30px; }
        .kop-surat h1 { font-size: 18pt; margin: 0; }
        .kop-surat p { font-size: 11pt; margin: 0; }
        h2 { text-align: center; font-size: 14pt; text-decoration: underline; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info-surat { margin-bottom: 20px; }
        .info-surat td { border: none; padding: 2px 0; }
        .signature-block { margin-top: 80px; width: 100%; }
        .signature { float: right; width: 250px; text-align: center; }
        .signature .name { margin-top: 70px; text-decoration: underline; font-weight: bold; }
        .clearfix::after { content: ""; clear: both; display: table; }

        @media print {
            body, .page-a4 { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page-a4">
        <div class="kop-surat">
            <h1>NAMA PERUSAHAAN/INSTANSI ANDA</h1>
            <p>Jalan Alamat No. 123, Kota, Provinsi, Kode Pos</p>
            <p>Telepon: (021) 1234567 | Email: info@perusahaan.com</p>
        </div>

        <h2>SURAT PERMINTAAN BARANG</h2>
        
        <table class="info-surat">
            <tr><td style="width: 120px;">Nomor</td><td>: SPB/<?php echo date('Y'); ?>/<?php echo str_pad($id_permintaan, 4, '0', STR_PAD_LEFT); ?></td></tr>
            <tr><td>Tanggal</td><td>: <?php echo date('d F Y', strtotime($permintaan['tanggal_permintaan'])); ?></td></tr>
            <tr><td>Peminta</td><td>: <?php echo htmlspecialchars($permintaan['nama_lengkap']); ?></td></tr>
        </table>

        <p>Dengan hormat,<br>Berdasarkan kebutuhan operasional, dengan ini kami mengajukan permohonan pengadaan barang-barang sebagai berikut:</p>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No.</th>
                    <th>ID NUSP</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while($item = $result_detail->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($item['nusp_id']); ?></td>
                    <td><?php echo htmlspecialchars($item['nama_kategori'] . ' (' . $item['spesifikasi'] . ')'); ?></td>
                    <td style="text-align: center;"><?php echo $item['jumlah']; ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($item['satuan']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p style="margin-top: 20px;">Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

        <div class="signature-block clearfix">
            <div class="signature">
                <p>Menyetujui,</p>
                <div class="name">(........................................)</div>
                <p>Admin / Kepala Bagian</p>
            </div>
        </div>

    </div>
</body>
</html>