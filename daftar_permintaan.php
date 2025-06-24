<?php
$page_title = 'Permintaan Barang';
$active_page = 'permintaan';
require_once 'template_header.php';

// --- LOGIKA DINAMIS BERDASARKAN PERAN ---
$sql = "SELECT p.id, u.nama_lengkap, p.tanggal_permintaan, p.status 
        FROM permintaan p
        JOIN users u ON p.id_user = u.id";

if ($_SESSION['role'] == 'user') {
    $sql .= " WHERE p.id_user = ?";
}

$sql .= " ORDER BY p.tanggal_permintaan DESC";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $koneksi->error);
}

if ($_SESSION['role'] == 'user') {
    $id_user_login = $_SESSION['user_id'];
    $stmt->bind_param("i", $id_user_login);
}

$stmt->execute();
$result = $stmt->get_result();
// --- AKHIR LOGIKA DINAMIS ---
?>

<header class="main-header">
    <h1><?php echo ($_SESSION['role'] == 'admin') ? 'Daftar Permintaan Masuk' : 'Riwayat Permintaan Saya'; ?></h1>
    <p>Berikut adalah daftar permintaan yang tercatat di sistem.</p>
</header>

<section class="content-section">
    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID Permintaan</th>
                    <?php if ($_SESSION['role'] == 'admin') echo '<th>Nama Peminta</th>'; ?>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <?php if ($_SESSION['role'] == 'admin') echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>'; ?>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_permintaan'])); ?></td>
                        <td><span
                                class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                            <a href="detail_permintaan.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo ($_SESSION['role'] == 'admin') ? 'Detail & Proses' : 'Lihat Detail'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php 
                        $colspan = ($_SESSION['role'] == 'admin') ? 5 : 4;
                    ?>
                    <tr>
                        <td colspan="<?php echo $colspan; ?>">Anda belum memiliki riwayat permintaan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


<?php
// 1. Siapkan variabel untuk modal berdasarkan parameter URL
$show_modal = false;
$modal_title = '';
$modal_message = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'permintaan_terkirim') {
        $show_modal = true;
        $modal_title = 'Permintaan Terkirim!';
        $modal_message = 'Permintaan Anda telah berhasil dikirim. Mohon menunggu konfirmasi dari admin.';
    } elseif ($_GET['status'] == 'proses_sukses') {
        $show_modal = true;
        $modal_title = 'Berhasil!';
        $modal_message = 'Status permintaan telah berhasil diproses.';
    }
}
?>

<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
        </div>
        <h2><?php echo $modal_title; ?></h2>
        <p><?php echo $modal_message; ?></p>
    </div>
</div>

<script>
    if (document.getElementById('successModal')) {
        var modal = document.getElementById('successModal');
        var closeBtn = document.querySelector('.modal .close-button');
        function closeModal() { modal.style.display = 'none'; }
        if(closeBtn) { closeBtn.onclick = closeModal; }
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    }
</script>

<?php if($show_modal): ?>
<script>
    // Panggil fungsi untuk menampilkan modal
    document.getElementById('successModal').style.display = 'block';
</script>
<?php endif; ?>


<?php
require_once 'template_footer.php';
?>