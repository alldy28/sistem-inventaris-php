<?php
require_once 'koneksi.php';
session_start();
ob_start();

// Keamanan dasar
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_POST['upload'])) {
    die("Akses ditolak.");
}

// Fungsi untuk mengatur pesan dan redirect
function redirect_with_message($pesan, $sukses = 0, $gagal = 'Semua') {
    $_SESSION['import_status'] = ['pesan' => $pesan, 'sukses' => $sukses, 'gagal' => $gagal];
    header('Location: produk.php');
    exit;
}

// Validasi File
if (!isset($_FILES['file_produk']) || $_FILES['file_produk']['error'] != 0) {
    redirect_with_message('Error: Tidak ada file yang diunggah atau terjadi kesalahan upload.');
}
if ($_FILES['file_produk']['size'] > 2097152) { // Maks 2MB
    redirect_with_message('Error: Ukuran file terlalu besar. Maksimal 2MB.');
}
$file_ext = strtolower(pathinfo($_FILES['file_produk']['name'], PATHINFO_EXTENSION));
if ($file_ext != 'csv') {
    redirect_with_message('Error: File yang diunggah harus berformat .csv');
}

// Baca dan Validasi Isi CSV
$file = fopen($_FILES['file_produk']['tmp_name'], 'r');
if ($file === FALSE) {
    redirect_with_message('Error: Gagal membuka file yang diunggah.');
}

fgetcsv($file); // Lewati baris header

$rows_to_insert = [];
$errors = [];
$all_nusp_ids_in_file = [];
$line_number = 1;

while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
    $line_number++;
    if (count($row) != 7) { $errors[] = "Baris #$line_number: Jumlah kolom tidak sesuai, seharusnya ada 7 kolom."; continue; }
    $nusp_id = trim($row[0]);
    $nama_barang = trim($row[1]);
    $satuan = trim($row[2]);
    if (empty($nama_barang)) { $errors[] = "Baris #$line_number: Nama Barang tidak boleh kosong."; }
    if (empty($satuan)) { $errors[] = "Baris #$line_number: Satuan tidak boleh kosong."; }
    if (!empty($nusp_id) && in_array($nusp_id, $all_nusp_ids_in_file)) { $errors[] = "Baris #$line_number: ID NUSP '$nusp_id' duplikat di dalam file CSV."; }
    if (!empty($nusp_id)) { $all_nusp_ids_in_file[] = $nusp_id; }
    $rows_to_insert[] = $row;
}
fclose($file);

// Cek Duplikasi dengan Database
if (!empty($all_nusp_ids_in_file)) {
    $placeholders = implode(',', array_fill(0, count($all_nusp_ids_in_file), '?'));
    $stmt_check_db = $koneksi->prepare("SELECT nusp_id FROM produk WHERE nusp_id IN ($placeholders)");
    $stmt_check_db->bind_param(str_repeat('s', count($all_nusp_ids_in_file)), ...$all_nusp_ids_in_file);
    $stmt_check_db->execute();
    $result_db = $stmt_check_db->get_result();
    while ($db_row = $result_db->fetch_assoc()) {
        $errors[] = "ID NUSP '" . htmlspecialchars($db_row['nusp_id']) . "' sudah ada di database.";
    }
}

// Keputusan Akhir
if (!empty($errors)) {
    $error_string = implode('<br>', $errors);
    redirect_with_message("Impor Gagal. Ditemukan " . count($errors) . " error:<br>" . $error_string);
}
if (empty($rows_to_insert)) {
    redirect_with_message("Tidak ada data valid untuk diimpor dari file.");
}

// Proses Simpan jika semua validasi lolos
$koneksi->begin_transaction();
try {
    $stmt = $koneksi->prepare("INSERT INTO produk (nusp_id, nama_barang, satuan, stok, harga, stok_awal, harga_awal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sukses_count = 0;

    foreach ($rows_to_insert as $row) {
        $nusp_id = $row[0];
        $nama_barang = $row[1];
        $satuan = $row[2];
        $stok = (int)$row[3];
        $harga = (float)$row[4];
        $stok_awal = (int)$row[5];
        $harga_awal = (float)$row[6];
        
        // <<-- INI PERBAIKANNYA: "ssiiddd" diubah menjadi "sssiddd" -->>
        $stmt->bind_param("sssiddd", $nusp_id, $nama_barang, $satuan, $stok, $harga, $stok_awal, $harga_awal);
        $stmt->execute();
        $sukses_count++;
    }

    $koneksi->commit();
    redirect_with_message("Impor Selesai. Berhasil menambahkan $sukses_count data produk baru.", $sukses_count, 0);

} catch (Exception $e) {
    $koneksi->rollback();
    redirect_with_message("Terjadi error database saat impor. Semua data dibatalkan. Error: " . $e->getMessage());
}

ob_end_flush();
?>