<?php
$page_title = 'Manajemen Pengguna';
$active_page = 'pengguna';
require_once 'template_header.php';

// Keamanan: Hanya admin yang boleh mengakses halaman ini
if ($_SESSION['role'] !== 'admin') {
    echo "<p class='content-section'>Akses ditolak. Halaman ini hanya untuk admin.</p>";
    require_once 'template_footer.php';
    exit;
}

// Inisialisasi variabel
$pesan_sukses = '';
$pesan_error = '';
$user_untuk_edit = null;
$mode_edit = false;

// =================================================================
// BAGIAN 1: PROSES PENGHAPUSAN PENGGUNA (DELETE)
// =================================================================
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int)$_GET['hapus_id'];
    // Keamanan: Jangan biarkan admin menghapus dirinya sendiri
    if ($id_hapus === $_SESSION['user_id']) {
        $pesan_error = "Anda tidak dapat menghapus akun Anda sendiri.";
    } else {
        $stmt_hapus = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt_hapus->bind_param("i", $id_hapus);
        if ($stmt_hapus->execute()) {
            $pesan_sukses = "Pengguna berhasil dihapus.";
        } else {
            $pesan_error = "Gagal menghapus pengguna.";
        }
        $stmt_hapus->close();
    }
}

// =================================================================
// BAGIAN 2: PROSES FORM (CREATE & UPDATE)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $id_edit = isset($_POST['id_edit']) ? (int)$_POST['id_edit'] : 0;

    // --- PROSES UPDATE ---
    if ($id_edit > 0) {
        // Validasi dasar untuk update
        if (empty($nama_lengkap) || empty($username) || empty($role)) {
            $pesan_error = "Nama, Username, dan Peran wajib diisi.";
        } else {
            // Jika password diisi, update password. Jika tidak, biarkan password lama.
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $pesan_error = "Password baru minimal harus 6 karakter.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update = $koneksi->prepare("UPDATE users SET nama_lengkap = ?, username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt_update->bind_param("ssssi", $nama_lengkap, $username, $hashed_password, $role, $id_edit);
                }
            } else {
                // Query tanpa update password
                $stmt_update = $koneksi->prepare("UPDATE users SET nama_lengkap = ?, username = ?, role = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $nama_lengkap, $username, $role, $id_edit);
            }

            if (empty($pesan_error) && $stmt_update->execute()) {
                $pesan_sukses = "Data pengguna berhasil diperbarui.";
            } elseif(empty($pesan_error)) {
                $pesan_error = "Gagal memperbarui data. Mungkin username sudah digunakan.";
            }
             if(isset($stmt_update)) $stmt_update->close();
        }
    } 
    // --- PROSES CREATE (TAMBAH BARU) ---
    else {
        // Validasi untuk tambah baru (password wajib)
        if (empty($nama_lengkap) || empty($username) || empty($password) || empty($role)) {
            $pesan_error = "Semua kolom wajib diisi.";
        } elseif (strlen($password) < 6) {
            $pesan_error = "Password minimal harus 6 karakter.";
        } else {
            // Cek duplikasi username
            $stmt_check = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $pesan_error = "Username sudah digunakan.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $koneksi->prepare("INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $nama_lengkap, $username, $hashed_password, $role);
                if ($stmt_insert->execute()) {
                    $pesan_sukses = "Pengguna baru berhasil ditambahkan.";
                } else {
                    $pesan_error = "Gagal menambahkan pengguna.";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

// =================================================================
// BAGIAN 3: PERSIAPAN MODE EDIT
// =================================================================
if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    $stmt_edit = $koneksi->prepare("SELECT nama_lengkap, username, role FROM users WHERE id = ?");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $user_untuk_edit = $result_edit->fetch_assoc();
        $mode_edit = true;
    }
    $stmt_edit->close();
}

// =================================================================
// BAGIAN 4: AMBIL SEMUA DATA PENGGUNA UNTUK DITAMPILKAN
// =================================================================
$result_users = $koneksi->query("SELECT id, nama_lengkap, username, role FROM users ORDER BY nama_lengkap ASC");

?>

<header class="main-header">
    <h1>Manajemen Pengguna</h1>
    <p>Tambah, edit, dan hapus pengguna yang terdaftar di sistem.</p>
</header>

<section class="content-section">
    
    <div class="form-container" style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h3><?= $mode_edit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h3>
        
        <?php if ($pesan_sukses): ?><div class="alert alert-success"><?= $pesan_sukses; ?></div><?php endif; ?>
        <?php if ($pesan_error): ?><div class="alert alert-danger"><?= $pesan_error; ?></div><?php endif; ?>

        <form action="pengguna.php" method="POST">
            <?php if ($mode_edit): ?>
                <input type="hidden" name="id_edit" value="<?= $id_edit; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap:</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user_untuk_edit['nama_lengkap'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user_untuk_edit['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" <?= !$mode_edit ? 'required' : ''; ?>>
                <?php if ($mode_edit): ?>
                    <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="role">Peran (Role):</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user" <?= (($user_untuk_edit['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User (Bisa meminta barang)</option>
                    <option value="admin" <?= (($user_untuk_edit['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin (Akses penuh)</option>
                </select>
            </div>
            <button type="submit" class="btn <?= $mode_edit ? 'btn-success' : 'btn-primary'; ?>">
                <?= $mode_edit ? 'Update Pengguna' : 'Tambah Pengguna'; ?>
            </button>
            <?php if ($mode_edit): ?>
                <a href="pengguna.php" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Daftar Pengguna</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_users->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center">Belum ada pengguna.</td></tr>
                <?php else: ?>
                    <?php $no = 1; while ($user = $result_users->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($user['nama_lengkap']); ?></td>
                            <td><?= htmlspecialchars($user['username']); ?></td>
                            <td class="text-center">
                                <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-primary'; ?>">
                                    <?= ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="pengguna.php?edit_id=<?= $user['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="pengguna.php?hapus_id=<?= $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'template_footer.php'; ?>