<?php
require_once 'koneksi.php';
session_start();

// Pastikan hanya admin yang bisa mengakses dan requestnya adalah POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Pastikan semua data POST yang dibutuhkan ada
if (!isset($_POST['id_permintaan'], $_POST['aksi'])) {
    die("Data tidak lengkap.");
}

$id_permintaan = $_POST['id_permintaan'];
$aksi = $_POST['aksi'];
$catatan_admin = $_POST['catatan_admin'] ?? ''; // Gunakan null coalescing operator untuk keamanan
$tanggal_proses = date('Y-m-d H:i:s');
$status_baru = '';

// Tentukan status baru berdasarkan aksi
if ($aksi == 'setujui') {
    $status_baru = 'Disetujui';
} elseif ($aksi == 'tolak') {
    $status_baru = 'Ditolak';
} else {
    die("Aksi tidak valid.");
}

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // 1. Update status permintaan di tabel `permintaan`
    $stmt1 = $koneksi->prepare("UPDATE permintaan SET status = ?, catatan_admin = ?, tanggal_diproses = ? WHERE id = ?");
    $stmt1->bind_param("sssi", $status_baru, $catatan_admin, $tanggal_proses, $id_permintaan);
    $stmt1->execute();

    // 2. Jika disetujui, kurangi stok barang di tabel `produk`
    if ($status_baru == 'Disetujui') {
        // Ambil semua item yang diminta dalam permintaan ini
        $stmt_items = $koneksi->prepare("SELECT id_produk, jumlah FROM detail_permintaan WHERE id_permintaan = ?");
        $stmt_items->bind_param("i", $id_permintaan);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        // Siapkan statement untuk update stok (hanya perlu disiapkan sekali)
        $stmt_update_stok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");

        while ($item = $result_items->fetch_assoc()) {
            $jumlah_diminta = $item['jumlah'];
            $id_produk = $item['id_produk'];
            
            // Ikat parameter dan eksekusi untuk setiap item
            $stmt_update_stok->bind_param("ii", $jumlah_diminta, $id_produk);
            $stmt_update_stok->execute();
        }
    }

    // Jika semua query di atas berhasil, commit (simpan permanen) perubahan
    $koneksi->commit();

    // <<-- INI PERUBAHANNYA -->>
    // Redirect kembali ke halaman DAFTAR PERMINTAAN dengan notifikasi sukses
    header('Location: daftar_permintaan.php?status=proses_sukses');
    exit;

} catch (mysqli_sql_exception $exception) {
    // Jika ada error di salah satu query, batalkan semua perubahan
    $koneksi->rollback();
    
    // Tampilkan pesan error yang informatif
    die("Gagal memproses permintaan. Error: " . $exception->getMessage());
}
?>