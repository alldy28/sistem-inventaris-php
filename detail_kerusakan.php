<?php
$page_title = 'Detail Laporan Kerusakan';
$active_page = 'kerusakan';
require_once 'template_header.php';

// Keamanan
if ($_SESSION['role'] !== 'admin' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Akses ditolak atau ID tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_perbaikan = $_GET['id'];

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
    <p>Diajukan oleh: <strong><?php echo htmlspecialchars($laporan['nama_lengkap'] ?? 'User Dihapus'); ?></strong> pada <?php echo date('d M Y, H:i', strtotime($laporan['tanggal_laporan'])); ?></p>
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
            <div class="form-static-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($laporan['deskripsi_kerusakan']); ?></div>
        </div>
    </div>

    <?php if($laporan['status_perbaikan'] == 'Selesai' || $laporan['status_perbaikan'] == 'Ditolak'): 
        $stmt_komponen = $koneksi->prepare("SELECT * FROM komponen_perbaikan WHERE id_perbaikan = ?");
        $stmt_komponen->bind_param("i", $laporan['id']);
        $stmt_komponen->execute();
        $komponen_result = $stmt_komponen->get_result();
    ?>
    <div class="card" style="margin-top: 20px;">
        <h3><?php echo ($laporan['status_perbaikan'] == 'Selesai') ? 'Komponen/Jasa yang Digunakan' : 'Hasil Proses'; ?></h3>
        <?php if ($komponen_result && $komponen_result->num_rows > 0): ?>
            <div class="table-container">
                <table class="product-table">
                    <thead><tr><th>Nama Komponen/Jasa</th><th>Jumlah</th><th>Harga Satuan</th><th>Total Harga</th></tr></thead>
                    <tbody>
                        <?php while($komp = $komponen_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($komp['nama_komponen']); ?></td>
                            <td><?php echo $komp['jumlah']; ?></td>
                            <td class="text-right">Rp <?php echo number_format($komp['harga_satuan'], 0, ',', '.'); ?></td>
                            <td class="text-right">Rp <?php echo number_format($komp['total_harga'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (!empty($laporan['catatan_admin'])): ?>
            <p style="margin-top:15px;"><strong>Catatan dari Admin:</strong></p>
            <p class="form-static-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($laporan['status_perbaikan'] == 'Baru' || $laporan['status_perbaikan'] == 'Diproses'): ?>
        <div class="card form-container" style="margin-top:20px;">
            <h3>Proses Laporan</h3>
            <form action="proses_status_perbaikan.php" method="POST">
                <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
                <div class="form-group">
                    <label for="status_perbaikan">Ubah Status</label>
                    <select name="status_perbaikan" id="status_perbaikan" class="form-control">
                        <option value="Baru" <?php if($laporan['status_perbaikan'] == 'Baru') echo 'selected'; ?>>Baru</option>
                        <option value="Diproses" <?php if($laporan['status_perbaikan'] == 'Diproses') echo 'selected'; ?>>Diproses</option>
                        <option value="Ditolak" <?php if($laporan['status_perbaikan'] == 'Ditolak') echo 'selected'; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="catatan_admin">Catatan Admin</label>
                    <textarea name="catatan_admin" id="catatan_admin" rows="4" class="form-control"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>

        <div class="card form-container" style="margin-top: 20px; border-left: 5px solid #2ecc71;">
            <h3>Tindakan Final</h3>
            <p>Gunakan tombol ini jika perbaikan sudah selesai dan Anda perlu mencatat penggunaan komponen/jasa.</p>
            <div class="form-actions">
                <a href="selesaikan_perbaikan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-success">Selesaikan & Catat Penggunaan Komponen</a>
            </div>
        </div>
    <?php endif; ?>

</section>

<?php require_once 'template_footer.php'; ?>