<?php
// Definisikan variabel untuk template dan logika awal
$page_title = 'Tambah Produk'; // Judul default
$active_page = 'produk'; // Halaman ini bagian dari menu 'Produk'

// Panggil template header. Sesi akan dimulai dan diperiksa di sini.
require_once 'template_header.php';

// Pastikan hanya admin yang bisa lanjut setelah header di-load
if ($_SESSION['role'] !== 'admin') {
    // Redirect bisa diarahkan ke halaman produk (tanpa akses) atau dashboard
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php'; // Tutup halaman dengan benar
    exit;
}

// Inisialisasi variabel untuk form
$action = 'tambah';
$nusp_id = '';
$nama_barang = '';
$satuan = '';
$stok = '';
$harga = '';
$id_produk = null;

// Cek apakah ini mode EDIT (ada 'id' di URL)
if (isset($_GET['id'])) {
    $id_produk = $_GET['id'];
    $page_title = 'Edit Produk'; // Ganti judul jika mode edit
    $action = 'edit';

    // Ambil data produk dari DB
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $produk = $result->fetch_assoc();
        $nusp_id = $produk['nusp_id'];
        $nama_barang = $produk['nama_barang'];
        $satuan = $produk['satuan'];
        $stok = $produk['stok'];
        $harga = $produk['harga'];
    } else {
        header('Location: produk.php?status=notfound');
        exit;
    }
    $stmt->close();
}
?>

<header class="main-header">
    <h1><?php echo $page_title; ?></h1>
    <p>Silakan isi form di bawah ini untuk <?php echo ($action == 'tambah') ? 'menambahkan produk baru' : 'memperbarui data produk'; ?>.</p>
</header>

<section class="content-section">
    <div class="form-container">
        <form action="proses_produk.php" method="POST">
            <input type="hidden" name="aksi" value="<?php echo $action; ?>">
            <?php if ($id_produk): ?>
                <input type="hidden" name="id" value="<?php echo $id_produk; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nusp_id">ID NUSP</label>
                <input type="text" id="nusp_id" name="nusp_id" value="<?php echo htmlspecialchars($nusp_id); ?>" required>
            </div>
            <div class="form-group">
                <label for="nama_barang">Nama Barang</label>
                <input type="text" id="nama_barang" name="nama_barang" value="<?php echo htmlspecialchars($nama_barang); ?>" required>
            </div>
            <div class="form-group">
                <label for="satuan">Satuan Barang</label>
                <input type="text" id="satuan" name="satuan" value="<?php echo htmlspecialchars($satuan); ?>" required>
            </div>
            <div class="form-group">
                <label for="stok">Stok Barang</label>
                <input type="number" id="stok" name="stok" value="<?php echo htmlspecialchars($stok); ?>" required>
            </div>
            <div class="form-group">
                <label for="harga">Harga</label>
                <input type="number" step="0.01" id="harga" name="harga" value="<?php echo htmlspecialchars($harga); ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Data</button>
                <a href="produk.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php
// Panggil template footer
require_once 'template_footer.php';
?>