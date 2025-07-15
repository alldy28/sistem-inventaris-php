<?php
require_once 'koneksi.php';
session_start();

// --- LOGIKA KEAMANAN ---
if (!isset($_SESSION['loggedin']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_perbaikan = $_GET['id'];

// Ambil data laporan utama dari database
$stmt_laporan = $koneksi->prepare(
    "SELECT pa.*, u.nama_lengkap as nama_user 
     FROM perbaikan_aset pa 
     LEFT JOIN users u ON pa.id_user = u.id 
     WHERE pa.id = ?"
);
$stmt_laporan->bind_param("i", $id_perbaikan);
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();
$laporan = $result_laporan->fetch_assoc();

// Cek jika laporan tidak ditemukan
if (!$laporan) {
    die("Laporan perbaikan tidak ditemukan.");
}

// Cek otorisasi: User hanya boleh mencetak laporannya sendiri
if ($_SESSION['role'] == 'user' && $laporan['id_user'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda hanya bisa mencetak bukti untuk laporan Anda sendiri.");
}

// Ambil data komponen yang digunakan
$stmt_komponen = $koneksi->prepare("SELECT * FROM komponen_perbaikan WHERE id_perbaikan = ?");
$stmt_komponen->bind_param("i", $id_perbaikan);
$stmt_komponen->execute();
$result_komponen = $stmt_komponen->get_result();

// Helper untuk format tanggal Indonesia
function format_tanggal_indonesia($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[ (int)$pecah[1] ] . ' ' . $pecah[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pengambilan Aset #<?= htmlspecialchars($laporan['id']); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; }
        .page-a4 { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1cm auto; background: white; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 30px; }
        .judul-utama { text-align: center; font-size: 14pt; text-decoration: underline; margin: 30px 0; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .content-table th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { float: left; width: 48%; text-align: center; }
        .signature-box.right { float: right; }
        .signature-box .signature-name { margin-top: 70px; text-decoration: underline; font-weight: bold; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer { margin-top: 50px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9pt; color: #777; }
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
                    <td style="width: 15%; text-align: left; vertical-align: middle;">
                        <img src="logo_depok.png" alt="Logo Depok" style="width: 90px;">
                    </td>
                    <td style="width: 70%; text-align: center;">
                        <div style="font-size: 18pt; font-weight: bold;">PEMERINTAH KOTA DEPOK</div>
                        <div style="font-size: 16pt; font-weight: bold;">DINAS KESEHATAN</div>
                        <div style="font-size: 14pt; font-weight: bold;">UPTD PUSKESMAS CIPAYUNG</div>
                        <div style="font-size: 11pt;">Jl. Blok Rambutan Rt.001/004 No.108 Kel. Cipayung 16437<br>
                        Email : upt_pkm_cipayung@yahoo.com</div>
                    </td>
                    <td style="width: 15%; text-align: right; vertical-align: middle;">
                        <img src="logo_sehat.png" alt="Logo Sehat" style="width: 100px;">
                    </td>
                </tr>
            </table>
        </div>

        <div class="judul-utama">TANDA TERIMA PENGAMBILAN BARANG</div>

        <p>Pada hari ini, <?php echo format_tanggal_indonesia(date('Y-m-d')); ?>, telah dilakukan serah terima barang hasil perbaikan dengan rincian sebagai berikut:</p>
        
        <table class="info-table">
            <tr><td style="width: 200px;">Nomor Laporan Perbaikan</td><td>: #<?= htmlspecialchars($laporan['id']); ?></td></tr>
            <tr><td>Aset yang Diperbaiki</td><td>: <?= htmlspecialchars($laporan['nama_aset']); ?></td></tr>
            <tr><td>Dilaporkan oleh</td><td>: <?= htmlspecialchars($laporan['nama_user'] ?? 'N/A'); ?></td></tr>
            <tr><td>Tanggal Laporan</td><td>: <?= format_tanggal_indonesia($laporan['tanggal_laporan']); ?></td></tr>
            <tr><td>Tanggal Selesai</td><td>: <?= $laporan['tanggal_selesai'] ? format_tanggal_indonesia($laporan['tanggal_selesai']) : '-'; ?></td></tr>
        </table>

        <p><strong>Dengan komponen/jasa yang telah digunakan sebagai berikut:</strong></p>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Nama Komponen/Jasa</th>
                    <th style="width: 15%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result_komponen->num_rows > 0): ?>
                    <?php while($komp = $result_komponen->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($komp['nama_komponen']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($komp['jumlah']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2" class="text-center">Tidak ada komponen spesifik yang dicatat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">Dengan ini saya menyatakan telah menerima kembali aset tersebut di atas dalam kondisi baik setelah dilakukan perbaikan.</p>

        <div class="signature-section clearfix">
            <div class="signature-box">
                <p>Yang Menyerahkan,</p>
                <div class="signature-name">( ........................................ )</div>
                <p>(Admin)</p>
            </div>
            <div class="signature-box right">
                <p>Yang Menerima,</p>
                <div class="signature-name">( <?= htmlspecialchars($laporan['nama_user'] ?? '................................'); ?> )</div>
                <p>(User)</p>
            </div>
        </div>

        <div class="footer clearfix">
             Dicetak oleh <?= htmlspecialchars($_SESSION['nama_lengkap']); ?> pada: <?= date('d M Y, H:i:s'); ?>
        </div>
    </div>
</body>
</html>