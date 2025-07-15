<?php
require_once 'koneksi.php';
session_start();

// --- LOGIKA KEAMANAN ---
if (!isset($_SESSION['loggedin']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_permintaan = (int)$_GET['id'];

// --- Ambil data utama permintaan ---
$stmt_req = $koneksi->prepare("SELECT p.*, u.nama_lengkap FROM permintaan p JOIN users u ON p.id_user = u.id WHERE p.id = ?");
$stmt_req->bind_param("i", $id_permintaan);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
$permintaan = $result_req->fetch_assoc();

if (!$permintaan) {
    die("Data permintaan tidak ditemukan.");
}

// --- Cek otorisasi untuk user ---
if ($_SESSION['role'] == 'user' && $permintaan['id_user'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda hanya dapat mencetak permintaan Anda sendiri.");
}

// --- Ambil rincian barang yang diminta ---
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

// --- Fungsi Bantuan ---
function format_tanggal_indonesia($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Permintaan Barang #<?= htmlspecialchars($id_permintaan); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; }
        .page-a4 { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1cm auto; background: white; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 30px; }
        .judul-utama { text-align: center; font-size: 14pt; text-decoration: underline; margin-bottom: 30px; font-weight: bold; }
        .info-surat { margin-bottom: 20px; }
        .info-surat td { border: none; padding: 2px 0; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .content-table th { background-color: #f2f2f2; text-align: center; }
        .signature-section { margin-top: 60px; width: 100%; }
        .signature-box { float: left; width: 48%; text-align: center; }
        .signature-box.right { float: right; }
        .signature-name { margin-top: 70px; text-decoration: underline; font-weight: bold; }
        .clearfix::after { content: ""; clear: both; display: table; }
        @media print {
            body, .page-a4 { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page-a4">
        <div class="kop-surat">
            <table style="width: 100%; border: 0;">
                <tr>
                    <td style="width: 15%; text-align: left; vertical-align: middle; border:0;"><img src="logo_depok.png" alt="Logo Depok" style="width: 90px;"></td>
                    <td style="width: 70%; text-align: center; border:0;">
                        <div style="font-size: 18pt; font-weight: bold;">PEMERINTAH KOTA DEPOK</div>
                        <div style="font-size: 16pt; font-weight: bold;">DINAS KESEHATAN</div>
                        <div style="font-size: 14pt; font-weight: bold;">UPTD PUSKESMAS CIPAYUNG</div>
                        <div style="font-size: 11pt;">Jl. Blok Rambutan Rt.001/004 No.108 Kel. Cipayung 16437<br>Email : upt_pkm_cipayung@yahoo.com</div>
                    </td>
                    <td style="width: 15%; text-align: right; vertical-align: middle; border:0;"><img src="logo_sehat.png" alt="Logo Sehat" style="width: 100px;"></td>
                </tr>
            </table>
        </div>

        <div class="judul-utama">SURAT PERMINTAAN BARANG</div>
        
        <table class="info-surat">
            <tr><td style="width: 120px;">Nomor</td><td>: SPB/<?= htmlspecialchars($permintaan['id']); ?>/<?= date('m/Y', strtotime($permintaan['tanggal_permintaan'])); ?></td></tr>
            <tr><td>Tanggal</td><td>: <?= format_tanggal_indonesia($permintaan['tanggal_permintaan']); ?></td></tr>
        </table>

        <p>Dengan hormat,<br>Sehubungan dengan kebutuhan operasional, dengan ini kami mengajukan permohonan pengadaan barang-barang sebagai berikut:</p>

        <table class="content-table">
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
                    <td style="text-align: center;"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($item['nusp_id']); ?></td>
                    <td><?= htmlspecialchars($item['nama_kategori'] . ' (' . $item['spesifikasi'] . ')'); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($item['jumlah']); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($item['satuan']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p style="margin-top: 20px;">Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

        <div class="signature-section clearfix">
            <div class="signature-box">
                <p>Hormat kami,<br>Pemohon</p>
                <div class="signature-name">( <?= htmlspecialchars($permintaan['nama_lengkap']); ?> )</div>
            </div>
            <div class="signature-box right">
                <p>Menyetujui,</p>
                <div class="signature-name">( ........................................ )</div>
                <p>Admin / Kepala Bagian</p>
            </div>
        </div>
    </div>
</body>
</html>