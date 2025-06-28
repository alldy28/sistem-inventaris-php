<?php
require_once 'koneksi.php';
session_start();

// --- LOGIKA KEAMANAN YANG DIPERBAIKI ---

// 1. Cek dasar: User harus login dan ID laporan harus ada
if (!isset($_SESSION['loggedin']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_perbaikan = $_GET['id'];

// 2. Ambil data laporan dari database
$stmt_laporan = $koneksi->prepare("SELECT pa.*, u.nama_lengkap as nama_user FROM perbaikan_aset pa LEFT JOIN users u ON pa.id_user = u.id WHERE pa.id = ?");
$stmt_laporan->bind_param("i", $id_perbaikan);
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();
$laporan = $result_laporan->fetch_assoc();

// 3. Cek apakah laporan ditemukan
if (!$laporan) {
    die("Laporan perbaikan tidak ditemukan.");
}

// 4. Cek otorisasi: Jika yang login adalah user, pastikan ini adalah laporannya
if ($_SESSION['role'] == 'user' && $laporan['id_user'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda hanya bisa mencetak bukti pengambilan untuk laporan Anda sendiri.");
}

// --- AKHIR LOGIKA KEAMANAN ---


// Ambil data komponen yang digunakan
$stmt_komponen = $koneksi->prepare("SELECT * FROM komponen_perbaikan WHERE id_perbaikan = ?");
$stmt_komponen->bind_param("i", $id_perbaikan);
$stmt_komponen->execute();
$result_komponen = $stmt_komponen->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pengambilan Aset #<?php echo $laporan['id']; ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        .page-a4 { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1cm auto; background: white; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 30px; }
        .kop-surat h1 { font-size: 16pt; margin: 0; }
        .kop-surat p { font-size: 11pt; margin: 0; }
        h2 { text-align: center; font-size: 14pt; text-decoration: underline; margin: 20px 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .content-table th { background-color: #f2f2f2; text-align: center; }
        .signature-section { margin-top: 60px; width: 100%; }
        .signature-box { float: left; width: 45%; text-align: center; }
        .signature-box.right { float: right; }
        .signature-box .signature-name { margin-top: 80px; text-decoration: underline; font-weight: bold; }
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
        </div>

        <h2>TANDA TERIMA PENGAMBILAN BARANG</h2>

        <p>Pada hari ini, <?php echo date('d F Y'); ?>, telah dilakukan serah terima barang hasil perbaikan dengan rincian sebagai berikut:</p>
        
        <table class="info-table">
            <tr><td style="width: 180px;">Nomor Laporan Perbaikan</td><td>: #<?php echo $laporan['id']; ?></td></tr>
            <tr><td>Aset yang Diperbaiki</td><td>: <?php echo htmlspecialchars($laporan['nama_aset']); ?></td></tr>
            <tr><td>Dilaporkan oleh</td><td>: <?php echo htmlspecialchars($laporan['nama_user'] ?? 'N/A'); ?></td></tr>
            <tr><td>Tanggal Laporan</td><td>: <?php echo date('d F Y', strtotime($laporan['tanggal_laporan'])); ?></td></tr>
            <tr><td>Tanggal Selesai</td><td>: <?php echo $laporan['tanggal_selesai'] ? date('d F Y, H:i', strtotime($laporan['tanggal_selesai'])) : '-'; ?></td></tr>
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
                        <td><?php echo htmlspecialchars($komp['nama_komponen']); ?></td>
                        <td class="text-center"><?php echo $komp['jumlah']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center;">Tidak ada komponen spesifik yang dicatat.</td></tr>
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
                <div class="signature-name">( <?php echo htmlspecialchars($laporan['nama_user'] ?? '................................'); ?> )</div>
                <p>(User)</p>
            </div>
        </div>
        <div class="footer clearfix">
            Dicetak oleh <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?> pada: <?php echo date('d M Y, H:i:s'); ?>
        </div>
    </div>
</body>
</html>