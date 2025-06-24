<?php
require 'koneksi.php';

// --- Data untuk Admin ---
$username_admin = 'admin';
$password_admin_plain = 'admin123'; // Password asli
$nama_admin = 'Administrator Utama';
$role_admin = 'admin';

// Hash password admin
$password_admin_hashed = password_hash($password_admin_plain, PASSWORD_DEFAULT);

// --- Data untuk User ---
$username_user = 'budi';
$password_user_plain = 'user123'; // Password asli
$nama_user = 'Budi Santoso';
$role_user = 'user';

// Hash password user
$password_user_hashed = password_hash($password_user_plain, PASSWORD_DEFAULT);

// Query untuk memasukkan data (menggunakan prepared statement)
$sql = "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?), (?, ?, ?, ?)";
$stmt = $koneksi->prepare($sql);

if ($stmt === false) {
    die("Gagal mempersiapkan statement: " . $koneksi->error);
}

// Bind parameter ke statement
$stmt->bind_param(
    "ssssssss",
    $username_admin, $password_admin_hashed, $nama_admin, $role_admin,
    $username_user, $password_user_hashed, $nama_user, $role_user
);

// Eksekusi statement
if ($stmt->execute()) {
    echo "User admin dan user biasa berhasil dibuat!<br>";
    echo "=====================================<br>";
    echo "Admin -> Username: admin, Password: admin123<br>";
    echo "User  -> Username: budi, Password: user123<br>";
} else {
    echo "Error: " . $stmt->error;
}

// Tutup statement dan koneksi
$stmt->close();
$koneksi->close();