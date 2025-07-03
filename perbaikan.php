<?php
$page_title = 'Perbaikan Aset';
$active_page = 'perbaikan';
require_once 'template_header.php';

// Pastikan hanya user yang bisa mengakses
if ($_SESSION['role'] !== 'user') {
    echo "<p class='content-section'>Halaman ini hanya untuk pengguna dengan peran 'user'.</p>";
    require_once 'template_footer.php';
    exit;
}

// Fungsi untuk mengambil riwayat perbaikan
function getRepairHistoryForUser(mysqli $koneksi, int $user_id): array 
{
    // Mengambil semua kolom termasuk yang baru
    $stmt = $koneksi->prepare("SELECT * FROM perbaikan_aset WHERE id_user = ? ORDER BY tanggal_laporan DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

$repair_history = getRepairHistoryForUser($koneksi, $_SESSION['user_id']);
?>

<header class="main-header">
    <h1>Laporan Perbaikan Aset</h1>
    <p>Gunakan form di bawah untuk melaporkan kerusakan aset atau lihat riwayat laporan Anda.</p>
</header>

<section class="content-section">
    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="card form-container">
        <h2>Buat Laporan Kerusakan Baru</h2>
        <form action="proses_perbaikan.php" method="POST">
            <div class="form-group">
                <label for="nama_aset">Nama Aset yang Rusak</label>
                <input type="text" id="nama_aset" name="nama_aset" class="form-control" placeholder="Contoh: Komputer PC Ruang A" required>
            </div>
            <div class="form-group">
                <label for="merek">Merek</label>
                <input type="text" id="merek" name="merek" class="form-control" placeholder="Contoh: Dell, Epson, Honda" required>
            </div>
            <div class="form-group">
                <label for="tipe">Tipe / Model</label>
                <input type="text" id="tipe" name="tipe" class="form-control" placeholder="Contoh: Optiplex 3070, L3110, Vario 125" required>
            </div>
            <div class="form-group">
                <label for="serial_number">Serial Number (Opsional)</label>
                <input type="text" id="serial_number" name="serial_number" class="form-control" placeholder="Jika ada, masukkan serial number aset">
            </div>
            <div class="form-group">
                <label for="jenis_kerusakan">Jenis Kerusakan</label>
                <input type="text" id="jenis_kerusakan" name="jenis_kerusakan" class="form-control" placeholder="Contoh: Mati Total, Tinta Mampet, Bunyi Bising" required>
            </div>
            <div class="form-group">
                <label for="deskripsi_kerusakan">Deskripsi Detail Kerusakan</label>
                <textarea id="deskripsi_kerusakan" name="deskripsi_kerusakan" class="form-control" rows="4" placeholder="Jelaskan detail kerusakan di sini..." required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="kirim_laporan" class="btn btn-primary">Kirim Laporan</button>
            </div>
        </form>
    </div>

    <?php if (!empty($repair_history)): ?>
    <div class="card" style="margin-top: 30px;">
        <h2>Riwayat Laporan Saya</h2>
        <div class="table-container">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Aset</th>
                        <th>Merek / Tipe</th>
                        <th>Jenis Kerusakan</th>
                        <th>Tanggal Lapor</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($repair_history as $row): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_aset']); ?></td>
                        <td><?php echo htmlspecialchars($row['merek'] . ' - ' . $row['tipe']); ?></td>
                        <td><?php echo htmlspecialchars($row['jenis_kerusakan']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_laporan'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status_perbaikan'])); ?>">
                                <?php echo htmlspecialchars($row['status_perbaikan']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <?php if ($row['status_perbaikan'] == 'Selesai'): ?>
                                <a href="cetak_bukti_pengambilan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-primary btn-sm">Cetak Bukti</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php require_once 'template_footer.php'; ?>