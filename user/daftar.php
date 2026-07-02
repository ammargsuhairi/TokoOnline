<?php
require_once '../includes/session.php';
include '../includes/db.php';
include '../includes/functions.php';

if (isLoggedIn()) {
    header('Location: indexUser.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = trim($_POST['username'] ?? '');
    $nama           = trim($_POST['nama'] ?? '');
    $alamat         = trim($_POST['alamat'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm        = $_POST['confirm_password'] ?? '';

    if (!$username || !$nama || !$alamat || !$password || !$confirm) {
        $error = 'Harap isi semua field.';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetch()) {
            $error = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (username, nama, alamat, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $nama, $alamat, $hash]);
            $success = 'Akun berhasil dibuat. Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 0; }
        .login-card { background: #fff; border: 1px solid #e0e0da; border-radius: 16px; padding: 2.5rem 2rem; width: 100%; max-width: 420px; }
        .login-card h2 { font-size: 1.3rem; font-weight: 700; letter-spacing: -.5px; margin-bottom: .25rem; }
        .form-control, .form-select { border: 1px solid #e0e0da; border-radius: 8px; font-size: .9rem; padding: 10px 14px; }
        .form-control:focus, .form-select:focus { border-color: #1a1a1a; box-shadow: none; }
        .form-label { font-size: .8rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .btn-login { background: #1a1a1a; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: .9rem; width: 100%; }
        .btn-login:hover { background: #333; color: #fff; }
        .radio-group { display: flex; gap: 12px; }
        .radio-option { flex: 1; border: 1px solid #e0e0da; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-size: .9rem; display: flex; align-items: center; gap: 8px; transition: border-color .15s, background .15s; }
        .radio-option input[type=radio] { accent-color: #1a1a1a; }
        .radio-option:has(input:checked) { border-color: #1a1a1a; background: #f5f5f0; font-weight: 600; }
    </style>
</head>
<body>
<div class="login-card">
    <p style="font-size:2rem;margin-bottom:.5rem">📝</p>
    <h2>Buat Akun</h2>
    <p class="text-muted mb-4" style="font-size:.85rem">TokoKu — Daftar Pelanggan</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3" style="font-size:.85rem;border-radius:8px"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2 px-3" style="font-size:.85rem;border-radius:8px">
            <?= htmlspecialchars($success) ?> <a href="loginUser.php">Login sekarang →</a>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" placeholder="Masukkan alamat lengkap" rows="3" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
        </div>
        <button type="submit" class="btn-login">Daftar →</button>
    </form>
    
    <p class="text-center mt-3" style="font-size:.8rem">
        Sudah punya akun? <a href="loginUser.php" class="text-muted">Login</a>
    </p>
    <p class="text-center mt-1" style="font-size:.8rem">
        <a href="../index.php" class="text-muted text-decoration-none">← Kembali ke toko</a>
    </p>
</div>
</body>
</html>
