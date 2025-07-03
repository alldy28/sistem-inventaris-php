<?php
$page_title = 'Daftar Produk';
$active_page = 'produk';
// Tidak ada pengecekan role global di sini, karena halaman ini dapat diakses oleh admin dan user.
// Pengecekan role dilakukan di dalam tampilan HTML untuk masing-masing fitur.
require_once 'template_header.php';

/**
 * Mengambil daftar produk, bisa dengan atau tanpa filter pencarian.
 */
function getProducts(mysqli $koneksi, string $keyword = ''): array
{
    $sql = "SELECT pr.id, pr.spesifikasi, pr.satuan, pr.stok, pr.harga, kp.nusp_id, kp.nama_kategori 
            FROM produk pr 
            JOIN kategori_produk kp ON pr.id_kategori = kp.id";
    
    $params = [];
    $types = '';

    if (!empty($keyword)) {
        $sql .= " WHERE (kp.nama_kategori LIKE ? OR pr.spesifikasi LIKE ? OR kp.nusp_id LIKE ?)";
        $search_term = "%" . $keyword . "%";
        $params = [$search_term, $search_term, $search_term];
        $types = "sss";
    }

    $sql .= " ORDER BY kp.nama_kategori, pr.spesifikasi ASC";
    
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
$products = getProducts($koneksi, $keyword);
?>

<header class="main-header">
    <h1>Daftar Produk</h1>
    <p>Kelola data produk spesifik atau pilih untuk diajukan permintaannya.</p>
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

    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div class="card form-container" style="margin-bottom: 25px;">
        <h2>Manajemen Produk</h2>
        <div class="action-bar"
            style="margin-top: 15px; padding-top:15px; border-top: 1px solid #eee; display:flex; justify-content:space-between;">
            <a href="form_produk.php" class="btn btn-primary">Tambah Produk Baru</a>
            <a href="template_produk.csv" download class="btn btn-secondary">Download Template CSV</a>
        </div>
        <form action="proses/proses_upload_produk.php" method="POST" enctype="multipart/form-data"
            style="margin-top: 20px;">
            <div class="form-group">
                <label for="file_produk"><strong>Impor dari File CSV:</strong></label>
                <input type="file" name="file_produk" id="file_produk" class="form-control" required accept=".csv">
            </div>
            <div class="form-actions">
                <button type="submit" name="upload" class="btn btn-success">Unggah dan Impor</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="search-form-container">
        <form action="produk.php" method="GET">
            <input type="text" name="keyword" placeholder="Cari berdasarkan Nama, Spesifikasi, atau ID NUSP..."
                value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="produk.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID NUSP</th>
                    <th>Nama Kategori</th>
                    <th>Spesifikasi</th>
                    <th>Stok</th>
                    <?php if ($_SESSION['role'] == 'admin') echo '<th>Harga Jual</th>'; ?>
                    <th style="width: 250px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                <?php foreach ($products as $produk): ?>
                <tr>
                    <td><?php echo htmlspecialchars($produk['nusp_id']); ?></td>
                    <td><?php echo htmlspecialchars($produk['nama_kategori']); ?></td>
                    <td><?php echo htmlspecialchars($produk['spesifikasi']); ?></td>
                    <td><?php echo $produk['stok']; ?> <?php echo htmlspecialchars($produk['satuan']); ?></td>

                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <td>Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                    <?php endif; ?>

                    <td class="action-links">
                        <?php if ($_SESSION['role'] == 'user'): ?>
                        <form action="proses_keranjang.php" method="POST" class="form-keranjang-inline">
                            <input type="number" name="jumlah" class="input-jumlah" value="1" min="1"
                                max="<?php echo $produk['stok']; ?>" <?php if($produk['stok'] < 1) echo 'disabled'; ?>
                                required>
                            <input type="hidden" name="id_produk" value="<?php echo $produk['id']; ?>">
                            <button type="submit" name="tambah_ke_keranjang" class="btn btn-primary btn-sm"
                                <?php if($produk['stok'] < 1) echo 'disabled'; ?>>+ Keranjang</button>
                        </form>
                        <?php else: // Untuk Admin ?>
                        <a href="form_produk.php?id=<?php echo $produk['id']; ?>" class="btn-edit">Edit</a>
                        <button class="btn btn-info btn-sm btn-harga-detail"
                            data-idproduk="<?php echo $produk['id']; ?>">Harga</button>
                        <form action="proses_produk.php" method="POST" style="display:inline;"
                            onsubmit="return confirm('Anda yakin ingin menghapus produk ini?');">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?php echo $produk['id']; ?>">
                            <button type="submit" class="btn-delete">Hapus</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <?php $colspan = ($_SESSION['role'] == 'admin') ? 6 : 5; ?>
                <tr>
                    <td colspan="<?php echo $colspan; ?>">
                        <?php echo !empty($keyword) ? 'Produk tidak ditemukan.' : 'Tidak ada data produk.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($_SESSION['role'] == 'admin'): ?>
<div id="hargaModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Rincian Harga per Batch</h2>
        <div id="modal-body-harga" class="table-container" style="text-align: left;"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('hargaModal');
    const modalBody = document.getElementById('modal-body-harga');
    const closeBtn = modal.querySelector('.close-button');

    function closeModal() {
        modal.style.display = 'none';
        modalBody.innerHTML = '';
    }

    closeBtn.onclick = closeModal;
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    const hargaButtons = document.querySelectorAll('.btn-harga-detail');
    hargaButtons.forEach(button => {
        button.addEventListener('click', function() {
            const idProduk = this.dataset.idproduk;

            modalBody.innerHTML = '<p>Memuat data harga...</p>';
            modal.style.display = 'block';

            fetch('get_harga_batch.php?id=' + idProduk)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `<p style="color:red;">${data.error}</p>`;
                        return;
                    }

                    const hargaSaatIni = data.harga_saat_ini;
                    const dataBatch = data.data_batch;

                    if (dataBatch.length > 0) {
                        let tableHTML =
                            '<table class="product-table"><thead><tr><th>Sisa Stok</th><th>Harga Beli</th><th>Tanggal Masuk</th><th>Status</th></tr></thead><tbody>';
                        dataBatch.forEach(batch => {
                            const hargaBeli = parseFloat(batch.harga_beli);
                            const isActive = hargaBeli === hargaSaatIni;
                            const activeClass = isActive ? 'active-price-row' : '';
                            const activeIcon = isActive ? ' &#9664; (Aktif)' : '';

                            tableHTML += `<tr class="${activeClass}">
                                <td class="text-center">${batch.sisa_stok}</td>
                                <td class="text-right">Rp ${new Intl.NumberFormat('id-ID').format(hargaBeli)}</td>
                                <td>${new Date(batch.tanggal_masuk).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
                                <td class="text-center"><strong>${activeIcon}</strong></td>
                            </tr>`;
                        });
                        tableHTML += '</tbody></table>';
                        modalBody.innerHTML = tableHTML;
                    } else {
                        modalBody.innerHTML =
                            '<p>Tidak ada rincian batch harga untuk produk ini.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="color:red;">Gagal memuat data.</p>';
                });
        });
    });
});
</script>
<?php endif; ?>


<?php
$koneksi->close();
require_once 'template_footer.php';
?>