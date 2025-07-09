<?php
$page_title = 'Laporan Bulanan Persediaan';
$active_page = 'laporan_bulanan';
require_once 'template_header.php'; // Asumsikan Anda punya template header

if ($_SESSION['role'] !== 'admin') {
    echo "<p class='content-section'>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// =================================================================
// FUNGSI LAPORAN BULANAN (TIDAK ADA PERUBAHAN DI SINI)
// =================================================================
function getMonthlyInventoryReport(mysqli $koneksi, int $bulan, int $tahun): array {
    $tanggal_awal = date('Y-m-d H:i:s', mktime(0, 0, 0, $bulan, 1, $tahun));
    $tanggal_akhir = date('Y-m-t H:i:s', mktime(23, 59, 59, $bulan, 1, $tahun));

    $sql = "
        SELECT
            p.id AS id_produk, kp.nama_kategori, p.spesifikasi, p.satuan,
            (
                COALESCE((SELECT SUM(sb.jumlah_awal) FROM stok_batch sb WHERE sb.id_produk = p.id AND sb.tanggal_masuk < '$tanggal_awal'), 0) +
                COALESCE((SELECT SUM(pr.jumlah) FROM penerimaan pr WHERE pr.id_produk = p.id AND pr.tanggal_penerimaan < '$tanggal_awal'), 0) -
                COALESCE((SELECT SUM(dp.jumlah_disetujui) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan < '$tanggal_awal'), 0)
            ) AS saldo_awal_jumlah,
            (
                COALESCE((SELECT SUM(sb.jumlah_awal * sb.harga_beli) FROM stok_batch sb WHERE sb.id_produk = p.id AND sb.tanggal_masuk < '$tanggal_awal'), 0) +
                COALESCE((SELECT SUM(pr.jumlah * pr.harga_satuan) FROM penerimaan pr WHERE pr.id_produk = p.id AND pr.tanggal_penerimaan < '$tanggal_awal'), 0) -
                COALESCE((SELECT SUM(dp.nilai_keluar_fifo) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan < '$tanggal_awal'), 0)
            ) AS saldo_awal_nilai,
            COALESCE((SELECT SUM(jumlah) FROM penerimaan WHERE id_produk = p.id AND tanggal_penerimaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS penerimaan_jumlah,
            COALESCE((SELECT SUM(jumlah * harga_satuan) FROM penerimaan WHERE id_produk = p.id AND tanggal_penerimaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS penerimaan_nilai,
            COALESCE((SELECT SUM(dp.jumlah_disetujui) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS pengeluaran_jumlah,
            COALESCE((SELECT SUM(dp.nilai_keluar_fifo) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS pengeluaran_nilai
        FROM produk p
        JOIN kategori_produk kp ON p.id_kategori = kp.id
        ORDER BY kp.nama_kategori, p.spesifikasi ASC
    ";
    
    $result = $koneksi->query($sql);
    $report_data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['saldo_awal_jumlah'] == 0 && $row['penerimaan_jumlah'] == 0 && $row['pengeluaran_jumlah'] == 0) continue;
        $row['saldo_akhir_jumlah'] = $row['saldo_awal_jumlah'] + $row['penerimaan_jumlah'] - $row['pengeluaran_jumlah'];
        $row['saldo_akhir_nilai'] = $row['saldo_awal_nilai'] + $row['penerimaan_nilai'] - $row['pengeluaran_nilai'];
        $report_data[] = $row;
    }
    return $report_data;
}

// =================================================================
// LOGIKA FILTER DENGAN VALIDASI PERIODE
// =================================================================
$bulan_terpilih = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun_terpilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$laporan_data = [];
$pesan_error = '';

if (isset($_GET['filter'])) {
    // **PENAMBAHAN LOGIKA VALIDASI**
    // Buat timestamp untuk hari pertama bulan yang dipilih
    $timestamp_terpilih = mktime(0, 0, 0, $bulan_terpilih, 1, $tahun_terpilih);
    // Buat timestamp untuk hari pertama bulan saat ini
    $timestamp_sekarang = mktime(0, 0, 0, date('m'), 1, date('Y'));

    if ($timestamp_terpilih > $timestamp_sekarang) {
        // Jika bulan yang dipilih ada di masa depan, jangan proses data.
        $pesan_error = "Laporan untuk periode mendatang tidak dapat ditampilkan.";
    } else {
        // Jika periode valid (sekarang atau masa lalu), proses data.
        $laporan_data = getMonthlyInventoryReport($koneksi, $bulan_terpilih, $tahun_terpilih);
    }
}

$nama_bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
?>

<header class="main-header">
    <h1>Laporan Bulanan Persediaan</h1>
    <p>Laporan ini menampilkan ringkasan pergerakan barang per bulan.</p>
</header>

<section class="content-section">
    <div class="filter-form-container" style="margin-bottom: 20px;">
        <form action="" method="GET" class="form-inline">
            <div class="form-group"><label for="bulan">Bulan:</label>
                <select name="bulan" id="bulan" class="form-control">
                    <?php foreach ($nama_bulan as $nomor => $nama): ?>
                        <option value="<?= $nomor; ?>" <?= ($nomor == $bulan_terpilih) ? 'selected' : ''; ?>><?= $nama; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="tahun">Tahun:</label>
                <select name="tahun" id="tahun" class="form-control">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i; ?>" <?= ($i == $tahun_terpilih) ? 'selected' : ''; ?>><?= $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" name="filter" value="true" class="btn btn-primary">Tampilkan</button>
        </form>
    </div>

    <?php if (isset($_GET['filter'])): ?>
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-warning" role="alert">
                <?= htmlspecialchars($pesan_error); ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="product-table report-table">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th><th rowspan="2">Nama Barang</th><th rowspan="2">Spesifikasi</th>
                            <th colspan="2">Saldo Awal</th><th colspan="2">Penerimaan</th>
                            <th colspan="2">Pengeluaran</th><th colspan="2">Saldo Akhir</th>
                        </tr>
                        <tr>
                            <th>Jml</th><th>Nilai</th><th>Jml</th><th>Nilai</th>
                            <th>Jml</th><th>Nilai</th><th>Jml</th><th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporan_data)): ?>
                        <tr><td colspan="11" class="text-center">Tidak ada data transaksi pada periode <strong><?= $nama_bulan[$bulan_terpilih] . ' ' . $tahun_terpilih; ?></strong>.</td></tr>
                        <?php else: $no = 1; $total_saldo_awal_nilai = 0; $total_penerimaan_nilai = 0; $total_pengeluaran_nilai = 0; $total_saldo_akhir_nilai = 0; foreach ($laporan_data as $item): $total_saldo_awal_nilai += $item['saldo_awal_nilai']; $total_penerimaan_nilai += $item['penerimaan_nilai']; $total_pengeluaran_nilai += $item['pengeluaran_nilai']; $total_saldo_akhir_nilai += $item['saldo_akhir_nilai']; ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($item['nama_kategori']); ?></td><td><?= htmlspecialchars($item['spesifikasi']); ?></td>
                            <td class="text-center"><?= number_format($item['saldo_awal_jumlah']); ?></td>
                            <td class="text-right">Rp <?= number_format($item['saldo_awal_nilai']); ?></td>
                            <td class="text-center"><?= number_format($item['penerimaan_jumlah']); ?></td>
                            <td class="text-right">Rp <?= number_format($item['penerimaan_nilai']); ?></td>
                            <td class="text-center"><?= number_format($item['pengeluaran_jumlah']); ?></td>
                            <td class="text-right">Rp <?= number_format($item['pengeluaran_nilai']); ?></td>
                            <td class="text-center"><?= number_format($item['saldo_akhir_jumlah']); ?></td>
                            <td class="text-right">Rp <?= number_format($item['saldo_akhir_nilai']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-center"><strong>GRAND TOTAL</strong></td><td></td>
                            <td class="text-right"><strong>Rp <?= number_format($total_saldo_awal_nilai); ?></strong></td><td></td>
                            <td class="text-right"><strong>Rp <?= number_format($total_penerimaan_nilai); ?></strong></td><td></td>
                            <td class="text-right"><strong>Rp <?= number_format($total_pengeluaran_nilai); ?></strong></td><td></td>
                            <td class="text-right"><strong>Rp <?= number_format($total_saldo_akhir_nilai); ?></strong></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once 'template_footer.php'; ?>