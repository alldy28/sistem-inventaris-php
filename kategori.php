<?php
$page_title = 'Manajemen Kategori';
$active_page = 'kategori';
require_once 'template_header.php';

// Keamanan: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Akses ditolak.</p>"; require_once 'template_footer.php'; exit;
}

// Ambil semua data kategori untuk ditampilkan di tabel
$list_kategori = $koneksi->query("SELECT * FROM kategori_produk ORDER BY nama_kategori ASC");
?>

<header class="main-header">
    <h1>Manajemen Kategori Produk</h1>
    <p>Kelola kategori umum untuk semua produk Anda.</p>
</header>

<section class="content-section">
    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success">
            <?php 
                if($_GET['status'] == 'sukses_tambah') echo 'Kategori baru berhasil ditambahkan!';
                if($_GET['status'] == 'sukses_edit') echo 'Data kategori berhasil diperbarui!';
                if($_GET['status'] == 'sukses_hapus') echo 'Kategori berhasil dihapus!';
            ?>
        </div>
    <?php endif; ?>

    <div class="card form-container">
        <h2>Tambah Kategori Baru</h2>
        <form action="proses_kategori.php" method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label for="nusp_id">ID NUSP</label>
                <input type="text" id="nusp_id" name="nusp_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="nama_kategori">Nama Kategori (Nama Umum Barang)</label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Daftar Kategori yang Ada</h2>
        <div class="table-container">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID NUSP</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_kategori && $list_kategori->num_rows > 0): ?>
                        <?php while($row = $list_kategori->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nusp_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                            <td class="action-links">
                                <a href="form_kategori.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <a href="proses_kategori.php?aksi=hapus&id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('PERINGATAN! Menghapus kategori ini juga akan menghapus SEMUA produk spesifik di dalamnya. Anda yakin?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Belum ada kategori yang ditambahkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>