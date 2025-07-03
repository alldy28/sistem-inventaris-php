<?php
$page_title = 'Detail Permintaan';
$active_page = 'permintaan';
require_once 'template_header.php';

// Fungsi untuk mengambil data utama permintaan
function getPermintaanById(mysqli $koneksi, int $id_permintaan): ?array
{
    $sql = "SELECT p.*, u.nama_lengkap 
            FROM permintaan p 
            JOIN users u ON p.id_user = u.id 
            WHERE p.id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id_permintaan);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Fungsi untuk mengambil item-item dalam permintaan
function getDetailItemsByPermintaanId(mysqli $koneksi, int $id_permintaan): array
{
    $sql = "SELECT dp.*, pr.spesifikasi, pr.stok, pr.satuan, kp.nama_kategori 
            FROM detail_permintaan dp 
            JOIN produk pr ON dp.id_produk = pr.id 
            JOIN kategori_produk kp ON pr.id_kategori = kp.id 
            WHERE dp.id_permintaan = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id_permintaan);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// --- LOGIKA UTAMA HALAMAN ---

// Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='content-section'>ID permintaan tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_permintaan = (int)$_GET['id'];

// Panggil fungsi untuk mengambil data
$permintaan = getPermintaanById($koneksi, $id_permintaan);

// Validasi Keamanan dan Hak Akses
if (!$permintaan) {
    echo "<p class='content-section'>Permintaan dengan ID #$id_permintaan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
if ($_SESSION['role'] === 'user' && $permintaan['id_user'] != $_SESSION['user_id']) {
    echo "<p class='content-section'>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil detail item hanya jika semua validasi berhasil
$detail_items = getDetailItemsByPermintaanId($koneksi, $id_permintaan);

?>

<header class="main-header">
    <h1>Detail Permintaan #<?php echo $id_permintaan; ?></h1>
    <p>
        <strong>Peminta:</strong> <?php echo htmlspecialchars($permintaan['nama_lengkap']); ?> |
        <strong>Tanggal Minta:</strong> <?php echo date('d M Y', strtotime($permintaan['tanggal_permintaan'])); ?> |
        <strong>Status:</strong> <span class="status-badge status-<?php echo strtolower($permintaan['status']); ?>"><?php echo $permintaan['status']; ?></span>
    </p>
</header>

<section class="content-section">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="proses_acc.php" method="POST">
        <h3>Daftar Barang yang Diminta:</h3>
        <div class="table-container">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th class="text-center">Jml Diminta</th>
                        <th class="text-center">Stok Tersedia</th>
                        <th class="text-center" style="width: 20%;">Jml Disetujui</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detail_items)): ?>
                        <tr><td colspan="4" class="text-center">Tidak ada barang dalam permintaan ini.</td></tr>
                    <?php else: ?>
                        <?php foreach($detail_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nama_kategori'] . ' ' . $item['spesifikasi']); ?></td>
                            <td class="text-center"><?php echo $item['jumlah']; ?> <?php echo htmlspecialchars($item['satuan']); ?></td>
                            <td class="text-center"><?php echo $item['stok']; ?> <?php echo htmlspecialchars($item['satuan']); ?></td>
                            <td>
                                <?php if ($_SESSION['role'] == 'admin' && $permintaan['status'] == 'Pending'): ?>
                                    <input type="number" 
                                           name="jumlah_disetujui[<?php echo $item['id']; ?>]" 
                                           class="form-control text-center"
                                           value="<?php echo min($item['jumlah'], $item['stok']); ?>"
                                           min="0"
                                           max="<?php echo min($item['jumlah'], $item['stok']); ?>"
                                           required>
                                <?php else: ?>
                                    <p class="text-center"><?php echo $item['jumlah_disetujui'] ?? 'N/A'; ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($_SESSION['role'] == 'admin' && $permintaan['status'] == 'Pending'): ?>
        <div class="form-container" style="margin-top: 30px;">
            <h3>Proses Permintaan</h3>
                <input type="hidden" name="id_permintaan" value="<?php echo $id_permintaan; ?>">
                <div class="form-group">
                    <label for="catatan_admin">Catatan (Opsional)</label>
                    <textarea name="catatan_admin" id="catatan_admin" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="aksi" value="setujui" class="btn btn-primary">Setujui & Proses</button>
                    <button type="submit" name="aksi" value="tolak" class="btn btn-delete" formnovalidate>Tolak</button>
                </div>
        </div>
        <?php endif; ?>
    </form>

    <?php if ($permintaan['status'] != 'Pending'): ?>
    <div class="info-card" style="margin-top: 30px;">
        <h3>Hasil Proses</h3>
        <p>Permintaan ini telah <strong><?php echo $permintaan['status']; ?></strong> pada tanggal <?php echo date('d M Y, H:i', strtotime($permintaan['tanggal_diproses'])); ?>.</p>
        <?php if (!empty($permintaan['catatan_admin'])): ?>
        <p><strong>Catatan dari Admin:</strong></p>
        <blockquote class="catatan-admin"><?php echo nl2br(htmlspecialchars($permintaan['catatan_admin'])); ?></blockquote>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="action-bar" style="margin-top: 30px; text-align: right;">
        <a href="cetak_surat.php?id=<?php echo $id_permintaan; ?>" target="_blank" class="btn btn-secondary">Cetak Surat Permintaan</a>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>