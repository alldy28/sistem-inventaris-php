<?php
$page_title = 'Kartu Stok';
$active_page = 'laporan_persediaan';
require_once 'template_header.php';

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || !isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    echo "<p>Akses ditolak atau ID produk tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}

$id_produk = $_GET['id_produk'];

// Query untuk mengambil data produk + kategori
$stmt_produk = $koneksi->prepare("
    SELECT pr.*, kp.nama_kategori 
    FROM produk pr
    JOIN kategori_produk kp ON pr.id_kategori = kp.id
    WHERE pr.id = ?
");
$stmt_produk->bind_param("i", $id_produk);
$stmt_produk->execute();
$produk = $stmt_produk->get_result()->fetch_assoc();

if (!$produk) {
    echo "<p>Produk tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}

// --- PENGUMPULAN SEMUA TRANSAKSI ---
$transactions = [];

// 1. Ambil semua PENERIMAAN
$stmt_penerimaan = $koneksi->prepare("SELECT tanggal_penerimaan as tanggal, jumlah, harga_satuan, id as ref_id FROM penerimaan WHERE id_produk = ?");
$stmt_penerimaan->bind_param("i", $id_produk);
$stmt_penerimaan->execute();
$result_penerimaan = $stmt_penerimaan->get_result();
while ($row = $result_penerimaan->fetch_assoc()) {
    $transactions[] = [
        'tanggal' => $row['tanggal'], 'keterangan' => 'Penerimaan #' . $row['ref_id'],
        'masuk_jml' => $row['jumlah'], 'masuk_harga' => $row['harga_satuan'],
        'keluar_jml' => 0, 'keluar_harga' => 0
    ];
}

// 2. Ambil semua PENGELUARAN
$stmt_pengeluaran = $koneksi->prepare("SELECT p.id as id_permintaan, p.tanggal_diproses as tanggal, dp.jumlah, dp.nilai_keluar_fifo FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE dp.id_produk = ? AND p.status = 'Disetujui'");
$stmt_pengeluaran->bind_param("i", $id_produk);
$stmt_pengeluaran->execute();
$result_pengeluaran = $stmt_pengeluaran->get_result();
while ($row = $result_pengeluaran->fetch_assoc()) {
    $harga_satuan_keluar = ($row['jumlah'] > 0) ? $row['nilai_keluar_fifo'] / $row['jumlah'] : 0;
    $transactions[] = [
        'tanggal' => $row['tanggal'], 'keterangan' => 'Permintaan Disetujui #' . $row['id_permintaan'],
        'masuk_jml' => 0, 'masuk_harga' => 0,
        'keluar_jml' => $row['jumlah'], 'keluar_harga' => $harga_satuan_keluar
    ];
}

// 3. Urutkan semua transaksi berdasarkan tanggal
usort($transactions, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});
?>

<header class="main-header">
    <h1>Kartu Stok: <?php echo htmlspecialchars($produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')'); ?></h1>
    <p>Menampilkan riwayat pergerakan stok secara mendetail.</p>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2">Tanggal</th>
                    <th rowspan="2">Keterangan</th>
                    <th colspan="3">Masuk</th>
                    <th colspan="3">Keluar</th>
                    <th colspan="3">Saldo</th>
                </tr>
                <tr>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Nilai</th>
                    <th>Jml</th>
                    <th>Harga</th>
                    <th>Nilai</th>
                    <th>Jml</th>
                    <th>Harga Rata-Rata</th>
                    <th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>-</td>
                    <td><strong>Saldo Awal</strong></td>
                    <td colspan="6"></td>
                    <td class="text-center"><strong><?php echo $produk['stok_awal']; ?></strong></td>
                    <td class="text-right"><strong>Rp
                            <?php echo number_format($produk['harga_awal'], 0, ',', '.'); ?></strong></td>
                    <td class="text-right"><strong>Rp
                            <?php echo number_format($produk['stok_awal'] * $produk['harga_awal'], 0, ',', '.'); ?></strong>
                    </td>
                </tr>

                <?php
    // Proses transaksi untuk menghitung saldo berjalan
    $saldo_jml = $produk['stok_awal'];
    $saldo_nilai = $produk['stok_awal'] * $produk['harga_awal'];

    foreach ($transactions as $trans):
        $nilai_masuk = $trans['masuk_jml'] * $trans['masuk_harga'];
        $nilai_keluar = $trans['keluar_jml'] * $trans['keluar_harga'];

        // Hitung saldo baru
        $saldo_jml = $saldo_jml + $trans['masuk_jml'] - $trans['keluar_jml'];
        $saldo_nilai = $saldo_nilai + $nilai_masuk - $nilai_keluar;
        $harga_saldo = ($saldo_jml > 0) ? $saldo_nilai / $saldo_jml : 0;
    ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($trans['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($trans['keterangan']); ?></td>

                    <td class="text-center"><?php echo $trans['masuk_jml'] ?: '-'; ?></td>
                    <td class="text-right">
                        <?php echo ($trans['masuk_jml'] > 0) ? 'Rp ' . number_format($trans['masuk_harga'], 0, ',', '.') : '-'; ?>
                    </td>
                    <td class="text-right">
                        <?php echo ($trans['masuk_jml'] > 0) ? 'Rp ' . number_format($nilai_masuk, 0, ',', '.') : '-'; ?>
                    </td>

                    <td class="text-center"><?php echo $trans['keluar_jml'] ?: '-'; ?></td>
                    <td class="text-right">
                        <?php echo ($trans['keluar_jml'] > 0) ? 'Rp ' . number_format($trans['keluar_harga'], 0, ',', '.') : '-'; ?>
                    </td>
                    <td class="text-right">
                        <?php echo ($trans['keluar_jml'] > 0) ? 'Rp ' . number_format($nilai_keluar, 0, ',', '.') : '-'; ?>
                    </td>

                    <td class="text-center"><?php echo $saldo_jml; ?></td>
                    <td class="text-right">
                        <?php echo ($saldo_jml > 0) ? 'Rp ' . number_format($harga_saldo, 0, ',', '.') : '-'; ?></td>
                    <td class="text-right">
                        <?php echo ($saldo_jml > 0) ? 'Rp ' . number_format($saldo_nilai, 0, ',', '.') : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>