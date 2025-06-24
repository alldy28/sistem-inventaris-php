<?php
// Konfigurasi Database
$host = 'localhost';    // atau sesuaikan dengan host database Anda
$user = 'root';         // atau sesuaikan dengan username database Anda
$pass = '';             // atau sesuaikan dengan password database Anda
$db   = 'db_auth';      // nama database yang sudah dibuat

// Membuat koneksi
$koneksi = new mysqli($host, $user, $pass, $db);

// Memeriksa koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
}

// Mengatur charset ke utf8mb4 untuk dukungan karakter yang lebih baik
$koneksi->set_charset("utf8mb4");