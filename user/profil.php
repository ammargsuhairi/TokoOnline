<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: loginUser.php');
    exit;
}

$stmtUser = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

if (!$currentUser) {
    session_destroy();
    header('Location: loginUser.php');
    exit;
}

$errors = [];
$success = '';
$tab = $_GET['tab'] ?? 'info';
if (!in_array($tab, ['info', 'password'], true)) {
    $tab = 'info';
}

// ==== UPDATE INFORMASI AKUN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $tab = 'info';
    $nama     = trim($_POST['nama'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!$nama || !$alamat || !$username) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (strlen($nama) > 25) {
        $errors[] = 'Nama maksimal 25 karakter.';
    } elseif (strlen($alamat) > 50) {
        $errors[] = 'Alamat maksimal 50 karakter.';
    } elseif (strlen($username) > 25) {
        $errors[] = 'Username maksimal 25 karakter.';
    } else {
        // Cek username dipakai user lain
        $cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $cek->execute([$username, $currentUser['id_user']]);
        if ($cek->fetch()) {
            $errors[] = 'Username sudah digunakan oleh akun lain.';
        } else {
            $stmt = $pdo->prepare("UPDATE user SET nama = ?, alamat = ?, username = ? WHERE id_user = ?");
            $stmt->execute([$nama, $alamat, $username, $currentUser['id_user']]);

            // Refresh session & data lokal biar langsung sinkron tanpa perlu login ulang
            $_SESSION['user_nama']     = $nama;
            $_SESSION['user_username'] = $username;
            $currentUser['nama']       = $nama;
            $currentUser['alamat']     = $alamat;
            $currentUser['username']   = $username;

            $success = 'Informasi akun berhasil diperbarui.';
        }
    }
}

// ==== UBAH PASSWORD ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $tab = 'password';
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (!password_verify($current_password, $currentUser['password'])) {
        $errors[] = 'Password saat ini salah.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    } elseif (password_verify($new_password, $currentUser['password'])) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET password = ? WHERE id_user = ?")->execute([$hash, $currentUser['id_user']]);
        $success = 'Password berhasil diubah.';
    }
}

$namaUser = $currentUser['nama'];

