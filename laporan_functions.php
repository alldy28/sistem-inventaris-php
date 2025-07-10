<?php
// laporan_functions.php

/**
 * Mengambil data laporan persediaan untuk bulan dan tahun tertentu.
 */
function getMonthlyInventoryReport(mysqli $koneksi, int $bulan, int $tahun): array {
    $tanggal_awal = date('Y-m-d H:i:s', mktime(0, 0, 0, $bulan, 1, $tahun));
    $tanggal_akhir = date('Y-m-t H:i:s', mktime(23, 59, 59, $bulan, 1, $tahun));

    $sql = "
        SELECT
            p.id AS id_produk, kp.nama_kategori, p.spesifikasi, p.satuan,
            (
                COALESCE((SELECT SUM(sb.jumlah_awal) FROM stok_batch sb WHERE sb.id_produk = p.id AND sb.tanggal_masuk < '$tanggal_awal'), 0) +
                COALESCE((SELECT SUM(pr.jumlah) FROM penerimaan pr WHERE pr.id_produk = p.id AND pr.tanggal_penerimaan < '$tanggal_awal'), 0) -
                COALESCE((SELECT SUM(dp.jumlah_disetujui) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan < '$tanggal_awal'), 0)
            ) AS saldo_awal_jumlah,
            (
                COALESCE((SELECT SUM(sb.jumlah_awal * sb.harga_beli) FROM stok_batch sb WHERE sb.id_produk = p.id AND sb.tanggal_masuk < '$tanggal_awal'), 0) +
                COALESCE((SELECT SUM(pr.jumlah * pr.harga_satuan) FROM penerimaan pr WHERE pr.id_produk = p.id AND pr.tanggal_penerimaan < '$tanggal_awal'), 0) -
                COALESCE((SELECT SUM(dp.nilai_keluar_fifo) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan < '$tanggal_awal'), 0)
            ) AS saldo_awal_nilai,
            COALESCE((SELECT SUM(jumlah) FROM penerimaan WHERE id_produk = p.id AND tanggal_penerimaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS penerimaan_jumlah,
            COALESCE((SELECT SUM(jumlah * harga_satuan) FROM penerimaan WHERE id_produk = p.id AND tanggal_penerimaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS penerimaan_nilai,
            COALESCE((SELECT SUM(dp.jumlah_disetujui) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS pengeluaran_jumlah,
            COALESCE((SELECT SUM(dp.nilai_keluar_fifo) FROM detail_permintaan dp JOIN permintaan per ON dp.id_permintaan = per.id WHERE dp.id_produk = p.id AND per.status = 'Disetujui' AND per.tanggal_permintaan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'), 0) AS pengeluaran_nilai
        FROM produk p
        JOIN kategori_produk kp ON p.id_kategori = kp.id
        ORDER BY kp.nama_kategori, p.spesifikasi ASC
    ";
    
    $result = $koneksi->query($sql);
    $report_data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['saldo_awal_jumlah'] == 0 && $row['penerimaan_jumlah'] == 0 && $row['pengeluaran_jumlah'] == 0) continue;
        $row['saldo_akhir_jumlah'] = $row['saldo_awal_jumlah'] + $row['penerimaan_jumlah'] - $row['pengeluaran_jumlah'];
        $row['saldo_akhir_nilai'] = $row['saldo_awal_nilai'] + $row['penerimaan_nilai'] - $row['pengeluaran_nilai'];
        $report_data[] = $row;
    }
    return $report_data;
}