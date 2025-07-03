<?php
$page_title = 'Selesaikan Perbaikan';
$active_page = 'kerusakan'; // Sesuaikan jika perlu
require_once 'template_header.php';

function getRepairDetailsForCompletion(mysqli $koneksi, int $id_perbaikan): ?array {
    // Memastikan hanya mengambil laporan yang belum final (statusnya bukan Selesai atau Ditolak)
    $stmt = $koneksi->prepare("SELECT * FROM perbaikan_aset WHERE id = ? AND status_perbaikan NOT IN ('Selesai', 'Ditolak')");
    $stmt->bind_param("i", $id_perbaikan);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan = $result->fetch_assoc();
    $stmt->close();
    return $laporan;
}

// Keamanan & Pengambilan Data
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='content-section'>Akses ditolak atau ID tidak valid.</p>";
    require_once 'template_footer.php';
    exit;
}
$id_perbaikan = (int)$_GET['id'];
$laporan = getRepairDetailsForCompletion($koneksi, $id_perbaikan);

if (!$laporan) {
    echo "<p class='content-section'>Laporan perbaikan tidak ditemukan atau sudah selesai diproses.</p>";
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
        <p><strong>Kerusakan Dilaporkan:</strong> <?php echo htmlspecialchars($laporan['jenis_kerusakan']); ?></p>
    </div>

    <div class="card form-container" style="margin-top:20px;">
        <form action="proses_penyelesaian.php" method="POST">
            <input type="hidden" name="id_perbaikan" value="<?php echo $laporan['id']; ?>">
            
            <h2>Pencatatan Komponen / Jasa Perbaikan</h2>
            
            <div id="komponen-container">
                <div class="komponen-item">
                    <div class="form-group">
                        <label>Nama Komponen / Jasa</label>
                        <input type="text" name="komponen[nama][]" class="form-control" placeholder="Contoh: Power Supply FSP 550W" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="komponen[jumlah][]" class="form-control" required min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="komponen[harga][]" class="form-control" required min="0" step="any" placeholder="Contoh: 550000">
                    </div>
                    <button type="button" class="btn btn-delete btn-sm hapus-komponen" style="display:none;">Hapus</button>
                </div>
            </div>

            <button type="button" id="tambah-komponen" class="btn btn-secondary" style="margin-top: 10px;">+ Tambah Komponen Lain</button>
            
            <hr style="margin: 30px 0;">

            <div class="form-group">
                <label for="catatan_admin">Catatan Final Admin (Tindakan yang dilakukan, dll)</label>
                <textarea name="catatan_admin" id="catatan_admin" rows="4" class="form-control" required><?php echo htmlspecialchars($laporan['catatan_admin']); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="selesaikan" class="btn btn-success">Selesaikan & Simpan Perbaikan</button>
                <a href="detail_kerusakan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('komponen-container');
    const addButton = document.getElementById('tambah-komponen');

    // Template untuk baris baru
    const template = container.querySelector('.komponen-item').cloneNode(true);
    // Bersihkan value pada template
    template.querySelector('input[name="komponen[nama][]"]').value = '';
    template.querySelector('input[name="komponen[jumlah][]"]').value = '1';
    template.querySelector('input[name="komponen[harga][]"]').value = '';
    template.querySelector('.hapus-komponen').style.display = 'inline-block';

    addButton.addEventListener('click', function() {
        container.appendChild(template.cloneNode(true));
    });

    // Event listener untuk tombol hapus
    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('hapus-komponen')) {
            e.target.closest('.komponen-item').remove();
        }
    });
});
</script>

<?php require_once 'template_footer.php'; ?>