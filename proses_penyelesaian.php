<?php
require_once 'koneksi.php';
session_start();

// Keamanan: Hanya admin yang login via POST
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Validasi data utama yang dikirim
if (!isset($_POST['selesaikan'], $_POST['id_perbaikan'])) {
    die("Akses ditolak atau data tidak lengkap.");
}

// Ambil data utama
$id_perbaikan = (int)$_POST['id_perbaikan'];
$catatan_admin = trim($_POST['catatan_admin']);
$tanggal_selesai = date('Y-m-d H:i:s');

// Ambil data komponen sebagai array
$komponen_list = $_POST['komponen'] ?? [];

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // Siapkan statement untuk insert komponen di luar loop untuk efisiensi
    $stmt_komp = $koneksi->prepare(
        "INSERT INTO komponen_perbaikan (id_perbaikan, nama_komponen, jumlah, harga_satuan, total_harga) 
         VALUES (?, ?, ?, ?, ?)"
    );

    // Loop melalui setiap komponen yang diinput dari form
    // Gunakan count dari salah satu array (misal 'nama') sebagai acuan jumlah baris
    if (!empty($komponen_list['nama'])) {
        for ($i = 0; $i < count($komponen_list['nama']); $i++) {
            $nama = trim($komponen_list['nama'][$i]);
            $jumlah = (int)$komponen_list['jumlah'][$i];
            $harga_satuan = (float)$komponen_list['harga'][$i];

            // Lakukan insert hanya jika nama komponen diisi dan jumlah valid
            // Ini untuk menangani jika ada baris kosong yang tidak sengaja terkirim
            if (!empty($nama) && $jumlah > 0) {
                $total_harga = $jumlah * $harga_satuan;

                $stmt_komp->bind_param("isidd", $id_perbaikan, $nama, $jumlah, $harga_satuan, $total_harga);
                $stmt_komp->execute();
            }
        }
    }
    $stmt_komp->close();


    // Setelah semua komponen berhasil disimpan, update status perbaikan utama
    $stmt_utama = $koneksi->prepare(
        "UPDATE perbaikan_aset SET status_perbaikan = 'Selesai', catatan_admin = ?, tanggal_selesai = ? WHERE id = ?"
    );
    $stmt_utama->bind_param("ssi", $catatan_admin, $tanggal_selesai, $id_perbaikan);
    $stmt_utama->execute();
    $stmt_utama->close();

    // Jika semua langkah di atas berhasil, simpan permanen perubahan
    $koneksi->commit();

    // Arahkan kembali ke daftar laporan kerusakan dengan notifikasi sukses
    $_SESSION['success_message'] = "Laporan perbaikan #{$id_perbaikan} berhasil diselesaikan.";
    header('Location: daftar_kerusakan.php');
    exit;

} catch (Exception $e) {
    // Jika ada error di salah satu langkah, batalkan semua perubahan
    $koneksi->rollback();
    
    // Simpan pesan error ke session dan arahkan kembali ke form
    $_SESSION['error_message'] = "Gagal menyelesaikan perbaikan. Error: " . $e->getMessage();
    header('Location: selesaikan_perbaikan.php?id=' . $id_perbaikan);
    exit;
}
?>