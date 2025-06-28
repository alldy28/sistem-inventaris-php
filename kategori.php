<?php
$page_title = 'Daftar Kategori';
$active_page = 'kategori';
require_once 'template_header.php';

// Keamanan: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// Logika Pencarian
$keyword = $_GET['keyword'] ?? '';
if (!empty($keyword)) {
    $stmt = $koneksi->prepare("SELECT * FROM kategori_produk WHERE nusp_id LIKE ? OR nama_kategori LIKE ? ORDER BY nama_kategori ASC");
    $search_term = "%" . $keyword . "%";
    $stmt->bind_param("ss", $search_term, $search_term);
} else {
    $stmt = $koneksi->prepare("SELECT * FROM kategori_produk ORDER BY nama_kategori ASC");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<header class="main-header">
    <h1>Daftar Kategori Produk</h1>
    <p>Kelola semua kategori umum untuk pengelompokan produk.</p>
</header>

<section class="content-section">
    <?php if(isset($_GET['status'])): ?>
    <div class="alert alert-success">
        <?php
                if ($_GET['status'] == 'sukses_tambah') echo "Kategori baru berhasil ditambahkan.";
                if ($_GET['status'] == 'sukses_edit') echo "Kategori berhasil diperbarui.";
                if ($_GET['status'] == 'sukses_hapus') echo "Kategori berhasil dihapus.";
                if ($_GET['status'] == 'import_sukses') echo "Impor kategori dari file CSV berhasil.";
            ?>
    </div>
    <?php elseif(isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <?php
                if ($_GET['error'] == 'import_gagal') echo "Gagal mengimpor data. Pastikan format file CSV sudah benar.";
            ?>
    </div>
    <?php endif; ?>

    <div class="card form-container" style="margin-bottom: 25px;">
        <h2>Manajemen Kategori</h2>
        <div class="action-bar"
            style="margin-top: 15px; padding-top:15px; border-top: 1px solid #eee; display:flex; justify-content:space-between;">
            <a href="form_tambah_kategori.php" class="btn btn-primary">Tambah Kategori Baru (Manual)</a>
            <a href="template_kategori.csv" download class="btn btn-secondary">Download Template CSV</a>
        </div>
        <form action="proses_upload_kategori.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
            <div class="form-group">
                <label for="file_kategori"><strong>Impor dari File CSV:</strong></label>
                <input type="file" name="file_kategori" id="file_kategori" class="form-control" required accept=".csv">
            </div>
            <div class="form-actions">
                <button type="submit" name="upload" class="btn btn-success">Unggah dan Impor</button>
            </div>
        </form>
    </div>

    <div class="search-form-container">
        <form action="kategori.php" method="GET">
            <input type="text" name="keyword" placeholder="Cari berdasarkan ID NUSP atau Nama Kategori..."
                value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="kategori.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th style="width: 150px;">ID NUSP</th>
                    <th>Nama Kategori</th>
                    <th style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while($kategori = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($kategori['nusp_id']); ?></td>
                    <td><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                    <td class="action-links">
                    <a href="form_edit_kategori.php?id=<?php echo $kategori['id']; ?>" class="btn-edit">Edit</a>
                        <a href="proses_kategori.php?aksi=hapus&id=<?php echo $kategori['id']; ?>" class="btn-delete"
                            onclick="return confirm('PERINGATAN! Menghapus kategori ini juga akan menghapus SEMUA produk spesifik di dalamnya. Anda yakin?');">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="3">Tidak ada data kategori.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php $koneksi->close(); require_once 'template_footer.php'; ?>