<?php
$page_title = 'Permintaan Barang';
$active_page = 'permintaan';
require_once 'template_header.php';

// --- LOGIKA BARU DENGAN FILTER STATUS & PAGINATION ---

// 1. Ambil parameter filter dan pagination dari URL
$status_filter = $_GET['status_filter'] ?? 'semua';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

// 2. Siapkan query dasar dan array untuk kondisi dinamis
$base_sql = "FROM permintaan p JOIN users u ON p.id_user = u.id";
$conditions = [];
$params = [];
$types = '';

// Tambahkan kondisi berdasarkan peran (role)
if ($_SESSION['role'] == 'user') {
    $conditions[] = "p.id_user = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
}

// Tambahkan kondisi berdasarkan filter status
if ($status_filter != 'semua') {
    $conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Gabungkan kondisi ke dalam klausa WHERE
$where_clause = '';
if (!empty($conditions)) {
    $where_clause = " WHERE " . implode(' AND ', $conditions);
}

// 3. Hitung total data dengan filter yang sama
$count_sql = "SELECT COUNT(p.id) " . $base_sql . $where_clause;
$stmt_count = $koneksi->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
$offset = ($page - 1) * $limit;

// 4. Siapkan query utama untuk mengambil data dengan filter DAN pagination
$main_sql = "SELECT p.id, u.nama_lengkap, p.tanggal_permintaan, p.status " . $base_sql . $where_clause . " ORDER BY p.tanggal_permintaan DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $koneksi->prepare($main_sql);
if (!$stmt) {
    die("Error preparing statement: " . $koneksi->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- AKHIR LOGIKA BARU ---
?>

<header class="main-header">
    <h1><?php echo ($_SESSION['role'] == 'admin') ? 'Daftar Permintaan Masuk' : 'Riwayat Permintaan Saya'; ?></h1>
    <p>Berikut adalah daftar permintaan yang tercatat di sistem.</p>
</header>

<section class="content-section">
    <div class="filter-bar">
        <a href="?status_filter=semua" class="<?php if($status_filter == 'semua') echo 'active'; ?>">Semua</a>
        <a href="?status_filter=Pending" class="<?php if($status_filter == 'Pending') echo 'active'; ?>">Pending</a>
        <a href="?status_filter=Disetujui" class="<?php if($status_filter == 'Disetujui') echo 'active'; ?>">Disetujui</a>
        <a href="?status_filter=Ditolak" class="<?php if($status_filter == 'Ditolak') echo 'active'; ?>">Ditolak</a>
    </div>

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
                        <td><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
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
                    <tr><td colspan="<?php echo $colspan; ?>">Tidak ada data permintaan yang cocok dengan filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        <?php if ($total_pages > 1): ?>
            <?php 
                // Siapkan parameter filter untuk link pagination
                $filter_query_string = http_build_query(['status_filter' => $status_filter]);
            ?>
            <?php if ($page > 1): ?>
                <a href="?<?php echo $filter_query_string; ?>&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo $filter_query_string; ?>&page=<?php echo $i; ?>" class="<?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo $filter_query_string; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>