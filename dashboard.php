<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';
require_once 'template_header.php';
require_once 'data_dashboard.php';

// Menentukan nilai default dan mengambil data dari filter
$tahun_chart_bar = isset($_GET['tahun_bar']) ? (int)$_GET['tahun_bar'] : date('Y');
$bulan_chart_pie = isset($_GET['bulan_pie']) ? (int)$_GET['bulan_pie'] : date('n');
$tahun_chart_pie = isset($_GET['tahun_pie']) ? (int)$_GET['tahun_pie'] : date('Y');

// Ambil data untuk grafik berdasarkan filter
$data_chart_bar = getDataPenerimaanPengeluaranTahunan($koneksi, $tahun_chart_bar);
$data_chart_pie = getDataPengeluaranPerKategoriBulanan($koneksi, $bulan_chart_pie, $tahun_chart_pie);

// Variabel helper untuk nama bulan dan pengecekan data
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$ada_data_pie = !empty($data_chart_pie['labels']);
?>

<style>
.chart-container { position: relative; height: 40vh; width: 100%; margin-bottom: 30px; }
.filter-form-container { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.no-data-info { display: flex; align-items: center; justify-content: center; height: 100%; color: #888; }
</style>

<header class="main-header">
    <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
    <p>Anda login sebagai <strong><?= htmlspecialchars($_SESSION['role']); ?></strong>. Ini adalah ringkasan aktivitas inventaris Anda.</p>
</header>

<section class="content-section">
    <?php if (isset($jumlah_permintaan_masuk) && $jumlah_permintaan_masuk > 0 && $_SESSION['role'] === 'admin'): ?>
    <div class="alert alert-info">
        <strong><i class="fas fa-bell"></i> Notifikasi Penting!</strong><br>
        Ada <strong><?= $jumlah_permintaan_masuk; ?></strong> permintaan barang baru yang menunggu persetujuan Anda. 
        <a href="daftar_permintaan.php">Klik di sini untuk melihatnya.</a>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Nilai Penerimaan vs Pengeluaran (Tahun <?= $tahun_chart_bar; ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="filter-form-container">
                        <form action="" method="GET" class="form-inline">
                            <div class="form-group"><label for="tahun_bar">Pilih Tahun:</label>
                                <select name="tahun_bar" id="tahun_bar" class="form-control">
                                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                    <option value="<?= $i; ?>" <?= ($i == $tahun_chart_bar) ? 'selected' : ''; ?>><?= $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartPenerimaanPengeluaran"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
             <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Komposisi Barang Keluar (<?= $nama_bulan[$bulan_chart_pie] . ' ' . $tahun_chart_pie; ?>)</h3>
                </div>
                <div class="card-body">
                     <div class="filter-form-container">
                        <form action="" method="GET" class="form-inline">
                            <div class="form-group"><label for="bulan_pie">Bulan:</label>
                                <select name="bulan_pie" id="bulan_pie" class="form-control">
                                    <?php foreach ($nama_bulan as $nomor => $nama): ?>
                                    <option value="<?= $nomor; ?>" <?= ($nomor == $bulan_chart_pie) ? 'selected' : ''; ?>><?= $nama; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="tahun_pie">Tahun:</label>
                                <select name="tahun_pie" id="tahun_pie" class="form-control">
                                     <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                    <option value="<?= $i; ?>" <?= ($i == $tahun_chart_pie) ? 'selected' : ''; ?>><?= $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                    <div class="chart-container">
                        <?php if ($ada_data_pie): ?>
                            <canvas id="chartPengeluaranKategori"></canvas>
                        <?php else: ?>
                            <div class="no-data-info">
                                <p>Tidak ada data pengeluaran untuk periode ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // BAR CHART
    const dataBar = {
        labels: <?= json_encode($data_chart_bar['labels']); ?>,
        datasets: [{
            label: 'Total Nilai Penerimaan (Rp)', data: <?= json_encode($data_chart_bar['penerimaan']); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1
        }, {
            label: 'Total Nilai Pengeluaran (Rp)', data: <?= json_encode($data_chart_bar['pengeluaran']); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.5)', borderColor: 'rgba(255, 99, 132, 1)', borderWidth: 1
        }]
    };
    const configBar = {
        type: 'bar', data: dataBar, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); }}}}}
    };
    new Chart(document.getElementById('chartPenerimaanPengeluaran'), configBar);

    // PIE CHART (Hanya render jika ada data)
    <?php if ($ada_data_pie): ?>
        const dataPie = {
            labels: <?= json_encode($data_chart_pie['labels']); ?>,
            datasets: [{
                label: 'Jumlah Barang Keluar', data: <?= json_encode($data_chart_pie['jumlah']); ?>,
                backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'],
                hoverOffset: 4
            }]
        };
        const configPie = { type: 'pie', data: dataPie, options: { responsive: true, maintainAspectRatio: false }};
        new Chart(document.getElementById('chartPengeluaranKategori'), configPie);
    <?php endif; ?>
});
</script>

<?php require_once 'template_footer.php'; ?>