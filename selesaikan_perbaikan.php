<?php
$page_title = 'Selesaikan Perbaikan';
$active_page = 'kerusakan';
require_once 'template_header.php';

// Keamanan dasar
if ($_SESSION['role'] !== 'admin' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Akses ditolak atau ID tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}

$id_perbaikan = $_GET['id'];
$stmt = $koneksi->prepare("SELECT * FROM perbaikan_aset WHERE id = ?");
$stmt->bind_param("i", $id_perbaikan);
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();

if (!$laporan) {
    echo "<p>Laporan perbaikan tidak ditemukan.</p>";
    require_once 'template_footer.php';
    exit;
}
?>

<header class="main-header">
    <h1>Finalisasi Perbaikan Aset #<?php echo $laporan['id']; ?></h1>
    <p>Catat komponen atau jasa yang digunakan untuk menyelesaikan perbaikan ini.</p>
</header>

<section class="content-section">
    <div class="card">
        <p><strong>Aset yang Diperbaiki:</strong> <?php echo htmlspecialchars($laporan['nama_aset']); ?></p>
        <p><strong>Kerusakan Dilaporkan:</strong> <?php echo htmlspecialchars($laporan['komponen_rusak']); ?></p>
    </div>
    <div class="card form-container" style="margin-top:20px;">
        <h2>Pencatatan Komponen / Jasa Perbaikan</h2>
        <p>Isi data di bawah ini. Jika ada lebih dari satu komponen, Anda bisa menambahkannya nanti melalui halaman edit.</p>
        <form action="proses_penyelesaian.php" method="POST">
            <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
            
            <div class="form-group">
                <label for="nama_komponen">Nama Komponen / Jasa</label>
                <input type="text" name="nama_komponen" id="nama_komponen" class="form-control" placeholder="Contoh: Power Supply FSP 550W, Jasa Servis" required>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" required min="1" value="1">
            </div>
            <div class="form-group">
                <label for="harga_satuan">Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" required min="0" step="any">
            </div>
            <div class="form-group">
                <label for="catatan_admin">Catatan Final Admin (Tindakan yang dilakukan, dll)</label>
                <textarea name="catatan_admin" id="catatan_admin" rows="4" class="form-control"><?php echo htmlspecialchars($laporan['catatan_admin']); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="selesaikan" class="btn btn-success">Selesaikan Perbaikan</button>
                <a href="detail_kerusakan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>