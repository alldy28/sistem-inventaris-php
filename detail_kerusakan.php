<?php
// Langkah 1: Siapkan variabel untuk header
$page_title = 'Detail Laporan Kerusakan';
$active_page = 'kerusakan';

// Langkah 2: Panggil header DI PALING ATAS. 
// Ini akan menjalankan session_start() dan koneksi.php sekali saja.
require_once 'template_header.php';

// Langkah 3: Keamanan: Pastikan hanya admin yang bisa mengakses.
// Kita bisa lakukan ini SETELAH header dipanggil, karena $_SESSION sudah ada.
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// Langkah 4: Sekarang baru kita proses form POST jika ada
// Karena header sudah dipanggil, $_SESSION dan $koneksi sudah tersedia.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_perbaikan = $_POST['id_perbaikan'];
    $status_baru = $_POST['status_perbaikan'];
    $catatan_admin = $_POST['catatan_admin'];
    $tanggal_selesai = ($status_baru == 'Selesai' || $status_baru == 'Ditolak') ? date('Y-m-d H:i:s') : null;

    $stmt_update = $koneksi->prepare("UPDATE perbaikan_aset SET status_perbaikan = ?, catatan_admin = ?, tanggal_selesai = ? WHERE id = ?");
    $stmt_update->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_selesai, $id_perbaikan);
    
    if($stmt_update->execute()){
        // Redirect kembali ke halaman DAFTAR dengan notifikasi sukses
        header('Location: daftar_kerusakan.php?status_update=sukses');
        exit;
    } else {
        // Sebaiknya ada penanganan error yang lebih baik di sini
        echo "<div class='alert alert-danger'>Gagal mengupdate status.</div>";
    }
}

// Langkah 5: Logika untuk menampilkan data (GET request)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID Laporan tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_perbaikan = $_GET['id'];

// Ambil data laporan dari database
$stmt_get = $koneksi->prepare("SELECT pa.*, u.nama_lengkap FROM perbaikan_aset pa LEFT JOIN users u ON pa.id_user = u.id WHERE pa.id = ?");
$stmt_get->bind_param("i", $id_perbaikan);
$stmt_get->execute();
$laporan = $stmt_get->get_result()->fetch_assoc();

if (!$laporan) {
    echo "<p>Laporan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
?>
<header class="main-header">
    <h1>Detail Laporan Kerusakan #<?php echo $laporan['id']; ?></h1>
    <p>Diajukan oleh: <strong><?php echo htmlspecialchars($laporan['nama_lengkap'] ?? 'User Dihapus'); ?></strong> pada
        <?php echo date('d M Y, H:i', strtotime($laporan['tanggal_laporan'])); ?></p>
</header>

<section class="content-section">
    <div class="card form-container">
        <h3>Detail Laporan</h3>
        <div class="form-group">
            <label>Nama Aset</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['nama_aset']); ?></p>
        </div>
        <div class="form-group">
            <label>Komponen/Bagian yang Rusak</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['komponen_rusak']); ?></p>
        </div>
        <div class="form-group">
            <label>Deskripsi Kerusakan dari Pengguna</label>
            <div class="form-static-text" style="white-space: pre-wrap;">
                <?php echo htmlspecialchars($laporan['deskripsi_kerusakan']); ?></div>
        </div>
    </div>

    <div class="card form-container" style="margin-top:20px;">
        <h3>Proses Laporan</h3>
        <form action="detail_kerusakan.php?id=<?php echo $laporan['id']; ?>" method="POST">
            <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
            <div class="form-group">
                <label for="status_perbaikan">Ubah Status</label>
                <select name="status_perbaikan" id="status_perbaikan" class="form-control">
                    <option value="Baru" <?php if($laporan['status_perbaikan'] == 'Baru') echo 'selected'; ?>>Baru
                    </option>
                    <option value="Diproses" <?php if($laporan['status_perbaikan'] == 'Diproses') echo 'selected'; ?>>
                        Diproses</option>
                    <option value="Ditolak" <?php if($laporan['status_perbaikan'] == 'Ditolak') echo 'selected'; ?>>
                        Ditolak</option>
                </select>
            </div>
            <div class="form-group">
                <label for="catatan_admin">Catatan Admin (Tindakan yang dilakukan, dll)</label>
                <textarea name="catatan_admin" id="catatan_admin" rows="4"
                    class="form-control"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                <a href="daftar_kerusakan.php" class="btn btn-secondary">Kembali ke Daftar</a>
            </div>
        </form>
    </div>
    <?php if ($laporan['status_perbaikan'] != 'Baru' && $laporan['status_perbaikan'] != 'Selesai' && $laporan['status_perbaikan'] != 'Ditolak'): ?>
    <div class="card form-container" style="margin-top: 20px; border-left: 5px solid #2ecc71;">
        <h3>Tindakan Final</h3>
        <p>Gunakan tombol ini jika perbaikan sudah selesai dan Anda perlu mengeluarkan komponen dari stok.</p>
        <div class="form-actions">
            <a href="selesaikan_perbaikan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-success">Selesaikan &
                Catat Penggunaan Komponen</a>
        </div>
    </div>
    <?php endif; ?>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>