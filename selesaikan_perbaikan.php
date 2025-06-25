<?php
$page_title = 'Selesaikan Perbaikan';
$active_page = 'kerusakan';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin' || !isset($_GET['id'])) { /* ... (Keamanan dasar) ... */ exit; }

$id_perbaikan = $_GET['id'];
$laporan = $koneksi->query("SELECT * FROM perbaikan_aset WHERE id=$id_perbaikan")->fetch_assoc();
$produk_list = $koneksi->query("SELECT pr.id, kp.nama_kategori, pr.spesifikasi FROM produk pr JOIN kategori_produk kp ON pr.id_kategori = kp.id ORDER BY kp.nama_kategori, pr.spesifikasi ASC");
?>

<header class="main-header">
    <h1>Finalisasi Perbaikan Aset #<?php echo $laporan['id']; ?></h1>
    <p>Pilih komponen yang digunakan dari stok untuk menyelesaikan perbaikan ini.</p>
</header>

<section class="content-section">
    <div class="card">
        <p><strong>Aset yang Diperbaiki:</strong> <?php echo htmlspecialchars($laporan['nama_aset']); ?></p>
        <p><strong>Kerusakan:</strong> <?php echo htmlspecialchars($laporan['komponen_rusak']); ?></p>
    </div>
    <div class="card form-container" style="margin-top:20px;">
        <h2>Pencatatan Penggunaan Komponen/Sparepart</h2>
        <form action="proses_penyelesaian.php" method="POST">
            <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
            <input type="hidden" name="id_user_peminta" value="<?php echo $laporan['id_user']; ?>">
            
            <div class="form-group">
                <label for="id_produk">Pilih Komponen dari Stok</label>
                <select name="id_produk" id="id_produk" class="form-control" required>
                    <option value="">-- Pilih Produk/Sparepart --</option>
                    <?php while($produk = $produk_list->fetch_assoc()): ?>
                        <option value="<?php echo $produk['id']; ?>"><?php echo htmlspecialchars($produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')'); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah Digunakan</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" required min="1">
            </div>
            <div class="form-group">
                <label for="catatan_admin">Catatan Final Admin</label>
                <textarea name="catatan_admin" id="catatan_admin" rows="4" class="form-control"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="selesaikan" class="btn btn-success">Selesaikan Perbaikan</button>
                <a href="detail_kerusakan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>