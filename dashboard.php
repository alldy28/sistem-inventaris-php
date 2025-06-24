<?php
// Definisikan variabel untuk template
$page_title = 'Dashboard';
$active_page = 'dashboard';

// Panggil template header
require_once 'template_header.php';
?>

<header class="main-header">
    <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
    <p>Anda telah berhasil login sebagai <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>. Silakan gunakan menu navigasi di samping untuk mengelola aplikasi.</p>
</header>

<section class="content-section">
    <p>Ini adalah halaman utama dashboard Anda. Anda bisa menambahkan ringkasan atau widget di sini nanti.</p>
</section>
<?php
// Panggil template footer
require_once 'template_footer.php';
?>