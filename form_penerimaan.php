<?php
$page_title = 'Catat Penerimaan Barang';
$active_page = 'penerimaan';
require_once 'template_header.php';

// Keamanan
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

// <<-- PERBAIKAN: Query untuk dropdown diubah dengan JOIN -->>
$sql_produk = "SELECT pr.id, pr.spesifikasi, kp.nama_kategori 
               FROM produk pr 
               JOIN kategori_produk kp ON pr.id_kategori = kp.id 
               ORDER BY kp.nama_kategori, pr.spesifikasi ASC";
$produk_list = $koneksi->query($sql_produk);
?>

<header class="main-header">
    <h1>Catat Penerimaan Barang Baru</h1>
    <p>Isi form di bawah untuk menambahkan stok barang baru.</p>
</header>

<section class="content-section">
    <div class="form-container card">
        <form action="proses_penerimaan.php" method="POST">
            <div class="form-group">
                <label for="id_produk">Nama Barang</label>
                <select id="id_produk" name="id_produk" class="form-control" required>
                    <option value="">-- Pilih Barang Spesifik --</option>
                    <?php while($produk = $produk_list->fetch_assoc()): ?>
                        > -->
                        <option value="<?php echo $produk['id']; ?>">
                            <?php echo htmlspecialchars($produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah Diterima</label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label for="harga_satuan">Harga Satuan (Rp)</label>
                <input type="number" id="harga_satuan" name="harga_satuan" class="form-control" min="0" step="any" required>
            </div>
            <div class="form-group">
                <label for="bentuk_kontrak">Bentuk Kontrak</label>
                <input type="text" id="bentuk_kontrak" name="bentuk_kontrak" class="form-control">
            </div>
            <div class="form-group">
                <label for="nama_penyedia">Nama Penyedia</label>
                <input type="text" id="nama_penyedia" name="nama_penyedia" class="form-control">
            </div>
            <div class="form-group">
                <label for="nomor_faktur">Nomor Faktur/Dokumen</label>
                <input type="text" id="nomor_faktur" name="nomor_faktur" class="form-control">
            </div>
            <div class="form-group">
                <label for="sumber_anggaran">Sumber Anggaran</label>
                <input type="text" id="sumber_anggaran" name="sumber_anggaran" class="form-control">
            </div>
            <div class="form-group">
                <label for="catatan">Catatan (Opsional)</label>
                <textarea name="catatan" id="catatan" rows="3" class="form-control"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Catat Penerimaan</button>
                <a href="penerimaan.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>