<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Hanya admin via POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}
if (!isset($_POST['tgl_akhir_periode'])) {
    die("Tanggal akhir periode tidak ditemukan.");
}

$tgl_akhir_periode = $_POST['tgl_akhir_periode'];

// Mulai transaksi database. Jika satu langkah gagal, semua akan dibatalkan.
$koneksi->begin_transaction();

try {
    // --- Langkah 1: Hitung Saldo Akhir untuk semua produk sampai tanggal akhir periode ---
    $produk_list = $koneksi->query("SELECT id, stok_awal, harga_awal, harga FROM produk");
    $semua_produk = [];
    while($p = $produk_list->fetch_assoc()) {
        $semua_produk[$p['id']] = $p;
    }

    $penerimaan_total = [];
    $stmt1 = $koneksi->prepare("SELECT id_produk, SUM(jumlah) as total FROM penerimaan WHERE DATE(tanggal_penerimaan) <= ?");
    $stmt1->bind_param("s", $tgl_akhir_periode);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    while($p = $res1->fetch_assoc()) {
        $penerimaan_total[$p['id_produk']] = $p['total'];
    }

    $pengeluaran_total = [];
    $stmt2 = $koneksi->prepare("SELECT dp.id_produk, SUM(dp.jumlah) as total FROM detail_permintaan dp JOIN permintaan p ON dp.id_permintaan = p.id WHERE p.status = 'Disetujui' AND DATE(p.tanggal_diproses) <= ?");
    $stmt2->bind_param("s", $tgl_akhir_periode);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while($p = $res2->fetch_assoc()) {
        $pengeluaran_total[$p['id_produk']] = $p['total'];
    }

    // --- Langkah 2: Update setiap produk dengan Saldo Akhir sebagai Saldo Awal baru ---
    $stmt_update_produk = $koneksi->prepare("UPDATE produk SET stok_awal = ?, stok = ?, harga_awal = ? WHERE id = ?");

    foreach ($semua_produk as $id_produk => $produk) {
        $terima = $penerimaan_total[$id_produk] ?? 0;
        $keluar = $pengeluaran_total[$id_produk] ?? 0;
        
        $saldo_akhir = $produk['stok_awal'] + $terima - $keluar;
        $harga_terbaru = $produk['harga']; // Harga terakhir yang tercatat

        // Jadikan saldo akhir sebagai stok, stok awal, dan harga awal baru untuk periode berikutnya
        $stmt_update_produk->bind_param("iidi", $saldo_akhir, $saldo_akhir, $harga_terbaru, $id_produk);
        $stmt_update_produk->execute();
    }
    
    // --- Langkah 3: Kosongkan semua data transaksi ---
    $koneksi->query("SET FOREIGN_KEY_CHECKS=0");
    $koneksi->query("TRUNCATE TABLE `stok_batch`");
    $koneksi->query("TRUNCATE TABLE `detail_permintaan`");
    $koneksi->query("TRUNCATE TABLE `penerimaan`");
    $koneksi->query("TRUNCATE TABLE `permintaan`");
    $koneksi->query("SET FOREIGN_KEY_CHECKS=1");

    // Jika semua berhasil, simpan permanen
    $koneksi->commit();

    // Redirect dengan notifikasi sukses
    header('Location: laporan_persediaan.php?status=tutup_buku_sukses');
    exit;

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $koneksi->rollback();
    die("Gagal melakukan proses Tutup Buku. Error: " . $e->getMessage());
}
?>