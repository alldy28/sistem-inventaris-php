<?php
// data_dashboard.php

/**
 * Mengambil data rekapitulasi nilai penerimaan dan pengeluaran per bulan untuk tahun yang dipilih.
 * @param mysqli $koneksi
 * @param int $tahun
 * @return array
 */

 function getJumlahPermintaanMasuk(mysqli $koneksi): int
{
    // PERBAIKAN: Mengubah 'Menunggu Persetujuan' menjadi 'Pending'
    $sql = "SELECT COUNT(id) as jumlah FROM permintaan WHERE status = 'Pending'";
    $result = $koneksi->query($sql);
    $row = $result->fetch_assoc();
    return (int)$row['jumlah'];
}


function getDataPenerimaanPengeluaranTahunan(mysqli $koneksi, int $tahun): array 
{
    $data = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
        'penerimaan' => array_fill(0, 12, 0),
        'pengeluaran' => array_fill(0, 12, 0)
    ];

    $sql_penerimaan = "SELECT MONTH(tanggal_penerimaan) as bulan, SUM(jumlah * harga_satuan) as total_nilai FROM penerimaan WHERE YEAR(tanggal_penerimaan) = ? GROUP BY MONTH(tanggal_penerimaan)";
    $stmt1 = $koneksi->prepare($sql_penerimaan);
    $stmt1->bind_param("i", $tahun);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    while ($row = $result1->fetch_assoc()) {
        $data['penerimaan'][$row['bulan'] - 1] = (float)$row['total_nilai'];
    }
    $stmt1->close();

    $sql_pengeluaran = "SELECT MONTH(per.tanggal_permintaan) as bulan, SUM(dp.nilai_keluar_fifo) as total_nilai FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE YEAR(per.tanggal_permintaan) = ? AND per.status = 'Disetujui' GROUP BY MONTH(per.tanggal_permintaan)";
    $stmt2 = $koneksi->prepare($sql_pengeluaran);
    $stmt2->bind_param("i", $tahun);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $data['pengeluaran'][$row['bulan'] - 1] = (float)$row['total_nilai'];
    }
    $stmt2->close();
    
    return $data;
}

/**
 * Mengambil data komposisi barang keluar per kategori untuk bulan dan tahun yang dipilih.
 * @param mysqli $koneksi
 * @param int $bulan
 * @param int $tahun
 * @return array
 */
function getDataPengeluaranPerKategoriBulanan(mysqli $koneksi, int $bulan, int $tahun): array
{
    $data = [
        'labels' => [],
        'jumlah' => []
    ];

    $sql = "
        SELECT kp.nama_kategori, SUM(dp.jumlah_disetujui) as total_jumlah
        FROM detail_permintaan dp
        JOIN permintaan per ON dp.id_permintaan = per.id
        JOIN produk p ON dp.id_produk = p.id
        JOIN kategori_produk kp ON p.id_kategori = kp.id
        WHERE MONTH(per.tanggal_permintaan) = ? AND YEAR(per.tanggal_permintaan) = ? AND per.status = 'Disetujui'
        GROUP BY kp.nama_kategori
        ORDER BY total_jumlah DESC
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ii", $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $data['labels'][] = $row['nama_kategori'];
        $data['jumlah'][] = (int)$row['total_jumlah'];
    }
    $stmt->close();

    return $data;
}
?>