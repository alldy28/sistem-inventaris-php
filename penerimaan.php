<?php
$page_title = 'Riwayat Penerimaan';
$active_page = 'penerimaan';
require_once 'template_header.php';

// Keamanan
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

// <<-- PERBAIKAN: Query diubah untuk JOIN 3 tabel -->>
$sql = "SELECT 
            pn.id, pn.jumlah, pn.harga_satuan, pn.tanggal_penerimaan, pn.nomor_faktur,
            pr.spesifikasi, 
            kp.nama_kategori
        FROM penerimaan pn
        JOIN produk pr ON pn.id_produk = pr.id
        JOIN kategori_produk kp ON pr.id_kategori = kp.id
        ORDER BY pn.tanggal_penerimaan DESC";
$result = $koneksi->query($sql);
?>

<header class="main-header">
    <h1>Riwayat Penerimaan Barang</h1>
    <p>Berikut adalah semua riwayat penambahan stok barang yang tercatat.</p>
</header>

<section class="content-section">
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="form_penerimaan.php" class="btn btn-primary">Catat Penerimaan Baru</a>
        </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Harga Satuan</th>
                    <th>Tanggal</th>
                    <th>No. Faktur</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        > -->
                        <td><?php echo htmlspecialchars($row['nama_kategori'] . ' (' . $row['spesifikasi'] . ')'); ?></td>
                        <td><?php echo $row['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_penerimaan'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nomor_faktur']); ?></td>
                        <td class="action-links">
                            <a href="cetak_penerimaan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-edit" style="background-color:#3498db;">Cetak</a>
                            <a href="proses_penerimaan.php?aksi=hapus&id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('PERINGATAN! Anda yakin ingin menghapus data penerimaan ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">Belum ada riwayat penerimaan barang.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>