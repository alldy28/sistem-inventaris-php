<?php
$page_title = 'Laporan Kerusakan Aset';
$active_page = 'kerusakan';
require_once 'template_header.php';

// Keamanan: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil semua data laporan dengan JOIN ke tabel users untuk mendapatkan nama pelapor
$sql = "SELECT pa.*, u.nama_lengkap 
        FROM perbaikan_aset pa 
        LEFT JOIN users u ON pa.id_user = u.id
        ORDER BY 
            CASE pa.status_perbaikan
                WHEN 'Baru' THEN 1
                WHEN 'Diproses' THEN 2
                WHEN 'Selesai' THEN 3
                WHEN 'Ditolak' THEN 4
            END, pa.tanggal_laporan DESC";
$result = $koneksi->query($sql);
?>

<header class="main-header">
    <h1>Laporan Kerusakan Aset</h1>
    <p>Kelola semua laporan kerusakan yang diajukan oleh pengguna.</p>
</header>

<section class="content-section">
    <?php if(isset($_GET['status_update']) && $_GET['status_update'] == 'sukses'): ?>
        <div class="alert alert-success">Status laporan berhasil diperbarui.</div>
    <?php endif; ?>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelapor</th>
                    <th>Nama Aset</th>
                    <th>Tanggal Lapor</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap'] ?? 'User Dihapus'); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_aset']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_laporan'])); ?></td>
                        <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status_perbaikan'])); ?>"><?php echo $row['status_perbaikan']; ?></span></td>
                        <td class="action-links">
                            <a href="detail_kerusakan.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Lihat & Proses</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">Tidak ada laporan kerusakan yang masuk.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>