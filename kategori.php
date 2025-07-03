<?php
$page_title = 'Daftar Kategori';
$active_page = 'kategori';
require_once 'template_header.php';

// Keamanan: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p class='content-section'>Akses ditolak.</p>";
    require_once 'template_footer.php';
    exit;
}

/**
 * Mengambil daftar kategori, bisa dengan atau tanpa filter pencarian.
 * @param mysqli $koneksi
 * @param string $keyword
 * @return array
 */
function getCategories(mysqli $koneksi, string $keyword = ''): array
{
    $sql = "SELECT * FROM kategori_produk";
    $params = [];
    $types = '';

    if (!empty($keyword)) {
        $sql .= " WHERE nusp_id LIKE ? OR nama_kategori LIKE ?";
        $search_term = "%" . $keyword . "%";
        $params = [$search_term, $search_term];
        $types = "ss";
    }

    $sql .= " ORDER BY nama_kategori ASC";

    $stmt = $koneksi->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data;
}

// --- LOGIKA UTAMA ---
$keyword = $_GET['keyword'] ?? '';
$categories = getCategories($koneksi, $keyword);
?>

<header class="main-header">
    <h1>Daftar Kategori Produk</h1>
    <p>Kelola semua kategori umum untuk pengelompokan produk.</p>
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

    <div class="card form-container" style="margin-bottom: 25px;">
        <h2>Manajemen Kategori</h2>
        <div class="action-bar" style="margin-top: 15px; padding-top:15px; border-top: 1px solid #eee; display:flex; justify-content:space-between;">
            <a href="form_tambah_kategori.php" class="btn btn-primary">Tambah Kategori Baru</a>
            <a href="template_kategori.csv" download class="btn btn-secondary">Download Template CSV</a>
        </div>
        <form action="proses_upload_kategori.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
            <div class="form-group">
                <label for="file_kategori"><strong>Impor dari File CSV:</strong></label>
                <input type="file" name="file_kategori" id="file_kategori" class="form-control" required accept=".csv">
            </div>
            <div class="form-actions">
                <button type="submit" name="upload" class="btn btn-success">Unggah dan Impor</button>
            </div>
        </form>
    </div>

    <div class="search-form-container">
        <form action="kategori.php" method="GET">
            <input type="text" name="keyword" placeholder="Cari berdasarkan ID NUSP atau Nama Kategori..."
                   value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="kategori.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th style="width: 25%;">ID NUSP</th>
                    <th>Nama Kategori</th>
                    <th style="width: 20%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach($categories as $kategori): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kategori['nusp_id']); ?></td>
                        <td><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                        <td class="action-links">
                            <a href="form_edit_kategori.php?id=<?php echo $kategori['id']; ?>" class="btn-edit">Edit</a>
                            
                            <form action="proses_kategori.php" method="POST" style="display:inline;" onsubmit="return confirm('PERINGATAN! Menghapus kategori ini juga akan menghapus SEMUA produk spesifik di dalamnya. Anda yakin?');">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?php echo $kategori['id']; ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">
                            <?php echo !empty($keyword) ? 'Kategori tidak ditemukan.' : 'Tidak ada data kategori.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php 
$koneksi->close(); 
require_once 'template_footer.php'; 
?>