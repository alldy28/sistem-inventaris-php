<?php
$page_title = 'Kartu Stok';
$active_page = 'laporan_realisasi';
require_once 'template_header.php';

/**
 * Mengambil dan menggabungkan SEMUA transaksi untuk sebuah produk menjadi satu alur waktu.
 *
 * @param mysqli $koneksi
 * @param int $id_produk
 * @return array Transaksi yang sudah terurut secara kronologis.
 */
function getStockCardTransactions(mysqli $koneksi, int $id_produk): array
{
    $transactions = [];

    // 1. Ambil SEMUA PENERIMAAN dan format sebagai transaksi 'MASUK'
    $stmt_penerimaan = $koneksi->prepare("
        SELECT 
            id as ref_id,
            tanggal_penerimaan as tanggal,
            jumlah,
            harga_satuan,
            (jumlah * harga_satuan) as nilai,
            'MASUK' as tipe,
            CONCAT('Penerimaan #', id) as keterangan
        FROM penerimaan 
        WHERE id_produk = ?
    ");
    $stmt_penerimaan->bind_param("i", $id_produk);
    $stmt_penerimaan->execute();
    $result_penerimaan = $stmt_penerimaan->get_result();
    while ($row = $result_penerimaan->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt_penerimaan->close();

    // 2. Ambil SEMUA PENGELUARAN dan format sebagai transaksi 'KELUAR'
    $stmt_pengeluaran = $koneksi->prepare("
        SELECT 
            p.id as ref_id,
            p.tanggal_diproses as tanggal,
            dp.jumlah_disetujui as jumlah,
            dp.nilai_keluar_fifo as nilai,
            'KELUAR' as tipe,
            CONCAT('Permintaan #', p.id) as keterangan
        FROM detail_permintaan dp 
        JOIN permintaan p ON dp.id_permintaan = p.id 
        WHERE dp.id_produk = ? AND p.status = 'Disetujui' AND dp.jumlah_disetujui > 0
    ");
    $stmt_pengeluaran->bind_param("i", $id_produk);
    $stmt_pengeluaran->execute();
    $result_pengeluaran = $stmt_pengeluaran->get_result();
    while ($row = $result_pengeluaran->fetch_assoc()) {
        // Hitung harga satuan rata-rata untuk transaksi keluar ini
        $row['harga_satuan'] = ($row['jumlah'] > 0) ? $row['nilai'] / $row['jumlah'] : 0;
        $transactions[] = $row;
    }
    $stmt_pengeluaran->close();

    // 3. Urutkan semua transaksi berdasarkan tanggal, lalu berdasarkan ID
    usort($transactions, function($a, $b) {
        $dateA = strtotime($a['tanggal']);
        $dateB = strtotime($b['tanggal']);
        if ($dateA == $dateB) {
            // Jika tanggal sama, dahulukan MASUK sebelum KELUAR
            if ($a['tipe'] === 'MASUK' && $b['tipe'] === 'KELUAR') return -1;
            if ($a['tipe'] === 'KELUAR' && $b['tipe'] === 'MASUK') return 1;
            return $a['ref_id'] <=> $b['ref_id']; // Urutkan berdasarkan ID jika tipe sama
        }
        return ($dateA < $dateB) ? -1 : 1;
    });
    
    return $transactions;
}

// --- LOGIKA UTAMA HALAMAN ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    echo "<p class='content-section'>Akses ditolak atau ID produk tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_produk = (int)$_GET['id_produk'];

$stmt_produk = $koneksi->prepare("SELECT pr.spesifikasi, kp.nama_kategori FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id WHERE pr.id = ?");
$stmt_produk->bind_param("i", $id_produk);
$stmt_produk->execute();
$produk = $stmt_produk->get_result()->fetch_assoc();
if (!$produk) {
    echo "<p class='content-section'>Produk tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
$stmt_produk->close();

$transactions = getStockCardTransactions($koneksi, $id_produk);
?>

<header class="main-header">
    <h1>Kartu Stok: <?php echo htmlspecialchars($produk['nama_kategori'] . ' ' . $produk['spesifikasi']); ?></h1>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:15%;">Tanggal</th>
                    <th rowspan="2">Keterangan</th>
                    <th colspan="3">Masuk</th>
                    <th colspan="3">Keluar</th>
                    <th colspan="3">Saldo</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                    <th>Jml</th><th>Harga</th><th>Nilai</th>
                    <th>Jml</th><th>Harga Avg.</th><th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Inisialisasi saldo berjalan
                $saldo_jumlah = 0;
                $saldo_nilai = 0;
                ?>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="11" class="text-center">Belum ada transaksi untuk produk ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($trans['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($trans['keterangan']); ?></td>
                            
                            <?php if ($trans['tipe'] == 'MASUK'): ?>
                                <?php
                                    $saldo_jumlah += $trans['jumlah'];
                                    $saldo_nilai += $trans['nilai'];
                                ?>
                                <td class="text-center"><?php echo number_format($trans['jumlah']); ?></td>
                                <td class="text-right">Rp <?php echo number_format($trans['harga_satuan']); ?></td>
                                <td class="text-right">Rp <?php echo number_format($trans['nilai']); ?></td>
                                <td colspan="3" class="text-center">-</td>
                            <?php else: // KELUAR ?>
                                <?php
                                    $saldo_jumlah -= $trans['jumlah'];
                                    $saldo_nilai -= $trans['nilai'];
                                ?>
                                <td colspan="3" class="text-center">-</td>
                                <td class="text-center"><?php echo number_format($trans['jumlah']); ?></td>
                                <td class="text-right">Rp <?php echo number_format($trans['harga_satuan']); ?></td>
                                <td class="text-right">Rp <?php echo number_format($trans['nilai']); ?></td>
                            <?php endif; ?>
                            
                            <td class="text-center" style="background-color: #f8f9fa;"><strong><?php echo number_format($saldo_jumlah); ?></strong></td>
                            <td class="text-right" style="background-color: #f8f9fa;"><strong>Rp <?php echo number_format(($saldo_jumlah > 0) ? $saldo_nilai / $saldo_jumlah : 0); ?></strong></td>
                            <td class="text-right" style="background-color: #f8f9fa;"><strong>Rp <?php echo number_format($saldo_nilai); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>