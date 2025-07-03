<?php
$page_title = 'Daftar Permintaan'; // Menyesuaikan judul dengan konten
$active_page = 'permintaan';
require_once 'template_header.php';

// =================================================================
// BAGIAN 1: FUNGSI-FUNGSI PENGAMBILAN DATA
// =================================================================

/**
 * Menyiapkan klausa WHERE dan parameter berdasarkan filter.
 * Fungsi helper ini digunakan untuk menghindari duplikasi kode.
 * @return array Berisi ['where_clause', 'types', 'params']
 */
function buildPermintaanQueryConditions(): array
{
    $status_filter = $_GET['status_filter'] ?? 'semua';

    $base_sql = "FROM permintaan p JOIN users u ON p.id_user = u.id";
    $conditions = [];
    $params = [];
    $types = '';

    if ($_SESSION['role'] === 'user') {
        $conditions[] = "p.id_user = ?";
        $params[] = $_SESSION['user_id'];
        $types .= 'i';
    }

    if ($status_filter !== 'semua') {
        $conditions[] = "p.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    $where_clause = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : '';
    
    return [
        'base_sql' => $base_sql,
        'where_clause' => $where_clause,
        'types' => $types,
        'params' => $params
    ];
}

/**
 * Menghitung total jumlah permintaan berdasarkan filter.
 * @param mysqli $koneksi
 * @return int Jumlah total permintaan.
 */
function getPermintaanCount(mysqli $koneksi): int
{
    $query_parts = buildPermintaanQueryConditions();
    
    $sql = "SELECT COUNT(p.id) " . $query_parts['base_sql'] . $query_parts['where_clause'];
    $stmt = $koneksi->prepare($sql);

    if (!empty($query_parts['params'])) {
        $stmt->bind_param($query_parts['types'], ...$query_parts['params']);
    }

    $stmt->execute();
    $total_rows = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();

    return $total_rows;
}

/**
 * Mengambil daftar permintaan dengan filter dan pagination.
 * @param mysqli $koneksi
 * @param int $limit
 * @param int $offset
 * @return array Daftar permintaan.
 */
function getPermintaanList(mysqli $koneksi, int $limit, int $offset): array
{
    $query_parts = buildPermintaanQueryConditions();
    
    $sql = "SELECT p.id, u.nama_lengkap, p.tanggal_permintaan, p.status " 
           . $query_parts['base_sql'] 
           . $query_parts['where_clause'] 
           . " ORDER BY p.tanggal_permintaan DESC LIMIT ? OFFSET ?";
    
    $query_parts['params'][] = $limit;
    $query_parts['params'][] = $offset;
    $query_parts['types'] .= 'ii';

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($query_parts['types'], ...$query_parts['params']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data;
}


// =================================================================
// BAGIAN 2: LOGIKA UTAMA HALAMAN
// =================================================================

// 1. Ambil parameter dari URL
$status_filter = $_GET['status_filter'] ?? 'semua';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah item per halaman

// 2. Hitung total data & halaman
$total_rows = getPermintaanCount($koneksi);
$total_pages = ceil($total_rows / $limit);
$offset = ($page - 1) * $limit;

// 3. Ambil data untuk halaman saat ini
$permintaan_list = getPermintaanList($koneksi, $limit, $offset);

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
                <?php if (!empty($permintaan_list)): ?>
                    <?php foreach($permintaan_list as $row): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <?php if ($_SESSION['role'] == 'admin') echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>'; ?>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_permintaan'])); ?></td>
                        <td><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                        <td>
                            <a href="detail_permintaan.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo ($_SESSION['role'] == 'admin' && $row['status'] == 'Pending') ? 'Proses' : 'Lihat Detail'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php $colspan = ($_SESSION['role'] == 'admin') ? 5 : 4; ?>
                    <tr><td colspan="<?php echo $colspan; ?>">Tidak ada data permintaan yang cocok dengan filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        <?php if ($total_pages > 1): ?>
            <?php $filter_query_string = http_build_query(['status_filter' => $status_filter]); ?>
            
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

<?php require_once 'template_footer.php'; ?>