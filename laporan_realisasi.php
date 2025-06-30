<?php
// Bagian 1: Inisialisasi dan Definisi Fungsi
$page_title = 'Laporan Realisasi Persediaan';
$active_page = 'laporan_realisasi';

function getInventoryRealizationReportData(mysqli $koneksi): array
{
    // 1. Ambil harga dari batch aktif tertua (untuk kolom Harga Penerimaan)
    $harga_batch_aktif_map = [];
    $sql_harga_aktif = "
        SELECT p.id_produk, p.harga_beli
        FROM stok_batch p
        INNER JOIN (
            SELECT id_produk, MIN(id) AS min_id FROM stok_batch WHERE sisa_stok > 0 GROUP BY id_produk
        ) AS oldest_active ON p.id = oldest_active.min_id
    ";
    $result_harga_aktif = $koneksi->query($sql_harga_aktif);
    if ($result_harga_aktif) {
        while ($h = $result_harga_aktif->fetch_assoc()) {
            $harga_batch_aktif_map[$h['id_produk']] = $h['harga_beli'];
        }
    }

    // 2. Ambil harga dari transaksi PENGELUARAN TERAKHIR (untuk kolom Harga Pengeluaran)
    $harga_keluar_terakhir_map = [];
    $sql_harga_keluar_terakhir = "
        SELECT
            dp.id_produk,
            (dp.nilai_keluar_fifo / dp.jumlah) AS harga_keluar_terakhir
        FROM detail_permintaan dp
        JOIN (
            SELECT dp_inner.id_produk, MAX(dp_inner.id) as max_detail_id
            FROM detail_permintaan dp_inner
            JOIN permintaan p_inner ON dp_inner.id_permintaan = p_inner.id
            WHERE p_inner.status = 'Disetujui'
            GROUP BY dp_inner.id_produk
        ) AS latest_detail ON dp.id = latest_detail.max_detail_id
        WHERE dp.jumlah > 0
    ";
    $result_harga_keluar = $koneksi->query($sql_harga_keluar_terakhir);
    if ($result_harga_keluar) {
        while ($h = $result_harga_keluar->fetch_assoc()) {
            $harga_keluar_terakhir_map[$h['id_produk']] = $h['harga_keluar_terakhir'];
        }
    }

    // --- PENAMBAHAN QUERY ---
    // 3. Ambil harga dari PENERIMAAN TERAKHIR sebagai fallback jika stok habis
    $harga_penerimaan_terakhir_map = [];
    $sql_penerimaan_terakhir = "
        SELECT p.id_produk, p.harga_satuan 
        FROM penerimaan p
        INNER JOIN (
           SELECT id_produk, MAX(id) as max_id FROM penerimaan GROUP BY id_produk
        ) as latest_receipt ON p.id = latest_receipt.max_id
    ";
    $result_penerimaan_terakhir = $koneksi->query($sql_penerimaan_terakhir);
    if ($result_penerimaan_terakhir) {
        while ($h = $result_penerimaan_terakhir->fetch_assoc()) {
            $harga_penerimaan_terakhir_map[$h['id_produk']] = $h['harga_satuan'];
        }
    }


    // 4. Query utama untuk mengambil data agregat
    $sql = "
        SELECT
            p.id AS id_produk, p.spesifikasi, p.satuan, kp.nama_kategori,
            sa.jumlah_awal, sa.harga_beli AS harga_awal,
            penerimaan.total_jumlah AS total_penerimaan_jumlah,
            penerimaan.total_nilai AS total_penerimaan_nilai,
            pengeluaran.total_jumlah AS total_pengeluaran_jumlah,
            pengeluaran.total_nilai AS total_pengeluaran_nilai,
            penerimaan_info.tanggal_penerimaan,
            penerimaan_info.bentuk_kontrak,
            penerimaan_info.nama_penyedia
        FROM produk p
        JOIN kategori_produk kp ON p.id_kategori = kp.id
        LEFT JOIN (
            SELECT sb.id_produk, sb.jumlah_awal, sb.harga_beli FROM stok_batch sb
            INNER JOIN (SELECT id_produk, MIN(id) AS first_batch_id FROM stok_batch GROUP BY id_produk) first_batches ON sb.id = first_batches.first_batch_id
        ) AS sa ON p.id = sa.id_produk
        LEFT JOIN (
            SELECT id_produk, SUM(jumlah) AS total_jumlah, SUM(jumlah * harga_satuan) AS total_nilai FROM penerimaan GROUP BY id_produk
        ) AS penerimaan ON p.id = penerimaan.id_produk
        LEFT JOIN (
            SELECT dp.id_produk, SUM(dp.jumlah) AS total_jumlah, SUM(dp.nilai_keluar_fifo) AS total_nilai FROM detail_permintaan dp
            JOIN permintaan per ON dp.id_permintaan = per.id WHERE per.status = 'Disetujui' GROUP BY dp.id_produk
        ) AS pengeluaran ON p.id = pengeluaran.id_produk
        LEFT JOIN (
            SELECT p1.id_produk, p1.tanggal_penerimaan, p1.bentuk_kontrak, p1.nama_penyedia FROM penerimaan p1
            INNER JOIN (SELECT id_produk, MAX(tanggal_penerimaan) AS max_tanggal FROM penerimaan GROUP BY id_produk) p2 ON p1.id_produk = p2.id_produk AND p1.tanggal_penerimaan = p2.max_tanggal
        ) AS penerimaan_info ON p.id = penerimaan_info.id_produk
        HAVING total_penerimaan_jumlah > 0 OR total_pengeluaran_jumlah > 0
        ORDER BY kp.nama_kategori, p.spesifikasi ASC
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan_data = [];

    while ($row = $result->fetch_assoc()) {
        $id_produk = $row['id_produk'];
        
        // --- PENAMBAHAN LOGIKA FALLBACK ---
        // 1. Coba ambil harga batch aktif
        $harga_untuk_kolom_penerimaan = $harga_batch_aktif_map[$id_produk] ?? 0;

        // 2. Jika tidak ada (stok habis), gunakan harga penerimaan terakhir
        if (empty($harga_untuk_kolom_penerimaan)) {
            $harga_untuk_kolom_penerimaan = $harga_penerimaan_terakhir_map[$id_produk] ?? 0;
        }

        // 3. Jika masih kosong, gunakan harga awal
        if (empty($harga_untuk_kolom_penerimaan)) {
            $harga_untuk_kolom_penerimaan = $row['harga_awal'] ?? 0;
        }

        $saldo_awal_jumlah = $row['jumlah_awal'] ?? 0;
        $saldo_awal_harga  = $row['harga_awal'] ?? 0;
        $saldo_awal_nilai  = $saldo_awal_jumlah * $saldo_awal_harga;
        
        $total_penerimaan_jumlah = $row['total_penerimaan_jumlah'] ?? 0;
        $total_penerimaan_nilai  = $row['total_penerimaan_nilai'] ?? 0;

        $pengeluaran_jumlah = $row['total_pengeluaran_jumlah'] ?? 0;
        $pengeluaran_nilai  = $row['total_pengeluaran_nilai'] ?? 0;
        
        $saldo_akhir_jumlah = $total_penerimaan_jumlah - $pengeluaran_jumlah;
        $saldo_akhir_nilai  = $total_penerimaan_nilai - $pengeluaran_nilai;
        $saldo_akhir_harga_avg = ($saldo_akhir_jumlah > 0) ? $saldo_akhir_nilai / $saldo_akhir_jumlah : 0;
        
        $laporan_data[] = [
            'id_produk'                  => $id_produk,
            'nama_kategori'              => $row['nama_kategori'],
            'spesifikasi'                => $row['spesifikasi'],
            'satuan'                     => $row['satuan'],
            'saldo_awal_jumlah'          => $saldo_awal_jumlah,
            'saldo_awal_harga'           => $saldo_awal_harga,
            'saldo_awal_nilai'           => $saldo_awal_nilai,
            'penerimaan_jumlah_total'    => $total_penerimaan_jumlah,
            'penerimaan_nilai_total'     => $total_penerimaan_nilai,
            'harga_batch_aktif'          => $harga_untuk_kolom_penerimaan, // Menggunakan variabel dengan logika fallback
            'pengeluaran_jumlah'         => $pengeluaran_jumlah,
            'pengeluaran_harga_terakhir' => $harga_keluar_terakhir_map[$id_produk] ?? 0,
            'pengeluaran_nilai'          => $pengeluaran_nilai,
            'saldo_akhir_jumlah'         => $saldo_akhir_jumlah,
            'saldo_akhir_harga'          => $saldo_akhir_harga_avg,
            'saldo_akhir_nilai'          => $saldo_akhir_nilai,
            'tgl_perolehan'              => $row['tanggal_penerimaan'] ?? '-',
            'bentuk_kontrak'             => $row['bentuk_kontrak'] ?? '-',
            'nama_penyedia'              => $row['nama_penyedia'] ?? '-',
        ];
    }
    $stmt->close();
    return $laporan_data;
}


