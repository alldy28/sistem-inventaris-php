<?php
$page_title = 'Histori Laporan Tahunan';
$active_page = 'histori_laporan';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<p class='content-section'>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil daftar tahun yang sudah diarsipkan dari tabel histori
$sql_tahun = "SELECT DISTINCT tahun_tutup_buku FROM histori_penerimaan ORDER BY tahun_tutup_buku DESC";
$result_tahun = $koneksi->query($sql_tahun);
$daftar_tahun = [];
while ($row = $result_tahun->fetch_assoc()) {
    $daftar_tahun[] = $row['tahun_tutup_buku'];
}
?>

<header class="main-header">
    <h1>Histori Laporan Tahunan</h1>
    <p>Lihat atau download kembali data laporan dari tahun-tahun yang sudah ditutup.</p>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>Tahun Laporan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_tahun)): ?>
                <tr>
                    <td colspan="2" class="text-center">Belum ada data yang diarsipkan.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($daftar_tahun as $tahun): ?>
                    <tr>
                        <td class="text-center"><?= $tahun; ?></td>
                        <td class="text-center">
                            <a href="download_histori.php?tahun=<?= $tahun; ?>" class="btn btn-primary">
                                Download Laporan <?= $tahun; ?> (CSV)
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>