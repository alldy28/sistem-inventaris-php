<?php
require_once 'koneksi.php';
session_start();

// --- LOGIKA KEAMANAN ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_penerimaan = (int)$_GET['id'];

// --- Mengambil Data Penerimaan ---
$stmt = $koneksi->prepare("
    SELECT 
        kp.nama_kategori, p.spesifikasi, p.satuan, 
        pn.id, pn.tanggal_penerimaan, pn.nama_penyedia, pn.nomor_faktur, pn.sumber_anggaran, pn.bentuk_kontrak, pn.jumlah, pn.harga_satuan
    FROM penerimaan pn 
    JOIN produk p ON pn.id_produk = p.id 
    JOIN kategori_produk kp ON p.id_kategori = kp.id 
    WHERE pn.id = ?
");
$stmt->bind_param("i", $id_penerimaan);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data penerimaan tidak ditemukan.");
}

// --- FUNGSI BANTUAN (HELPER FUNCTIONS) ---
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function format_tanggal($tanggal_db, $format = 'd F Y, H:i') {
    return date($format, strtotime($tanggal_db));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Penerimaan Barang #<?= htmlspecialchars($data['id']); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        .page-a4 { width: 21cm; min-height: 29.7cm; padding: 2cm; margin: 1cm auto; background: white; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 30px; }
        .judul-utama { text-align: center; font-size: 14pt; text-decoration: underline; margin: 20px 0; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .content-table th { background-color: #f2f2f2; text-align: center; }
        .signatures { margin-top: 60px; display: table; width: 100%; }
        .signature-col { display: table-cell; width: 48%; text-align: center; }
        .signature-name { margin-top: 70px; font-weight: bold; }
        .footer { margin-top: 50px; text-align: right; font-size: 9pt; color: #777; font-style: italic; }
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
                    <td style="width: 15%; text-align: left; vertical-align: middle; border: 0;">
                        <img src="logo_depok.png" alt="Logo Depok" style="width: 90px;">
                    </td>
                    <td style="width: 70%; text-align: center; border: 0;">
                        <div style="font-size: 18pt; font-weight: bold;">PEMERINTAH KOTA DEPOK</div>
                        <div style="font-size: 16pt; font-weight: bold;">DINAS KESEHATAN</div>
                        <div style="font-size: 14pt; font-weight: bold;">UPTD PUSKESMAS CIPAYUNG</div>
                        <div style="font-size: 11pt;">Jl. Blok Rambutan Rt.001/004 No.108 Kel. Cipayung 16437<br>
                        Email : upt_pkm_cipayung@yahoo.com</div>
                    </td>
                    <td style="width: 15%; text-align: right; vertical-align: middle; border: 0;">
                        <img src="logo_sehat.png" alt="Logo Sehat" style="width: 100px;">
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="judul-utama">BUKTI PENERIMAAN BARANG</div>

        <table class="info-table">
            <tr><td style="width: 200px;">ID Penerimaan</td><td>: #<?= htmlspecialchars($data['id']); ?></td></tr>
            <tr><td>Tanggal</td><td>: <?= format_tanggal($data['tanggal_penerimaan']); ?></td></tr>
            <tr><td>Nama Penyedia</td><td>: <?= htmlspecialchars($data['nama_penyedia']); ?></td></tr>
            <tr><td>Nomor Faktur/Dokumen</td><td>: <?= htmlspecialchars($data['nomor_faktur']); ?></td></tr>
            <tr><td>Sumber Anggaran</td><td>: <?= htmlspecialchars($data['sumber_anggaran']); ?></td></tr>
            <tr><td>Bentuk Kontrak</td><td>: <?= htmlspecialchars($data['bentuk_kontrak']); ?></td></tr>
        </table>

        <h3>Rincian Barang Diterima</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th style="width:15%;">Jumlah</th>
                    <th style="width:15%;">Satuan</th>
                    <th style="width:20%;">Harga Satuan</th>
                    <th style="width:20%;">Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($data['nama_kategori'] . ' ' . $data['spesifikasi']); ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($data['jumlah']); ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($data['satuan']); ?></td>
                    <td style="text-align:right;"><?= format_rupiah($data['harga_satuan']); ?></td>
                    <td style="text-align:right;"><?= format_rupiah($data['jumlah'] * $data['harga_satuan']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-col">
                <p>Pihak Yang Menyerahkan,<br>Penyedia</p>
                <div style="height: 80px;"></div>
                <div class="signature-name">( .............................. )</div>
            </div>
            <div class="signature-col">
                <p>Pihak Yang Menerima,<br>Petugas Gudang</p>
                <div style="height: 80px;"></div>
                <div class="signature-name">( <?= htmlspecialchars($_SESSION['nama_lengkap']); ?> )</div>
            </div>
        </div>

        <div class="footer">
            <p>Dicetak pada: <?= date('d M Y, H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>