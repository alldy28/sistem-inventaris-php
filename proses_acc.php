<?php
require_once 'koneksi.php'; // Pastikan path ini benar
session_start();

/**
 * Memproses pengurangan stok FIFO.
 * Sekarang menerima statement yang sudah disiapkan sebagai argumen untuk efisiensi.
 *
 * @param mysqli $koneksi
 * @param int $id_produk
 * @param int $jumlah_keluar
 * @param mysqli_stmt $stmt_update_batch
 * @param mysqli_stmt $stmt_update_produk_stok
 * @param mysqli_stmt $stmt_update_harga_produk
 * @return array ['nilai_total' => float, 'harga_terakhir' => float]
 * @throws Exception
 */
function processStockIssuance(
    mysqli $koneksi,
    int $id_produk,
    int $jumlah_keluar,
    mysqli_stmt $stmt_update_batch,
    mysqli_stmt $stmt_update_produk_stok,
    mysqli_stmt $stmt_update_harga_produk
): array {
    if ($jumlah_keluar <= 0) {
        return ['nilai_total' => 0, 'harga_terakhir' => 0];
    }

    $jumlah_masih_dibutuhkan = $jumlah_keluar;
    $total_nilai_keluar_item = 0;
    $harga_batch_terakhir_digunakan = 0;

    $stmt_batches = $koneksi->prepare("SELECT id, sisa_stok, harga_beli FROM stok_batch WHERE id_produk = ? AND sisa_stok > 0 ORDER BY tanggal_masuk ASC, id ASC");
    $stmt_batches->bind_param("i", $id_produk);
    $stmt_batches->execute();
    $result_batches = $stmt_batches->get_result();
    $batches = $result_batches->fetch_all(MYSQLI_ASSOC);
    $stmt_batches->close();

    $total_stok_tersedia = array_sum(array_column($batches, 'sisa_stok'));
    if ($total_stok_tersedia < $jumlah_keluar) {
        throw new Exception("Stok untuk Produk ID #$id_produk tidak mencukupi. Dibutuhkan: $jumlah_keluar, Tersedia: $total_stok_tersedia.");
    }

    for ($i = 0; $i < count($batches); $i++) {
        $batch = $batches[$i];
        if ($jumlah_masih_dibutuhkan <= 0) break;
        
        $ambil_dari_batch_ini = min($jumlah_masih_dibutuhkan, $batch['sisa_stok']);
        if ($ambil_dari_batch_ini > 0) {
            $total_nilai_keluar_item += $ambil_dari_batch_ini * $batch['harga_beli'];
            $harga_batch_terakhir_digunakan = $batch['harga_beli'];
            
            $stmt_update_batch->bind_param("ii", $ambil_dari_batch_ini, $batch['id']);
            $stmt_update_batch->execute();
            
            if (($batch['sisa_stok'] - $ambil_dari_batch_ini) <= 0) {
                if (isset($batches[$i + 1])) {
                    $harga_baru = $batches[$i + 1]['harga_beli'];
                    $stmt_update_harga_produk->bind_param("di", $harga_baru, $id_produk);
                    $stmt_update_harga_produk->execute();
                }
            }
            $jumlah_masih_dibutuhkan -= $ambil_dari_batch_ini;
        }
    }

    $stmt_update_produk_stok->bind_param("ii", $jumlah_keluar, $id_produk);
    $stmt_update_produk_stok->execute();

    return [
        'nilai_total' => $total_nilai_keluar_item,
        'harga_terakhir' => $harga_batch_terakhir_digunakan
    ];
}


// --- LOGIKA UTAMA PROSES PERMINTAAN ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}
$id_permintaan = (int)($_POST['id_permintaan'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
// ... (validasi input lainnya) ...

$catatan_admin = $_POST['catatan_admin'] ?? '';
$tanggal_proses = date('Y-m-d H:i:s');
$status_baru = ($aksi == 'setujui') ? 'Disetujui' : 'Ditolak';

$koneksi->begin_transaction();
try {
    $stmt_permintaan = $koneksi->prepare("UPDATE permintaan SET status = ?, catatan_admin = ?, tanggal_diproses = ? WHERE id = ?");
    $stmt_permintaan->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_proses, $id_permintaan);
    $stmt_permintaan->execute();
    $stmt_permintaan->close();

    if ($status_baru == 'Disetujui') {
        $jumlah_disetujui_map = $_POST['jumlah_disetujui'] ?? [];
        
        $stmt_items = $koneksi->prepare("SELECT id, id_produk FROM detail_permintaan WHERE id_permintaan = ?");
        $stmt_items->bind_param("i", $id_permintaan);
        $stmt_items->execute();
        $items_permintaan = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_items->close();

        // PERBAIKAN: Siapkan SEMUA statement UPDATE satu kali di luar loop
        $stmt_update_batch = $koneksi->prepare("UPDATE stok_batch SET sisa_stok = sisa_stok - ? WHERE id = ?");
        $stmt_update_produk_stok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmt_update_harga_produk = $koneksi->prepare("UPDATE produk SET harga = ? WHERE id = ?");
        $stmt_update_detail = $koneksi->prepare("UPDATE detail_permintaan SET jumlah_disetujui = ?, nilai_keluar_fifo = ?, harga_keluar_terakhir = ? WHERE id = ?");

        foreach ($items_permintaan as $item) {
            $id_detail_permintaan = $item['id'];
            $id_produk = $item['id_produk'];
            $jumlah_disetujui = (int)($jumlah_disetujui_map[$id_detail_permintaan] ?? 0);

            // Panggil fungsi dengan melewatkan statement yang sudah disiapkan
            $hasil_proses = processStockIssuance(
                $koneksi, $id_produk, $jumlah_disetujui,
                $stmt_update_batch, $stmt_update_produk_stok, $stmt_update_harga_produk
            );
            $nilai_keluar = $hasil_proses['nilai_total'];
            $harga_terakhir = $hasil_proses['harga_terakhir'];

            $stmt_update_detail->bind_param("iddi", $jumlah_disetujui, $nilai_keluar, $harga_terakhir, $id_detail_permintaan);
            $stmt_update_detail->execute();
        }

        // Tutup semua statement setelah loop selesai
        $stmt_update_batch->close();
        $stmt_update_produk_stok->close();
        $stmt_update_harga_produk->close();
        $stmt_update_detail->close();
    }

    $koneksi->commit();
    header('Location: daftar_permintaan.php?status_update=sukses');
    exit;
} catch (Exception $e) {
    $koneksi->rollback();
    $_SESSION['error_message'] = "Gagal memproses permintaan. Error: " . $e->getMessage();
    header('Location: detail_permintaan.php?id=' . $id_permintaan);
    exit;
}
?>