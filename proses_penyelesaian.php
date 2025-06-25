<?php
require_once 'koneksi.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['selesaikan'])) { die("Akses ditolak."); }

// Ambil semua data dari form
$id_perbaikan = $_POST['id_perbaikan'];
$id_user_peminta = $_POST['id_user_peminta']; // User yang asetnya rusak
$id_produk = $_POST['id_produk'];
$jumlah_diminta = $_POST['jumlah'];
$catatan_admin = $_POST['catatan_admin'];
$tanggal_proses = date('Y-m-d H:i:s');

$koneksi->begin_transaction();
try {
    // ---- BAGIAN LOGIKA PENGELUARAN BARANG (FIFO) ----
    
    // 1. Buat record 'permintaan' baru yang terhubung ke perbaikan ini
    $stmt1 = $koneksi->prepare("INSERT INTO permintaan (id_user, id_perbaikan_aset, status, catatan_admin, tanggal_permintaan, tanggal_diproses) VALUES (?, ?, 'Disetujui', ?, ?, ?)");
    $catatan_permintaan = "Untuk perbaikan aset ID #" . $id_perbaikan;
    $stmt1->bind_param("iisss", $id_user_peminta, $id_perbaikan, $catatan_permintaan, $tanggal_proses, $tanggal_proses);
    $stmt1->execute();
    $id_permintaan_baru = $koneksi->insert_id;

    // 2. Dapatkan harga produk saat ini untuk referensi
    $harga_saat_ini = $koneksi->query("SELECT harga FROM produk WHERE id=$id_produk")->fetch_assoc()['harga'];
    
    // 3. Buat record 'detail_permintaan'
    $stmt2 = $koneksi->prepare("INSERT INTO detail_permintaan (id_permintaan, id_produk, jumlah, harga_saat_minta) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("iiid", $id_permintaan_baru, $id_produk, $jumlah_diminta, $harga_saat_ini);
    $stmt2->execute();
    $id_detail_permintaan = $koneksi->insert_id;

    // 4. Proses pengurangan stok via FIFO (sama seperti di proses_acc.php)
    $total_nilai_keluar_item = 0;
    $jumlah_masih_dibutuhkan = $jumlah_diminta;
    
    $stmt_batches = $koneksi->prepare("SELECT id, sisa_stok, harga_beli FROM stok_batch WHERE id_produk = ? AND sisa_stok > 0 ORDER BY tanggal_masuk ASC");
    $stmt_batches->bind_param("i", $id_produk);
    $stmt_batches->execute();
    $result_batches = $stmt_batches->get_result();

    $stmt_update_batch = $koneksi->prepare("UPDATE stok_batch SET sisa_stok = sisa_stok - ? WHERE id = ?");

    while ($batch = $result_batches->fetch_assoc()) {
        if ($jumlah_masih_dibutuhkan <= 0) break;
        $ambil_dari_batch_ini = min($jumlah_masih_dibutuhkan, $batch['sisa_stok']);
        $total_nilai_keluar_item += $ambil_dari_batch_ini * $batch['harga_beli'];
        $stmt_update_batch->bind_param("ii", $ambil_dari_batch_ini, $batch['id']);
        $stmt_update_batch->execute();
        $jumlah_masih_dibutuhkan -= $ambil_dari_batch_ini;
    }

    // 5. Update nilai FIFO di detail_permintaan
    $stmt_update_nilai_fifo = $koneksi->prepare("UPDATE detail_permintaan SET nilai_keluar_fifo = ? WHERE id = ?");
    $stmt_update_nilai_fifo->bind_param("di", $total_nilai_keluar_item, $id_detail_permintaan);
    $stmt_update_nilai_fifo->execute();

    // 6. Update stok total di tabel produk
    $stmt_update_produk = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
    $stmt_update_produk->bind_param("ii", $jumlah_diminta, $id_produk);
    $stmt_update_produk->execute();

    // ---- BAGIAN LOGIKA PERBAIKAN ASET ----
    // 7. Update status laporan perbaikan menjadi 'Selesai'
    $stmt_selesaikan = $koneksi->prepare("UPDATE perbaikan_aset SET status_perbaikan = 'Selesai', catatan_admin = ?, tanggal_selesai = ? WHERE id = ?");
    $stmt_selesaikan->bind_param("ssi", $catatan_admin, $tanggal_proses, $id_perbaikan);
    $stmt_selesaikan->execute();

    // Jika semua berhasil, commit transaksi
    $koneksi->commit();
    header('Location: daftar_kerusakan.php?status_update=sukses');
    exit;

} catch (Exception $e) {
    $koneksi->rollback();
    die("Gagal menyelesaikan perbaikan. Error: " . $e->getMessage());
}
?>