// ==== STATISTIK PESANAN ====
$stmtStat = $pdo->prepare("SELECT COUNT(*) AS total_pesanan,
                                   COALESCE(SUM(CASE WHEN status != 'Dibatalkan' THEN total_bayar ELSE 0 END), 0) AS total_belanja
                            FROM orders WHERE id_user = ?");
$stmtStat->execute([$currentUser['id_user']]);
$stat = $stmtStat->fetch();

$stmtSelesai = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE id_user = ? AND status = 'Selesai'");
$stmtSelesai->execute([$currentUser['id_user']]);
$totalSelesai = $stmtSelesai->fetchColumn();

// Inisial untuk avatar
$parts = preg_split('/\s+/', trim($namaUser));
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : substr($parts[0], 1, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --accent: #e8ff00; --soft: #f5f5f0; --border: #e0e0da; }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }

        .page-header { padding: 2rem 0 0; }
        .page-header h1 { font-size: 1.6rem; font-weight: 800; letter-spacing: -1px; }

        /* PROFILE HEADER CARD */
        .profile-banner { background: var(--soft); border-radius: 16px; padding: 2rem; margin: 1.5rem 0; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .avatar-circle { width: 76px; height: 76px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 700; letter-spacing: -1px; flex-shrink: 0; }
        .profile-name { font-size: 1.3rem; font-weight: 800; letter-spacing: -.5px; margin-bottom: 2px; }
        .profile-username { color: #777; font-size: .85rem; }
        .profile-stats { display: flex; gap: 1.75rem; margin-left: auto; flex-wrap: wrap; }
        .profile-stat-val { font-size: 1.3rem; font-weight: 800; letter-spacing: -.5px; }
        .profile-stat-label { font-size: .72rem; color: #999; text-transform: uppercase; letter-spacing: .5px; }

        /* LAYOUT */
        .settings-nav { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .settings-nav a { display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-size: .875rem; color: #555; text-decoration: none; border-bottom: 1px solid var(--border); }
        .settings-nav a:last-child { border-bottom: none; }
        .settings-nav a i { width: 18px; font-size: 1rem; }
        .settings-nav a:hover { background: var(--soft); color: var(--primary); }
        .settings-nav a.active { background: var(--primary); color: #fff; font-weight: 600; }
        .settings-nav a.text-danger:hover { background: #fef2f2; color: #dc2626 !important; }

        .card-box { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem; }
        .card-box h5 { font-size: 1.05rem; font-weight: 700; letter-spacing: -.3px; margin-bottom: 4px; }
        .card-box .subtitle { color: #888; font-size: .82rem; margin-bottom: 1.5rem; }

        .form-label { font-size: .78rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .form-control { border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; padding: 10px 14px; }
        .form-control:focus { border-color: #1a1a1a; box-shadow: none; }
        .form-control[readonly] { background: var(--soft); color: #888; }
        .btn-save { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 9px 22px; font-size: .875rem; }
        .btn-save:hover { background: #333; color: #fff; }

        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<?php $base = '../'; $activeNav = 'profil'; include '../includes/navbar.php'; ?>

<div class="container" style="max-width:960px">
    <div class="page-header">
        <h1>Profil Saya</h1>
        <p class="text-muted mb-0" style="font-size:.9rem">Kelola informasi akun dan keamanan akunmu di sini.</p>
    </div>

    <!-- PROFILE BANNER -->
    <div class="profile-banner">
        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
        <div>
            <div class="profile-name"><?= htmlspecialchars($namaUser) ?></div>
            <div class="profile-username"><i class="bi bi-at"></i><?= htmlspecialchars($currentUser['username']) ?></div>
        </div>
        <div class="profile-stats">
            <div>
                <div class="profile-stat-val"><?= (int) $stat['total_pesanan'] ?></div>
                <div class="profile-stat-label">Total Pesanan</div>
            </div>
            <div>
                <div class="profile-stat-val"><?= (int) $totalSelesai ?></div>
                <div class="profile-stat-label">Selesai</div>
            </div>
            <div>
                <div class="profile-stat-val" style="font-size:1.05rem"><?= formatRupiah($stat['total_belanja']) ?></div>
                <div class="profile-stat-label">Total Belanja</div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.85rem;border-radius:8px">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;border-radius:8px">
            <?php foreach ($errors as $e): ?>
                <div>⚠️ <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-5">
        <!-- SIDEBAR MENU -->
        <div class="col-md-3">
            <div class="settings-nav">
                <a href="?tab=info" class="<?= $tab === 'info' ? 'active' : '' ?>">
                    <i class="bi bi-person-lines-fill"></i> Informasi Akun
                </a>
                <a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock"></i> Ubah Password
                </a>
                <a href="riwayat_pesanan.php">
                    <i class="bi bi-clock-history"></i> Riwayat Pesanan
                </a>
                <a href="logoutUser.php" class="text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="col-md-9">
            <?php if ($tab === 'info'): ?>
                <div class="card-box">
                    <h5>Informasi Akun</h5>
                    <p class="subtitle">Perbarui nama, alamat, dan username akunmu.</p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" maxlength="25" value="<?= htmlspecialchars($currentUser['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" maxlength="50" required><?= htmlspecialchars($currentUser['alamat']) ?></textarea>
                            <div class="form-text" style="font-size:.75rem">Maksimal 50 karakter. Alamat ini akan otomatis muncul saat checkout.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" maxlength="25" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                            <div class="form-text" style="font-size:.75rem">Digunakan untuk login. Harus unik.</div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Simpan Perubahan</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="card-box">
                    <h5>Ubah Password</h5>
                    <p class="subtitle">Gunakan password yang kuat dan tidak dipakai di tempat lain.</p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn-save">Ubah Password</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
