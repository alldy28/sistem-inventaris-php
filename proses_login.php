<?php
session_start();
require 'koneksi.php';

// Pastikan request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi input dasar
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Username dan Password tidak boleh kosong");
        exit;
    }

    // Gunakan prepared statement untuk mencegah SQL Injection
    $sql = "SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?";
    $stmt = $koneksi->prepare($sql);
    
    if ($stmt === false) {
        // Jika prepare gagal, mungkin ada error di SQL syntax
        die("Error pada prepare statement: " . $koneksi->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek apakah user ditemukan
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Password cocok, buat sesi
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            // Arahkan berdasarkan role
            header("Location: dashboard.php");
exit;
        } else {
            // Password salah
            header("Location: login.php?error=Username atau Password salah");
            exit;
        }
    } else {
        // User tidak ditemukan
        header("Location: login.php?error=Username atau Password salah");
        exit;
    }

    $stmt->close();
    $koneksi->close();
} else {
    // Jika bukan metode POST, redirect ke halaman login
    header("Location: login.php");
    exit;
}