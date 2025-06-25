<?php
$page_title = 'Edit Kategori';
$active_page = 'kategori';
require_once 'template_header.php';

// Keamanan & Ambil Data
if ($_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
    echo "<p>Akses ditolak atau ID tidak valid.</p>"; require_once 'template_footer.php'; exit;
}

$id_kategori = $_GET['id'];
$stmt = $koneksi->prepare("SELECT * FROM kategori_produk WHERE id = ?");
$stmt->bind_param("i", $id_kategori);
$stmt->execute();
$kategori = $stmt->get_result()->fetch_assoc();

if (!$kategori) {
    echo "<p>Kategori tidak ditemukan.</p>"; require_once 'template_footer.php'; exit;
}
?>

<header class="main-header">
    <h1>Edit Kategori Produk</h1>
    <p>Perbarui data untuk kategori: <?php echo htmlspecialchars($kategori['nama_kategori']); ?></p>
</header>

<section class="content-section">
    <div class="form-container card">
        <form action="proses_kategori.php" method="POST">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" value="<?php echo $kategori['id']; ?>">
            <div class="form-group">
                <label for="nusp_id">ID NUSP</label>
                <input type="text" id="nusp_id" name="nusp_id" class="form-control" value="<?php echo htmlspecialchars($kategori['nusp_id']); ?>" required>
            </div>
            <div class="form-group">
                <label for="nama_kategori">Nama Kategori</label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" value="<?php echo htmlspecialchars($kategori['nama_kategori']); ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Kategori</button>
                <a href="kategori.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>