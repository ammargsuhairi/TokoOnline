<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$errors = [];
$edit = null;

// Hapus kategori
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Cek apakah kategori masih dipakai produk
    $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $errors[] = 'Kategori ini masih dipakai oleh produk. Ubah kategori produk terlebih dahulu.';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        header('Location: kategori.php?msg=deleted');
        exit;
    }
}

// Ambil data edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);

    if (!$nama) {
        $errors[] = 'Nama kategori wajib diisi.';
    } else {
        if ($id) {
            $pdo->prepare("UPDATE categories SET nama = ? WHERE id = ?")->execute([$nama, $id]);
        } else {
            $pdo->prepare("INSERT INTO categories (nama) VALUES (?)")->execute([$nama]);
        }
        header('Location: kategori.php?msg=saved');
        exit;
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS jumlah_produk 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id 
    GROUP BY c.id 
    ORDER BY c.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori — TokoKu Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --soft: #f5f5f0; --border: #e0e0da; }
        body { background: var(--soft); font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 220px; min-height: 100vh; background: #fff; border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; padding: 1.5rem 1rem; }
        .sidebar-logo { font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
        .nav-item a { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 8px; color: #666; font-size: .875rem; text-decoration: none; margin-bottom: 2px; }
        .nav-item a:hover, .nav-item a.active { background: var(--soft); color: var(--primary); font-weight: 600; }
        .main { margin-left: 220px; padding: 2rem; }
        .card-box { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .form-label { font-size: .75rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .form-control { border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; padding: 9px 12px; }
        .form-control:focus { border-color: #888; box-shadow: none; }
        .btn-save { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 9px 20px; font-size: .875rem; }
        .btn-save:hover { background: #333; color: #fff; }
        .table th { font-size: .75rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border) !important; }
        .table td { font-size: .875rem; vertical-align: middle; border-color: var(--border); }
        .btn-icon { border: 1px solid var(--border); background: #fff; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; color: #555; text-decoration: none; }
        .btn-icon:hover { background: var(--soft); color: var(--primary); }
        .btn-icon.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
        .alert-success-sm { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: 8px; padding: 8px 14px; font-size: .85rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">⚙ TokoKu Admin</div>
    <nav>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="index.php"><i class="bi bi-box-seam"></i> Produk</a></li>
            <li class="nav-item"><a href="kategori.php" class="active"><i class="bi bi-tags"></i> Kategori</a></li>
            <li class="nav-item"><a href="pesanan.php"><i class="bi bi-receipt"></i> Pesanan</a></li>
        </ul>
    </nav>
</div>

<div class="main">
    <h1 style="font-size:1.2rem;font-weight:700;letter-spacing:-.5px;margin-bottom:1.5rem">Kategori</h1>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert-success-sm mb-3">
            <?= $_GET['msg'] === 'deleted' ? '🗑 Kategori dihapus.' : '✅ Kategori berhasil disimpan!' ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;border-radius:8px">
            <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- FORM -->
        <div class="col-md-4">
            <div class="card-box">
                <h6 style="font-weight:700;font-size:.9rem;margin-bottom:1rem">
                    <?= $edit ? 'Edit kategori' : 'Tambah kategori' ?>
                </h6>
                <form method="POST">
                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Nama kategori *</label>
                        <input type="text" name="nama" class="form-control"
                            value="<?= htmlspecialchars($_POST['nama'] ?? $edit['nama'] ?? '') ?>"
                            placeholder="cth: Elektronik" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-check-lg"></i> Simpan
                        </button>
                        <?php if ($edit): ?>
                            <a href="kategori.php" class="btn btn-outline-secondary" style="border-radius:8px;font-size:.875rem">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL -->
        <div class="col-md-8">
            <div class="card-box p-0">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="padding:12px 16px">#</th>
                            <th>Nama kategori</th>
                            <th>Jumlah produk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($categories as $i => $cat): ?>
                        <tr>
                            <td style="padding:10px 16px;color:#999"><?= $i + 1 ?></td>
                            <td style="font-weight:500"><?= htmlspecialchars($cat['nama']) ?></td>
                            <td><span style="font-size:.8rem;color:#666"><?= $cat['jumlah_produk'] ?> produk</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="kategori.php?edit=<?= $cat['id'] ?>" class="btn-icon" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="kategori.php?delete=<?= $cat['id'] ?>" class="btn-icon danger" title="Hapus"
                                        onclick="return confirm('Yakin hapus kategori ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
