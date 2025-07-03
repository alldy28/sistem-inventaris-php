<?php
require_once 'koneksi.php';
session_start();

// Keamanan dasar: hanya admin yang login dan menggunakan metode POST yang diizinkan
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Fungsi helper untuk memeriksa duplikasi NUSP ID
function isNuspIdDuplicate(mysqli $koneksi, string $nusp_id, ?int $exclude_id = null): bool
{
    $sql = "SELECT id FROM kategori_produk WHERE nusp_id = ?";
    $params = [$nusp_id];
    $types = "s";

    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->store_result();
    $count = $stmt->num_rows;
    $stmt->close();

    return $count > 0;
}

// Ambil aksi dari form
$aksi = $_POST['aksi'] ?? '';

switch ($aksi) {
    case 'tambah':
        // Validasi input
        if (empty($_POST['nama_kategori']) || empty($_POST['nusp_id'])) {
            $_SESSION['error_message'] = "Nama Kategori dan ID NUSP tidak boleh kosong.";
            header('Location: form_tambah_kategori.php');
            exit;
        }

        $nama_kategori = trim($_POST['nama_kategori']);
        $nusp_id = trim($_POST['nusp_id']);

        // Cek duplikasi sebelum insert
        if (isNuspIdDuplicate($koneksi, $nusp_id)) {
            $_SESSION['error_message'] = "ID NUSP '{$nusp_id}' sudah digunakan. Silakan gunakan ID lain.";
            header('Location: form_tambah_kategori.php');
            exit;
        }

        // Lanjutkan insert jika aman
        $stmt = $koneksi->prepare("INSERT INTO kategori_produk (nusp_id, nama_kategori) VALUES (?, ?)");
        $stmt->bind_param("ss", $nusp_id, $nama_kategori);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kategori baru berhasil ditambahkan.";
            header('Location: kategori.php');
        } else {
            $_SESSION['error_message'] = "Gagal menambah kategori: " . $stmt->error;
            header('Location: form_tambah_kategori.php');
        }
        $stmt->close();
        break;

    case 'edit':
        // Validasi input
        if (empty($_POST['nama_kategori']) || empty($_POST['nusp_id']) || empty($_POST['id'])) {
            $_SESSION['error_message'] = "Data tidak lengkap untuk proses edit.";
            header('Location: kategori.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $nama_kategori = trim($_POST['nama_kategori']);
        $nusp_id = trim($_POST['nusp_id']);

        // Cek duplikasi, tapi kecualikan ID yang sedang diedit
        if (isNuspIdDuplicate($koneksi, $nusp_id, $id)) {
            $_SESSION['error_message'] = "ID NUSP '{$nusp_id}' sudah digunakan oleh kategori lain.";
            header('Location: form_edit_kategori.php?id=' . $id);
            exit;
        }

        // Lanjutkan update jika aman
        $stmt = $koneksi->prepare("UPDATE kategori_produk SET nusp_id = ?, nama_kategori = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nusp_id, $nama_kategori, $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kategori berhasil diperbarui.";
            header('Location: kategori.php');
        } else {
            $_SESSION['error_message'] = "Gagal mengupdate kategori: " . $stmt->error;
            header('Location: form_edit_kategori.php?id=' . $id);
        }
        $stmt->close();
        break;

    case 'hapus':
        if (empty($_POST['id'])) {
            die("ID untuk dihapus tidak ditemukan.");
        }
        $id = (int)$_POST['id'];

        // Hapus kategori berdasarkan ID
        $stmt = $koneksi->prepare("DELETE FROM kategori_produk WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kategori berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus kategori: " . $stmt->error;
        }
        $stmt->close();
        header('Location: kategori.php');
        break;

    default:
        // Jika tidak ada aksi yang cocok, kembalikan ke halaman utama kategori
        header('Location: kategori.php');
        break;
}

exit;
?>