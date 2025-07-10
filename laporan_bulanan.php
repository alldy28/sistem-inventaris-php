<?php
$page_title = 'Laporan Bulanan Persediaan';
$active_page = 'laporan_bulanan';
require_once 'template_header.php';
require_once 'laporan_functions.php'; // Asumsikan Anda punya template header

if ($_SESSION['role'] !== 'admin') {
    echo "<p class='content-section'>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// =================================================================
// FUNGSI LAPORAN BULANAN (TIDAK ADA PERUBAHAN DI SINI)
// =================================================================


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
            <div class="action-bar" style="display:flex; justify-content:flex-start; gap: 10px; margin-bottom: 20px;">
                <a href="download_laporan_bulanan.php?bulan=<?= $bulan_terpilih; ?>&tahun=<?= $tahun_terpilih; ?>" target="_blank" class="btn btn-success">
                    Download Laporan (CSV)
                </a>
                <button type="submit" name="filter" value="true" class="btn btn-primary">Tampilkan</button>
            </div>
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