<?php
$page_title = 'Keranjang Permintaan';
$active_page = 'keranjang';
require_once 'template_header.php';

// Pastikan hanya user yang bisa mengakses halaman keranjang
if ($_SESSION['role'] !== 'user') {
    echo "<p>Halaman ini hanya untuk pengguna dengan peran 'user'.</p>";
    require_once 'template_footer.php';
    exit;
}

// Ambil data keranjang dari sesi
$keranjang = $_SESSION['keranjang'] ?? [];
$produk_di_keranjang = [];
$total_keseluruhan = 0;

if (!empty($keranjang)) {
    // Siapkan placeholder untuk query IN()
    $placeholders = implode(',', array_fill(0, count($keranjang), '?'));
    $ids = array_keys($keranjang);

    // <<-- PERBAIKAN: Query diperbarui dengan JOIN ke tabel kategori -->>
    $sql = "SELECT pr.id, pr.spesifikasi, pr.satuan, pr.harga, kp.nama_kategori 
            FROM produk pr 
            JOIN kategori_produk kp ON pr.id_kategori = kp.id 
            WHERE pr.id IN ($placeholders)";

    $stmt = $koneksi->prepare($sql);
    // Bind parameter dinamis berdasarkan jumlah item di keranjang
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $produk_di_keranjang[$row['id']] = $row;
    }
}
?>

<header class="main-header">
    <h1>Keranjang Permintaan Barang</h1>
    <p>Berikut adalah daftar barang yang akan Anda ajukan permintaannya.</p>
</header>

<section class="content-section">
    <div class="table-container">
        <?php if (!empty($keranjang)): ?>
            <form action="proses_permintaan.php" method="POST">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Harga Satuan (Referensi)</th>
                            <th>Jumlah</th>
                            <th>Subtotal (Referensi)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($keranjang as $id_produk => $jumlah): 
                            // Pastikan produk ada di hasil query sebelum ditampilkan
                            if (isset($produk_di_keranjang[$id_produk])):
                                $produk = $produk_di_keranjang[$id_produk];
                                $subtotal = $produk['harga'] * $jumlah;
                                $total_keseluruhan += $subtotal;

                                // <<-- PERBAIKAN: Gabungkan nama kategori dan spesifikasi -->>
                                $nama_lengkap_produk = $produk['nama_kategori'] . ' (' . $produk['spesifikasi'] . ')';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nama_lengkap_produk); ?></td>
                            <td>Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                            <td><?php echo $jumlah; ?> <?php echo htmlspecialchars($produk['satuan']); ?></td>
                            <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            <td>
                                <a href="proses_keranjang.php?aksi=hapus&id=<?php echo $id_produk; ?>" class="btn-delete">Hapus</a>
                            </td>
                        </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align: right;">Total Nilai Referensi</th>
                            <th colspan="2">Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <div class="action-bar" style="text-align: right; margin-top: 20px;">
                    <button type="submit" name="ajukan_permintaan" class="btn btn-primary">Ajukan Permintaan Sekarang</button>
                </div>
            </form>
        <?php else: ?>
            <p style="text-align: center; padding: 20px;">Keranjang permintaan Anda masih kosong. Silakan pilih produk di halaman <a href="produk.php">Produk</a>.</p>
        <?php endif; ?>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>