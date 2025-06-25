<?php
$page_title = 'Tambah Produk';
$active_page = 'produk';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') { echo "<p>Akses ditolak.</p>"; require_once 'template_footer.php'; exit; }

// Inisialisasi variabel
$spesifikasi = ''; $satuan = ''; $stok = 0; $harga = 0;
$id_kategori_pilihan = '';
$is_edit = false;
$id_produk = null;

// Ambil semua kategori untuk dropdown
$semua_kategori = $koneksi->query("SELECT id, nama_kategori, nusp_id FROM kategori_produk ORDER BY nama_kategori ASC");

// Mode Edit: jika ada ID di URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $id_produk = $_GET['id'];
    $page_title = 'Edit Produk';
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    if($produk) {
        $id_kategori_pilihan = $produk['id_kategori'];
        $spesifikasi = $produk['spesifikasi'];
        $satuan = $produk['satuan'];
        $stok = $produk['stok'];
        $harga = $produk['harga'];
    }
}
?>

<header class="main-header">
    <h1><?php echo $page_title; ?></h1>
    <p>Isi detail spesifik untuk produk di bawah kategori yang sesuai.</p>
</header>

<section class="content-section">
    <div class="form-container card">
        <form action="proses_produk.php" method="POST">
            <input type="hidden" name="aksi" value="<?php echo $is_edit ? 'edit' : 'tambah'; ?>">
            <?php if($is_edit) echo '<input type="hidden" name="id" value="' . $id_produk . '">'; ?>

            <div class="form-group">
                <label for="id_kategori">Kategori Barang</label>
                <select name="id_kategori" id="id_kategori" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while($kategori = $semua_kategori->fetch_assoc()): ?>
                        <option value="<?php echo $kategori['id']; ?>" <?php if($kategori['id'] == $id_kategori_pilihan) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($kategori['nama_kategori']) . ' (' . htmlspecialchars($kategori['nusp_id']) . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="spesifikasi">Spesifikasi (Merk, Ukuran, Tipe, dll)</label>
                <input type="text" id="spesifikasi" name="spesifikasi" class="form-control" value="<?php echo htmlspecialchars($spesifikasi); ?>" required>
            </div>
            <div class="form-group">
                <label for="satuan">Satuan</label>
                <input type="text" id="satuan" name="satuan" class="form-control" value="<?php echo htmlspecialchars($satuan); ?>" required>
            </div>
            <div class="form-group">
                <label for="harga">Harga Satuan</label>
                <input type="number" id="harga" name="harga" class="form-control" value="<?php echo htmlspecialchars($harga); ?>" step="any" min="0" required>
            </div>
            <?php if(!$is_edit): // Hanya tampilkan field stok & saldo awal saat menambah produk baru ?>
            <div class="form-group">
                <label for="stok">Stok Awal</label>
                <input type="number" id="stok" name="stok" class="form-control" value="<?php echo htmlspecialchars($stok); ?>" min="0" required>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
                <a href="produk.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>