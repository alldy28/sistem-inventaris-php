<?php
$page_title = 'Detail Laporan Kerusakan';
$active_page = 'kerusakan'; // Sesuaikan jika ini bagian dari menu perbaikan
require_once 'template_header.php';

// BAGIAN 1: FUNGSI-FUNGSI PENGAMBILAN DATA
function getRepairDetails(mysqli $koneksi, int $id_perbaikan): ?array {
    $stmt = $koneksi->prepare("SELECT pa.*, u.nama_lengkap FROM perbaikan_aset pa LEFT JOIN users u ON pa.id_user = u.id WHERE pa.id = ?");
    $stmt->bind_param("i", $id_perbaikan);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan = $result->fetch_assoc();
    $stmt->close();
    return $laporan;
}

function getRepairComponents(mysqli $koneksi, int $id_perbaikan): array {
    $stmt = $koneksi->prepare("SELECT * FROM komponen_perbaikan WHERE id_perbaikan = ?");
    $stmt->bind_param("i", $id_perbaikan);
    $stmt->execute();
    $result = $stmt->get_result();
    $komponen = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $komponen;
}

// BAGIAN 2: LOGIKA UTAMA HALAMAN
// Keamanan dan pengambilan ID
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='content-section'>Akses ditolak atau ID tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_perbaikan = (int)$_GET['id'];

// Ambil data utama laporan
$laporan = getRepairDetails($koneksi, $id_perbaikan);

if (!$laporan) {
    echo "<p class='content-section'>Laporan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil data komponen hanya jika diperlukan (status selesai)
$komponen_digunakan = [];
if ($laporan['status_perbaikan'] === 'Selesai') {
    $komponen_digunakan = getRepairComponents($koneksi, $id_perbaikan);
}
?>

<header class="main-header">
    <h1>Detail Laporan Kerusakan #<?php echo $laporan['id']; ?></h1>
    <p>Diajukan oleh: <strong><?php echo htmlspecialchars($laporan['nama_lengkap'] ?? 'User Dihapus'); ?></strong> pada <?php echo date('d M Y, H:i', strtotime($laporan['tanggal_laporan'])); ?></p>
</header>

<section class="content-section">
    <div class="card form-container">
        <h3>Detail Aset & Kerusakan</h3>
        <div class="form-group">
            <label>Nama Aset</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['nama_aset']); ?></p>
        </div>
        <div class="form-group">
            <label>Merek</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['merek']); ?></p>
        </div>
        <div class="form-group">
            <label>Tipe / Model</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['tipe']); ?></p>
        </div>
        <div class="form-group">
            <label>Serial Number</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['serial_number'] ?? '-'); ?></p>
        </div>
        <div class="form-group">
            <label>Jenis Kerusakan Dilaporkan</label>
            <p class="form-static-text"><?php echo htmlspecialchars($laporan['jenis_kerusakan']); ?></p>
        </div>
        <div class="form-group">
            <label>Deskripsi Kerusakan dari Pengguna</label>
            <div class="form-static-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($laporan['deskripsi_kerusakan']); ?></div>
        </div>
    </div>

    <?php if (in_array($laporan['status_perbaikan'], ['Baru', 'Diproses'])): ?>
        
        <div class="card form-container" style="margin-top:20px;">
            <h3>Proses Laporan</h3>
            <form action="proses_status_perbaikan.php" method="POST">
                <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
                <div class="form-group">
                    <label for="status_perbaikan">Ubah Status</label>
                    <select name="status_perbaikan" id="status_perbaikan" class="form-control">
                        <option value="Baru" <?php if($laporan['status_perbaikan'] == 'Baru') echo 'selected'; ?>>Baru</option>
                        <option value="Diproses" <?php if($laporan['status_perbaikan'] == 'Diproses') echo 'selected'; ?>>Diproses</option>
                        <option value="Ditolak">Ditolak</option>
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

        <div class="card form-container" style="margin-top: 20px; border-left: 5px solid var(--color-success);">
            <h3>Tindakan Final</h3>
            <p>Gunakan tombol ini jika perbaikan sudah selesai dan Anda perlu mencatat penggunaan komponen/jasa.</p>
            <div class="form-actions">
                <a href="selesaikan_perbaikan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-success">Selesaikan & Catat Penggunaan Komponen</a>
            </div>
        </div>

    <?php else: // Status 'Selesai' atau 'Ditolak' ?>

        <div class="card" style="margin-top: 20px;">
            <h3><?php echo ($laporan['status_perbaikan'] == 'Selesai') ? 'Hasil Akhir & Komponen Digunakan' : 'Hasil Akhir Proses'; ?></h3>
            <p>
                Status Laporan: <span class="status-badge status-<?php echo strtolower($laporan['status_perbaikan']); ?>"><?php echo $laporan['status_perbaikan']; ?></span>
                <?php if (!empty($laporan['tanggal_selesai'])): ?>
                    pada tanggal <?php echo date('d M Y', strtotime($laporan['tanggal_selesai'])); ?>
                <?php endif; ?>
            </p>

            <?php if (!empty($komponen_digunakan)): ?>
                <div class="table-container" style="margin-top:15px;">
                    <table class="product-table">
                        <thead><tr><th>Nama Komponen/Jasa</th><th>Jumlah</th><th>Harga Satuan</th><th>Total Harga</th></tr></thead>
                        <tbody>
                            <?php foreach($komponen_digunakan as $komp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($komp['nama_komponen']); ?></td>
                                <td class="text-center"><?php echo $komp['jumlah']; ?></td>
                                <td class="text-right">Rp <?php echo number_format($komp['harga_satuan'], 0, ',', '.'); ?></td>
                                <td class="text-right">Rp <?php echo number_format($komp['total_harga'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($laporan['catatan_admin'])): ?>
                <p style="margin-top:15px;"><strong>Catatan dari Admin:</strong></p>
                <div class="form-static-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</section>

<?php require_once 'template_footer.php'; ?>