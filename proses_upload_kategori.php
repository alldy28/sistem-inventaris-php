<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

if (isset($_POST['upload'])) {
    // Cek apakah file berhasil diunggah
    if (isset($_FILES['file_kategori']) && $_FILES['file_kategori']['error'] == 0) {
        
        $filename = $_FILES['file_kategori']['tmp_name'];
        $file = fopen($filename, "r");

        // Mulai transaksi database
        $koneksi->begin_transaction();

        try {
            // Lewati baris header
            fgetcsv($file); 

            // Siapkan statement INSERT untuk efisiensi
            $stmt = $koneksi->prepare("INSERT INTO kategori_produk (nusp_id, nama_kategori) VALUES (?, ?)");

            // Baca setiap baris file CSV
            while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                // Pastikan baris memiliki 2 kolom dan tidak kosong
                if (count($data) == 2 && !empty($data[0]) && !empty($data[1])) {
                    $nusp_id = $data[0];
                    $nama_kategori = $data[1];
                    
                    $stmt->bind_param("ss", $nusp_id, $nama_kategori);
                    $stmt->execute();
                }
            }

            // Jika semua berhasil, commit transaksi
            $koneksi->commit();
            fclose($file);
            header('Location: kategori.php?status=import_sukses');
            exit;

        } catch (Exception $e) {
            // Jika ada error, batalkan semua perubahan
            $koneksi->rollback();
            fclose($file);
            header('Location: kategori.php?error=import_gagal&msg=' . urlencode($e->getMessage()));
            exit;
        }
        
    } else {
        // Jika file gagal diunggah
        header('Location: kategori.php?error=upload_gagal');
        exit;
    }
} else {
    header('Location: kategori.php');
    exit;
}
?>