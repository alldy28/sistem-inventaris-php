<?php
$page_title = 'Keranjang Permintaan';
$active_page = 'keranjang';
require_once 'template_header.php';

// Ambil data produk berdasarkan ID yang ada di keranjang
$keranjang = $_SESSION['keranjang'] ?? [];
$produk_di_keranjang = [];
if (!empty($keranjang)) {
    $ids = implode(',', array_keys($keranjang));
    $sql = "SELECT id, nama_barang, satuan, harga FROM produk WHERE id IN ($ids)";
    $result = $koneksi->query($sql);
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
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_keseluruhan = 0;
                        foreach ($keranjang as $id_produk => $jumlah): 
                            if (isset($produk_di_keranjang[$id_produk])):
                                $produk = $produk_di_keranjang[$id_produk];
                                $subtotal = $produk['harga'] * $jumlah;
                                $total_keseluruhan += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produk['nama_barang']); ?></td>
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
                            <th colspan="3" style="text-align: right;">Total Keseluruhan</th>
                            <th colspan="2">Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <div class="action-bar" style="text-align: right; margin-top: 20px;">
                    <button type="submit" name="ajukan_permintaan" class="btn btn-primary">Ajukan Permintaan Sekarang</button>
                </div>
            </form>
        <?php else: ?>
            <p style="text-align: center;">Keranjang permintaan Anda masih kosong. Silakan pilih produk di halaman <a href="produk.php">Produk</a>.</p>
        <?php endif; ?>
    </div>
</section>

<?php
require_once 'template_footer.php';
?>