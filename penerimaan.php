<?php
$page_title = 'Riwayat Penerimaan';
$active_page = 'penerimaan';
require_once 'template_header.php';

// Keamanan
if ($_SESSION['role'] !== 'admin') {
    echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
    require_once 'template_footer.php';
    exit;
}

/**
 * Fungsi untuk mengambil seluruh riwayat penerimaan barang.
 * Menggabungkan logika database ke dalam satu fungsi yang rapi.
 *
 * @param mysqli $koneksi Objek koneksi database.
 * @return array Data riwayat penerimaan.
 */
function getRiwayatPenerimaan(mysqli $koneksi): array
{
    // Query untuk mengambil data dengan JOIN ke 3 tabel
    $sql = "SELECT 
                pn.id, pn.jumlah, pn.harga_satuan, pn.tanggal_penerimaan, pn.nomor_faktur,
                pr.spesifikasi, 
                kp.nama_kategori
            FROM penerimaan pn
            JOIN produk pr ON pn.id_produk = pr.id
            JOIN kategori_produk kp ON pr.id_kategori = kp.id
            ORDER BY pn.tanggal_penerimaan DESC, pn.id DESC";

    // Menggunakan prepared statement sebagai best practice
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    // Ambil semua hasil sekaligus ke dalam array
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data;
}

// Panggil fungsi untuk mendapatkan data
$riwayat_penerimaan = getRiwayatPenerimaan($koneksi);
?>

<header class="main-header">
    <h1>Riwayat Penerimaan Barang</h1>
    <p>Berikut adalah semua riwayat penambahan stok barang yang tercatat.</p>
</header>

<section class="content-section">
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="form_penerimaan.php" class="btn btn-primary">Catat Penerimaan Baru</a>
    </div>

    <div class="table-container">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Harga Satuan</th>
                    <th>Tanggal</th>
                    <th>No. Faktur</th>
                    <th style="width: 200px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($riwayat_penerimaan)): ?>
                    <?php foreach ($riwayat_penerimaan as $row): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_kategori'] . ' ' . $row['spesifikasi']); ?></td>
                        <td class="text-center"><?php echo $row['jumlah']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_penerimaan'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nomor_faktur']); ?></td>
                        <td class="action-links">
                            <a href="cetak_penerimaan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-edit" style="background-color:#3498db;">Cetak</a>
                            
                            <form action="proses/proses_penerimaan.php" method="POST" style="display:inline;" onsubmit="return confirm('PERINGATAN! Menghapus data penerimaan akan mengurangi stok yang sesuai. Anda yakin ingin melanjutkan?');">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_penerimaan" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Belum ada riwayat penerimaan barang.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>