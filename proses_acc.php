<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}
if (!isset($_POST['id_permintaan'], $_POST['aksi'])) {
    die("Data tidak lengkap.");
}

$id_permintaan = $_POST['id_permintaan'];
$aksi = $_POST['aksi'];
$catatan_admin = $_POST['catatan_admin'] ?? '';
$tanggal_proses = date('Y-m-d H:i:s');
$status_baru = ($aksi == 'setujui') ? 'Disetujui' : 'Ditolak';

$koneksi->begin_transaction();

try {
    // Langkah 1: Update status permintaan utama
    $stmt_update_permintaan = $koneksi->prepare("UPDATE permintaan SET status = ?, catatan_admin = ?, tanggal_diproses = ? WHERE id = ?");
    $stmt_update_permintaan->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_proses, $id_permintaan);
    $stmt_update_permintaan->execute();

    if ($status_baru == 'Disetujui') {
        $stmt_items = $koneksi->prepare("SELECT id, id_produk, jumlah FROM detail_permintaan WHERE id_permintaan = ?");
        $stmt_items->bind_param("i", $id_permintaan);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        // Siapkan statement di luar loop
        $stmt_update_batch = $koneksi->prepare("UPDATE stok_batch SET sisa_stok = sisa_stok - ? WHERE id = ?");
        $stmt_update_produk = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        // <<-- STATEMENT BARU UNTUK UPDATE NILAI FIFO -->>
        $stmt_update_nilai_fifo = $koneksi->prepare("UPDATE detail_permintaan SET nilai_keluar_fifo = ? WHERE id = ?");


        while ($item = $result_items->fetch_assoc()) {
            $id_detail_permintaan = $item['id'];
            $id_produk = $item['id_produk'];
            $jumlah_diminta = $item['jumlah'];
            $total_nilai_keluar_item = 0; // Inisialisasi nilai keluar untuk item ini

            // Ambil semua batch yang relevan
            $stmt_batches = $koneksi->prepare("SELECT id, sisa_stok, harga_beli FROM stok_batch WHERE id_produk = ? AND sisa_stok > 0 ORDER BY tanggal_masuk ASC");
            $stmt_batches->bind_param("i", $id_produk);
            $stmt_batches->execute();
            $result_batches = $stmt_batches->get_result();

            $jumlah_masih_dibutuhkan = $jumlah_diminta;

            while ($batch = $result_batches->fetch_assoc()) {
                if ($jumlah_masih_dibutuhkan <= 0) break;

                $ambil_dari_batch_ini = min($jumlah_masih_dibutuhkan, $batch['sisa_stok']);
                
                // <<-- HITUNG NILAI FIFO -->>
                $total_nilai_keluar_item += $ambil_dari_batch_ini * $batch['harga_beli'];
                
                // Kurangi sisa stok di batch
                $stmt_update_batch->bind_param("ii", $ambil_dari_batch_ini, $batch['id']);
                $stmt_update_batch->execute();

                $jumlah_masih_dibutuhkan -= $ambil_dari_batch_ini;
            }

            // Setelah loop batch, update stok total produk
            $stmt_update_produk->bind_param("ii", $jumlah_diminta, $id_produk);
            $stmt_update_produk->execute();

            // <<-- SIMPAN NILAI FIFO YANG SUDAH DIHITUNG -->>
            $stmt_update_nilai_fifo->bind_param("di", $total_nilai_keluar_item, $id_detail_permintaan);
            $stmt_update_nilai_fifo->execute();
        }
    }

    $koneksi->commit();
    header('Location: daftar_permintaan.php?status=proses_sukses');
    exit;

} catch (Exception $e) {
    $koneksi->rollback();
    die("Gagal memproses permintaan. Error: " . $e->getMessage());
}
?>