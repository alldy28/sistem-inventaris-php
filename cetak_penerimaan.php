<?php
require_once 'koneksi.php';
session_start();

// Keamanan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_penerimaan = $_GET['id'];

// --- PERBAIKAN QUERY SQL ---
// Kita perlu JOIN ke 3 tabel: penerimaan, produk, dan kategori_produk
// untuk mendapatkan nama barang yang lengkap (kategori + spesifikasi).
$stmt = $koneksi->prepare("
    SELECT 
        kp.nama_kategori, 
        p.spesifikasi, 
        p.satuan, 
        pn.* FROM penerimaan pn 
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Penerimaan Barang #<?php echo $data['id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .container { width: 100%; max-width: 800px; margin: auto; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16pt; }
        .header h2 { margin: 5px 0 0 0; font-size: 12pt; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info-table td { border: none; padding: 3px 0; }
        .footer { margin-top: 50px; text-align: center; font-size: 9pt; }
        .signatures { margin-top: 60px; display: table; width: 100%; }
        .signature-col { display: table-cell; width: 50%; text-align: center; }
        @media print {
            @page { size: A4 portrait; margin: 2cm; }
            body, .container { margin: 0; width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>BUKTI PENERIMAAN BARANG</h1>
            <h2>NAMA PERUSAHAAN/INSTANSI ANDA</h2>
        </div>
        
        <h3>Detail Transaksi</h3>
        <table class="info-table">
            <tr><td width="200px">ID Penerimaan</td><td>: #<?php echo $data['id']; ?></td></tr>
            <tr><td>Tanggal</td><td>: <?php echo date('d F Y, H:i', strtotime($data['tanggal_penerimaan'])); ?></td></tr>
            <tr><td>Nama Penyedia</td><td>: <?php echo htmlspecialchars($data['nama_penyedia']); ?></td></tr>
            <tr><td>Nomor Faktur/Dokumen</td><td>: <?php echo htmlspecialchars($data['nomor_faktur']); ?></td></tr>
            <tr><td>Sumber Anggaran</td><td>: <?php echo htmlspecialchars($data['sumber_anggaran']); ?></td></tr>
            <tr><td>Bentuk Kontrak</td><td>: <?php echo htmlspecialchars($data['bentuk_kontrak']); ?></td></tr>
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
                    <td><?php echo htmlspecialchars($data['nama_kategori'] . ' ' . $data['spesifikasi']); ?></td>
                    <td style="text-align:center;"><?php echo $data['jumlah']; ?></td>
                    <td style="text-align:center;"><?php echo htmlspecialchars($data['satuan']); ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($data['harga_satuan'], 0, ',', '.'); ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($data['jumlah'] * $data['harga_satuan'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-col">
                <p>Pihak Yang Menyerahkan,</p>
                <p>Penyedia</p>
                <br><br><br><br>
                <p>(..............................)</p>
            </div>
            <div class="signature-col">
                <p>Pihak Yang Menerima,</p>
                <p>Petugas Gudang</p>
                <br><br><br><br>
                <p>(<?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>)</p>
            </div>
        </div>

        <div class="footer">
            <p>Dicetak pada: <?php echo date('d M Y, H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>