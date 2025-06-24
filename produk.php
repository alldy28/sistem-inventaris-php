<?php
$page_title = 'Daftar Produk';
$active_page = 'produk';
require_once 'template_header.php';

// --- LOGIKA PENCARIAN (TETAP SAMA) ---
$keyword = $_GET['keyword'] ?? '';
$sql = "SELECT id, nusp_id, nama_barang, satuan, stok, harga FROM produk";
$params = [];
$types = '';

if (!empty($keyword)) {
    $search_term = "%" . $keyword . "%";
    $sql .= " WHERE (nama_barang LIKE ? OR nusp_id LIKE ?)";
    $params[] = &$search_term;
    $params[] = &$search_term;
    $types .= "ss";
}
$sql .= " ORDER BY id ASC";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $koneksi->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
// --- AKHIR LOGIKA PENCARIAN ---
?>

<header class="main-header">
    <h1>Daftar Produk</h1>
    <p>Pilih produk yang Anda butuhkan untuk diajukan permintaannya.</p>
</header>

<section class="content-section">

    <?php if(isset($_GET['status']) && $_GET['status'] == 'keranjang_update'): ?>
    <div class="alert alert-success">
        Keranjang berhasil diperbarui!
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="action-bar">
            <a href="form_produk.php" class="btn btn-primary">Tambah Produk Baru</a>
        </div>
    <?php endif; ?>

    <div class="search-form-container">
        <form action="produk.php" method="GET">
            <input type="text" name="keyword" placeholder="Cari berdasarkan Nama Barang atau ID NUSP..." value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="produk.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID NUSP</th>
                    <th>Nama Barang</th>
                    <th>Stok</th>

                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>Harga</th>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'user'): ?>
                        <th style="width: 150px;">Jumlah Permintaan</th>
                    <?php endif; ?>

                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $no = 1; while($produk = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($produk['nusp_id']); ?></td>
                        <td><?php echo htmlspecialchars($produk['nama_barang']); ?></td>
                        <td><?php echo $produk['stok']; ?> <?php echo htmlspecialchars($produk['satuan']); ?></td>
                        
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <td>Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] == 'user'): ?>
                            <td>
                                <form action="proses_keranjang.php" method="POST" class="form-keranjang-inline">
                                    <input type="number" name="jumlah" class="input-jumlah" value="1" min="1" max="<?php echo $produk['stok']; ?>" required>
                                    <input type="hidden" name="id_produk" value="<?php echo $produk['id']; ?>">
                            </td>
                            <td class="action-links">
                                    <button type="submit" name="tambah_ke_keranjang" class="btn btn-primary btn-sm">
                                        + Keranjang
                                    </button>
                                </form>
                            </td>
                        <?php else: // Untuk Admin ?>
                            <td class="action-links">
                                <a href="form_produk.php?id=<?php echo $produk['id']; ?>" class="btn-edit">Edit</a>
                                <a href="proses_produk.php?aksi=hapus&id=<?php echo $produk['id']; ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php 
                        // PERUBAHAN #3: Colspan sekarang sama untuk kedua role, yaitu 6
                        $colspan = 6;
                    ?>
                    <tr><td colspan="<?php echo $colspan; ?>">
                        <?php echo !empty($keyword) ? 'Produk tidak ditemukan.' : 'Tidak ada data produk.'; ?>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$koneksi->close();
require_once 'template_footer.php';
?>