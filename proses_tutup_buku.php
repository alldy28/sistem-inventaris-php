<?php
ob_start();
session_start();
require_once 'koneksi.php';

// Keamanan dan Validasi Akses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil tahun tutup buku (tahun saat ini)
$tahun_tutup_buku = date('Y');

// 1. Ambil data saldo akhir dari laporan realisasi
define('IS_LOGIC_CALL', true);
require_once 'laporan_realisasi.php';
$laporan_data = getInventoryRealizationReport($koneksi);

// Memulai transaksi database
$koneksi->begin_transaction();

try {
    // 2. SALIN DATA KE TABEL HISTORI
    // Menyalin data dengan menambahkan tahun tutup buku
    $koneksi->query("INSERT INTO histori_penerimaan SELECT '$tahun_tutup_buku', penerimaan.* FROM penerimaan");
    $koneksi->query("INSERT INTO histori_permintaan SELECT '$tahun_tutup_buku', permintaan.* FROM permintaan");
    $koneksi->query("INSERT INTO histori_detail_permintaan SELECT '$tahun_tutup_buku', detail_permintaan.* FROM detail_permintaan");
    $koneksi->query("INSERT INTO histori_stok_batch SELECT '$tahun_tutup_buku', stok_batch.* FROM stok_batch");

    // 3. HAPUS DATA TRANSAKSI DARI TABEL UTAMA
    $koneksi->query("SET FOREIGN_KEY_CHECKS=0");
    $koneksi->query("TRUNCATE TABLE `detail_permintaan`");
    $koneksi->query("TRUNCATE TABLE `permintaan`");
    $koneksi->query("TRUNCATE TABLE `stok_batch`");
    $koneksi->query("TRUNCATE TABLE `penerimaan`");
    $koneksi->query("SET FOREIGN_KEY_CHECKS=1");

    // 4. BUAT SALDO AWAL BARU (seperti sebelumnya)
    $stmt_penerimaan = $koneksi->prepare("INSERT INTO penerimaan (id_produk, jumlah, harga_satuan, tanggal_penerimaan, nama_penyedia, nomor_faktur) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt_batch = $koneksi->prepare("INSERT INTO stok_batch (id_produk, id_penerimaan, jumlah_awal, sisa_stok, harga_beli, tanggal_masuk) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_produk = $koneksi->prepare("UPDATE produk SET stok = ?, harga = ? WHERE id = ?");

    foreach ($laporan_data as $item) {
        if ($item['saldo_akhir_jumlah'] > 0) {
            $penyedia = "Sistem (Saldo Awal " . ($tahun_tutup_buku + 1) . ")";
            $faktur = "SALDOAWAL-" . ($tahun_tutup_buku + 1);
            $stmt_penerimaan->bind_param("iddss", $item['id_produk'], $item['saldo_akhir_jumlah'], $item['saldo_akhir_harga'], $penyedia, $faktur);
            $stmt_penerimaan->execute();
            $id_penerimaan_baru = $koneksi->insert_id;

            $stmt_batch->bind_param("iiidd", $item['id_produk'], $id_penerimaan_baru, $item['saldo_akhir_jumlah'], $item['saldo_akhir_jumlah'], $item['saldo_akhir_harga']);
            $stmt_batch->execute();
            
            $stmt_produk->bind_param("idi", $item['saldo_akhir_jumlah'], $item['saldo_akhir_harga'], $item['id_produk']);
            $stmt_produk->execute();
        } else {
            $nol = 0;
            $stmt_produk->bind_param("idi", $nol, $nol, $item['id_produk']);
            $stmt_produk->execute();
        }
    }
    
    $stmt_penerimaan->close();
    $stmt_batch->close();
    $stmt_produk->close();

    // Jika semua berhasil, konfirmasi transaksi
    $koneksi->commit();
    $_SESSION['success_message'] = "Proses Tutup Buku untuk tahun $tahun_tutup_buku berhasil. Data telah diarsipkan ke histori.";

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $koneksi->rollback();
    $_SESSION['error_message'] = "PROSES GAGAL: " . $e->getMessage();
}

header('Location: laporan_realisasi.php');
ob_end_flush();
exit;
?>