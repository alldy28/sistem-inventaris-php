<?php
$page_title = 'Perbaikan Aset';
$active_page = 'perbaikan';
require_once 'template_header.php';

// Pastikan hanya user yang bisa mengakses
if ($_SESSION['role'] !== 'user') {
    echo "<p>Halaman ini hanya untuk pengguna dengan peran 'user'.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil riwayat perbaikan untuk user yang sedang login
$id_user_login = $_SESSION['user_id'];
$stmt = $koneksi->prepare("SELECT * FROM perbaikan_aset WHERE id_user = ? ORDER BY tanggal_laporan DESC");
$stmt->bind_param("i", $id_user_login);
$stmt->execute();
$history = $stmt->get_result();
?>

<header class="main-header">
    <h1>Laporan Perbaikan Aset</h1>
    <p>Gunakan form di bawah untuk melaporkan kerusakan aset.</p>
</header>

<section class="content-section">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert alert-success">Laporan kerusakan berhasil dikirim dan akan segera diproses oleh admin.</div>
    <?php endif; ?>

    <div class="card form-container">
        <h2>Buat Laporan Kerusakan Baru</h2>
        <form action="proses_perbaikan.php" method="POST">
            <div class="form-group">
                <label for="nama_aset">Nama Aset yang Rusak</label>
                <input type="text" id="nama_aset" name="nama_aset" class="form-control" placeholder="Contoh: Komputer PC Ruang A, Printer Epson L3110" required>
            </div>
            <div class="form-group">
                <label for="komponen_rusak">Komponen / Bagian yang Rusak</label>
                <input type="text" id="komponen_rusak" name="komponen_rusak" class="form-control" placeholder="Contoh: Power Supply, Keyboard, Tinta Mampet" required>
            </div>
            <div class="form-group">
                <label for="deskripsi_kerusakan">Deskripsi Kerusakan</label>
                <textarea id="deskripsi_kerusakan" name="deskripsi_kerusakan" class="form-control" rows="4" placeholder="Jelaskan detail kerusakan di sini..." required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Riwayat Laporan Saya</h2>
        <div class="table-container">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID Laporan</th>
                        <th>Nama Aset</th>
                        <th>Komponen Rusak</th>
                        <th>Tanggal Lapor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history && $history->num_rows > 0): ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_aset']); ?></td>
                            <td><?php echo htmlspecialchars($row['komponen_rusak']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_laporan'])); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status_perbaikan'])); ?>"><?php echo $row['status_perbaikan']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Anda belum pernah membuat laporan kerusakan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>