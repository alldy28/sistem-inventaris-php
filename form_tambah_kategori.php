<?php
$page_title = 'Tambah Kategori';
$active_page = 'kategori';
require_once 'template_header.php';

// Keamanan
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Akses ditolak.</p>"; 
    require_once 'template_footer.php'; 
    exit;
}
?>

<header class="main-header">
    <h1>Tambah Kategori Produk Baru</h1>
    <p>Isi detail untuk kategori umum barang.</p>
</header>

<section class="content-section">
    <div class="form-container card">
        <form action="proses_kategori.php" method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label for="nusp_id">ID NUSP</label>
                <input type="text" id="nusp_id" name="nusp_id" class="form-control" placeholder="Masukkan ID NUSP yang unik" required>
            </div>
            <div class="form-group">
                <label for="nama_kategori">Nama Kategori</label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Contoh: Alat Tulis Kantor, Peralatan Kebersihan" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                <a href="kategori.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>