// Bagian Tampilan HTML (tidak perlu diubah)
if (!defined('IS_LOGIC_CALL')) {
    require_once 'template_header.php';
    if ($_SESSION['role'] !== 'admin') {
        echo "<p>Maaf, Anda tidak memiliki akses ke halaman ini.</p>";
        require_once 'template_footer.php';
        exit;
    }
    $laporan_data = getInventoryRealizationReportData($koneksi);
?>

<header class="main-header">
    <h1>Laporan Realisasi Persediaan</h1>
    <p>Laporan ini menampilkan ringkasan total pergerakan barang dari awal hingga saat ini.</p>
</header>
<section class="content-section">
    <div class="action-bar" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;">
        <a href="cetak_laporan_realisasi.php" target="_blank" class="btn btn-secondary">Cetak Laporan</a>
        <a href="download_laporan_realisasi.php" class="btn btn-primary">Download (CSV)</a>
    </div>

    <div class="table-container">
        <table class="product-table report-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th><th rowspan="2">Nama Barang</th><th rowspan="2">Spesifikasi</th><th rowspan="2">Satuan</th>
                    <th colspan="3">Saldo Awal</th>
                    <th colspan="3">Penerimaan (Total)</th>
                    <th colspan="3">Pengeluaran</th>
                    <th colspan="3">Saldo Akhir</th>
                    <th rowspan="2">Tgl Perolehan Terakhir</th><th rowspan="2">Bentuk Kontrak</th><th rowspan="2">Nama Penyedia</th>
                </tr>
                <tr>
                    <th>Jml</th><th>Harga</th><th>Total</th>
                    <th>Jml</th><th>Harga Batch Aktif</th><th>Nilai Total</th>
                    <th>Jml</th><th>Harga Keluar Terakhir</th><th>Nilai Total</th>
                    <th>Jml</th><th>Harga Avg.</th><th>Nilai Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr><td colspan="19" class="text-center">Tidak ada data.</td></tr>
                <?php else: $no = 1; foreach ($laporan_data as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><a href="kartu_stok.php?id_produk=<?php echo $item['id_produk']; ?>" target="_blank"><?php echo htmlspecialchars($item['nama_kategori']); ?></a></td>
                    <td><?php echo htmlspecialchars($item['spesifikasi']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_awal_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_awal_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['penerimaan_jumlah_total']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['harga_batch_aktif']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['penerimaan_nilai_total']); ?></td>
                    <td class="text-center"><?php echo number_format($item['pengeluaran_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_harga_terakhir']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['pengeluaran_nilai']); ?></td>
                    <td class="text-center"><?php echo number_format($item['saldo_akhir_jumlah']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_harga']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($item['saldo_akhir_nilai']); ?></td>
                    <td><?php echo $item['tgl_perolehan'] != '-' ? date('d-m-Y', strtotime($item['tgl_perolehan'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($item['bentuk_kontrak']); ?></td>
                    <td><?php echo htmlspecialchars($item['nama_penyedia']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
    require_once 'template_footer.php';
}
?>