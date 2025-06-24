<?php
// Mulai atau lanjutkan sesi yang ada
session_start();

// Cek apakah pengguna sudah login atau belum
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Jika SUDAH login, langsung arahkan ke halaman dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Jika BELUM login, arahkan ke halaman login
    header('Location: login.php');
    exit;
}

?>