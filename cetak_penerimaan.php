<?php
require_once 'koneksi.php';
session_start();

// Keamanan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
    die("Akses ditolak atau ID tidak valid.");
}

$id_penerimaan = $_GET['id'];

// Query untuk mengambil SATU data penerimaan berdasarkan ID
$stmt = $koneksi->prepare("SELECT p.nama_barang, p.satuan, pn.* FROM penerimaan pn JOIN produk p ON pn.id_produk = p.id WHERE pn.id = ?");
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
        body { font-family: Arial, sans-serif; font-size: 11pt; } .container { width: 100%; } .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; } .header h1 { margin: 0; font-size: 16pt; } table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10pt; } th, td { border: 1px solid #000; padding: 8px; text-align: left; } .info-table td { border: none; padding: 3px 0; } .footer { margin-top: 50px; }
        @media print { @page { size: A4 portrait; margin: 2cm; } body, .container { margin: 0; } }
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
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($data['nama_barang']); ?></td>
                    <td style="text-align:center;"><?php echo $data['jumlah']; ?></td>
                    <td style="text-align:center;"><?php echo htmlspecialchars($data['satuan']); ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($data['harga_satuan'], 2, ',', '.'); ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($data['jumlah'] * $data['harga_satuan'], 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Dicetak pada: <?php echo date('d M Y, H:i:s'); ?> oleh <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
    </div>
</body>
</html>