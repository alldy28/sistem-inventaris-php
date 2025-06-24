<?php
$page_title = 'Catat Penerimaan Barang';
$active_page = 'penerimaan';
require_once 'template_header.php';

// Pastikan hanya admin yang bisa mengakses
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil daftar produk untuk dropdown
$produk_list = $koneksi->query("SELECT id, nama_barang FROM produk ORDER BY nama_barang ASC");
?>

<header class="main-header">
    <h1>Catat Penerimaan Barang Baru</h1>
    <p>Isi form di bawah untuk menambahkan stok barang baru.</p>
</header>

<section class="content-section">
    <div class="form-container">
        <form action="proses_penerimaan.php" method="POST">
            <div class="form-group">
                <label for="id_produk">Pilih Produk</label>
                <select name="id_produk" id="id_produk" class="form-control" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php while($produk = $produk_list->fetch_assoc()): ?>
                        <option value="<?php echo $produk['id']; ?>"><?php echo htmlspecialchars($produk['nama_barang']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah Diterima</label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label for="harga_satuan">Harga Satuan (Rp)</label>
                <input type="number" step="0.01" id="harga_satuan" name="harga_satuan" class="form-control" min="0" required>
            </div>
            <div class="form-group">
                <label for="catatan">Catatan (Nomor Faktur, Supplier, dll.)</label>
                <textarea name="catatan" id="catatan" rows="3" class="form-control"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                <a href="penerimaan.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>