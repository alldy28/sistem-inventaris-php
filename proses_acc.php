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
    // 1. Update status permintaan utama
    $stmt_update_permintaan = $koneksi->prepare("UPDATE permintaan SET status = ?, catatan_admin = ?, tanggal_diproses = ? WHERE id = ?");
    $stmt_update_permintaan->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_proses, $id_permintaan);
    $stmt_update_permintaan->execute();

    // 2. Jika disetujui, jalankan logika FIFO
    if ($status_baru == 'Disetujui') {
        // Ambil semua item dari detail permintaan
        $stmt_items = $koneksi->prepare("SELECT id, id_produk, jumlah FROM detail_permintaan WHERE id_permintaan = ?");
        $stmt_items->bind_param("i", $id_permintaan);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        // Siapkan statement di luar loop untuk efisiensi
        $stmt_update_batch = $koneksi->prepare("UPDATE stok_batch SET sisa_stok = sisa_stok - ? WHERE id = ?");
        $stmt_update_produk_stok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmt_update_nilai_fifo = $koneksi->prepare("UPDATE detail_permintaan SET nilai_keluar_fifo = ? WHERE id = ?");
        $stmt_update_harga_produk = $koneksi->prepare("UPDATE produk SET harga = ? WHERE id = ?");

        while ($item = $result_items->fetch_assoc()) {
            $id_detail_permintaan = $item['id'];
            $id_produk = $item['id_produk'];
            $jumlah_diminta = $item['jumlah'];
            $total_nilai_keluar_item = 0;
            $jumlah_masih_dibutuhkan = $jumlah_diminta;

            // Ambil semua batch yang relevan, urutkan dari yang terlama
            $stmt_batches = $koneksi->prepare("SELECT id, sisa_stok, harga_beli FROM stok_batch WHERE id_produk = ? AND sisa_stok > 0 ORDER BY tanggal_masuk ASC");
            $stmt_batches->bind_param("i", $id_produk);
            $stmt_batches->execute();
            $result_batches = $stmt_batches->get_result();

            // Loop melalui setiap batch untuk memenuhi permintaan
            while ($batch = $result_batches->fetch_assoc()) {
                if ($jumlah_masih_dibutuhkan <= 0) break;
                
                $stok_di_batch = $batch['sisa_stok'];
                $ambil_dari_batch_ini = min($jumlah_masih_dibutuhkan, $stok_di_batch);
                
                $total_nilai_keluar_item += $ambil_dari_batch_ini * $batch['harga_beli'];
                
                // Kurangi stok di batch
                $stmt_update_batch->bind_param("ii", $ambil_dari_batch_ini, $batch['id']);
                $stmt_update_batch->execute();

                // Cek jika batch ini habis & update harga utama
                if (($stok_di_batch - $ambil_dari_batch_ini) <= 0) {
                    $stmt_next_batch = $koneksi->prepare("SELECT harga_beli FROM stok_batch WHERE id_produk = ? AND sisa_stok > 0 ORDER BY tanggal_masuk ASC LIMIT 1");
                    $stmt_next_batch->bind_param("i", $id_produk);
                    $stmt_next_batch->execute();
                    $next_batch_result = $stmt_next_batch->get_result();
                    if ($next_batch_result->num_rows > 0) {
                        $harga_baru = $next_batch_result->fetch_assoc()['harga_beli'];
                        $stmt_update_harga_produk->bind_param("di", $harga_baru, $id_produk);
                        $stmt_update_harga_produk->execute();
                    }
                }
                
                $jumlah_masih_dibutuhkan -= $ambil_dari_batch_ini;
            }

            // Update stok total produk
            $stmt_update_produk_stok->bind_param("ii", $jumlah_diminta, $id_produk);
            $stmt_update_produk_stok->execute();

            // Simpan nilai FIFO yang akurat ke detail permintaan
            $stmt_update_nilai_fifo->bind_param("di", $total_nilai_keluar_item, $id_detail_permintaan);
            $stmt_update_nilai_fifo->execute();
        }
    }

    // Jika semua langkah berhasil, simpan permanen
    $koneksi->commit();

    header('Location: daftar_permintaan.php?status_update=sukses');
    exit;

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $koneksi->rollback();
    die("Gagal memproses permintaan. Error: " . $e->getMessage());
}
?>