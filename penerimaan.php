<?php
$page_title = 'Riwayat Penerimaan';
$active_page = 'penerimaan';
require_once 'template_header.php';

// Pastikan hanya admin yang bisa mengakses
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

// Query SQL yang sudah dipastikan mengambil harga_satuan
$sql = "SELECT pn.id, p.nama_barang, pn.jumlah, pn.harga_satuan, pn.tanggal_penerimaan
        FROM penerimaan pn
        JOIN produk p ON pn.id_produk = p.id
        ORDER BY pn.tanggal_penerimaan DESC";
$result = $koneksi->query($sql);
?>

<header class="main-header">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'saldo_awal_sukses'): ?>
    <div class="alert alert-success">
        Saldo Awal untuk semua produk berhasil diperbarui!
    </div>
    <?php endif; ?>
    <h1>Riwayat Penerimaan Barang</h1>
    <p>Berikut adalah semua riwayat penambahan stok barang.</p>
</header>

<section class="content-section">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
    <div class="alert alert-success">
        Penerimaan barang baru berhasil dicatat!
    </div>
    <?php endif; ?>

    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center;">
        <a href="form_penerimaan.php" class="btn btn-primary">Catat Penerimaan Baru</a>

        <form action="proses_saldo_awal.php" method="POST"
            onsubmit="return confirm('PERINGATAN: Aksi ini akan menimpa data Saldo Awal sebelumnya dengan data STOK saat ini. Ini biasanya hanya dilakukan di akhir periode (tahunan/bulanan). Lanjutkan?');">
            <button type="submit" name="set_saldo_awal" class="btn btn-secondary">Jadikan Stok Saat Ini Sebagai Saldo
                Awal</button>
        </form>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Jumlah Diterima</th>
                    <th>Harga Satuan</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo $row['jumlah']; ?></td>
                    <td>Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_penerimaan'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">Belum ada riwayat penerimaan barang.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>