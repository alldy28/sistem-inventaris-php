<?php
$page_title = 'Detail Permintaan';
$active_page = 'permintaan';
require_once 'template_header.php';

// --- LOGIKA KONTROL AKSES YANG BENAR DIMULAI DI SINI ---

// 1. Cek dasar: Pastikan ada ID di URL dan ID tersebut adalah angka.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID permintaan tidak valid atau tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_permintaan = $_GET['id'];

// 2. Ambil data permintaan dari database berdasarkan ID dari URL.
$stmt_req = $koneksi->prepare("SELECT p.*, u.nama_lengkap FROM permintaan p JOIN users u ON p.id_user = u.id WHERE p.id = ?");
$stmt_req->bind_param("i", $id_permintaan);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
$permintaan = $result_req->fetch_assoc();

// 3. Cek apakah data permintaan dengan ID tersebut benar-benar ada.
if (!$permintaan) {
    echo "<p>Permintaan dengan ID #$id_permintaan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}

// 4. INI BAGIAN KUNCI: Pengecekan Hak Akses (Otorisasi)
// Jika yang login adalah 'user', kita harus cek apakah ID pemilik permintaan ini sama dengan ID user yang sedang login.
// Admin tidak perlu dicek karena boleh melihat semua.
if ($_SESSION['role'] == 'user' && $permintaan['id_user'] != $_SESSION['user_id']) {
    echo "<p>Akses ditolak. Anda hanya dapat melihat detail permintaan Anda sendiri.</p>";
    require_once 'template_footer.php';
    exit;
}

// Jika semua pengecekan di atas lolos, maka pengguna boleh melihat halaman ini.
// Lanjutkan mengambil data detail barang.
$stmt_detail = $koneksi->prepare("SELECT dp.*, pr.nama_barang, pr.satuan FROM detail_permintaan dp JOIN produk pr ON dp.id_produk = pr.id WHERE dp.id_permintaan = ?");
$stmt_detail->bind_param("i", $id_permintaan);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

// --- AKHIR LOGIKA KONTROL AKSES ---
?>

<header class="main-header">
    <h1>Detail Permintaan #<?php echo $id_permintaan; ?></h1>
    <p>
        <strong>Peminta:</strong> <?php echo htmlspecialchars($permintaan['nama_lengkap']); ?> | 
        <strong>Status:</strong> <span class="status-badge status-<?php echo strtolower($permintaan['status']); ?>"><?php echo $permintaan['status']; ?></span>
    </p>
</header>

<section class="content-section">
    <h3>Daftar Barang yang Diminta:</h3>
    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Jumlah Diminta</th>
                    <th>Harga Saat Minta</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_keseluruhan = 0;
                while($item = $result_detail->fetch_assoc()): 
                    $subtotal = $item['jumlah'] * $item['harga_saat_minta'];
                    $total_keseluruhan += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                    <td><?php echo $item['jumlah']; ?> <?php echo htmlspecialchars($item['satuan']); ?></td>
                    <td>Rp <?php echo number_format($item['harga_saat_minta'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align: right;">Total Nilai Barang</th>
                    <th>Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($_SESSION['role'] == 'admin' && $permintaan['status'] == 'Pending'): ?>
    <div class="form-container" style="margin-top: 30px;">
        <h3>Proses Permintaan</h3>
        <form action="proses_acc.php" method="POST">
            <input type="hidden" name="id_permintaan" value="<?php echo $id_permintaan; ?>">
            <div class="form-group">
                <label for="catatan_admin">Catatan (Opsional)</label>
                <textarea name="catatan_admin" id="catatan_admin" rows="3" class="form-control"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="aksi" value="setujui" class="btn btn-primary">Setujui</button>
                <button type="submit" name="aksi" value="tolak" class="btn btn-delete">Tolak</button>
            </div>
        </form>
    </div>
    <?php elseif ($permintaan['status'] != 'Pending'): // Jika sudah diproses, tampilkan hasilnya (untuk admin & user) ?>
        <div class="form-container" style="margin-top: 30px;">
            <h3>Hasil Proses</h3>
            <p>Permintaan ini telah diproses pada tanggal <?php echo date('d M Y, H:i', strtotime($permintaan['tanggal_diproses'])); ?>.</p>
            <?php if (!empty($permintaan['catatan_admin'])): ?>
                <p><strong>Catatan dari Admin:</strong></p>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($permintaan['catatan_admin']); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="action-bar" style="margin-top: 30px; text-align: right;">
        <a href="cetak_surat.php?id=<?php echo $id_permintaan; ?>" target="_blank" class="btn btn-secondary">Cetak Surat Permintaan</a>
    </div>

</section>

<?php
require_once 'template_footer.php';
?>