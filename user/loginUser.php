<?php
require_once '../includes/session.php';
include '../includes/db.php';
include '../includes/functions.php';

// ---- Tentukan tujuan redirect setelah login (default: indexUser.php) ----
// Hanya izinkan path relatif di dalam folder toko_online untuk mencegah open redirect.
function resolveRedirect($raw) {
    $raw = $raw ?? '';
    if ($raw === '' || preg_match('#^(https?:)?//#i', $raw) || str_starts_with($raw, '\\\\')) {
        return 'indexUser.php';
    }
    return $raw;
}
$redirect = resolveRedirect($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if (isLoggedIn()) {
    header('Location: ' . ($redirect !== 'indexUser.php' ? '../' . $redirect : 'indexUser.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $login = $pdo->prepare("SELECT * FROM user WHERE username = ?");
        $login->execute([$username]);
        $user = $login->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // PENTING: kolom primary key tabel `user` adalah `id_user`, bukan `id`.
            $_SESSION['user_id']       = $user['id_user'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_nama']     = $user['nama'];
            header('Location: ' . ($redirect !== 'indexUser.php' ? '../' . $redirect : 'indexUser.php'));
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #fff; border: 1px solid #e0e0da; border-radius: 16px; padding: 2.5rem 2rem; width: 100%; max-width: 380px; }
        .login-card h2 { font-size: 1.3rem; font-weight: 700; letter-spacing: -.5px; margin-bottom: .25rem; }
        .form-control { border: 1px solid #e0e0da; border-radius: 8px; font-size: .9rem; padding: 10px 14px; }
        .form-control:focus { border-color: #1a1a1a; box-shadow: none; }
        .form-label { font-size: .8rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .btn-login { background: #1a1a1a; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: .9rem; width: 100%; }
        .btn-login:hover { background: #333; color: #fff; }
    </style>
</head>
<body>
<div class="login-card">
    <p style="font-size:2rem;margin-bottom:.5rem">🔐</p>
    <h2>Login Pelanggan</h2>
    <p class="text-muted mb-4" style="font-size:.85rem">Selamat Datang di TokoKu</p>

    <?php if ($redirect !== 'indexUser.php'): ?>
        <div class="alert alert-warning py-2 px-3" style="font-size:.85rem;border-radius:8px">
            Silakan login terlebih dahulu untuk membuka halaman katalog.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3" style="font-size:.85rem;border-radius:8px"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-login">Masuk →</button>
    </form>
    
     <p class="text-center mt-3" style="font-size:.8rem">
        <a href="daftar.php" class="text-muted text-decoration-none">Daftar</a>
    </p>
    <p class="text-center mt-3" style="font-size:.8rem">
        <a href="../index.php" class="text-muted text-decoration-none">← Kembali ke toko</a>
    </p>
</div>
</body>
</html>
