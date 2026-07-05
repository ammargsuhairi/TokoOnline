<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produk = null;
$errors = [];

// Ambil data produk jika edit
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $produk = $stmt->fetch();
    if (!$produk) {
        header('Location: index.php');
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama'] ?? '');
    $harga      = (float)($_POST['harga'] ?? 0);
    $stok       = (int)($_POST['stok'] ?? 0);
    $cat_id     = (int)($_POST['category_id'] ?? 0);
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $gambar_lama = $produk['gambar'] ?? '';
    $gambar_baru = $gambar_lama;

    // Validasi
    if (!$nama) $errors[] = 'Nama produk wajib diisi.';
    if ($harga <= 0) $errors[] = 'Harga harus lebih dari 0.';

    // Upload gambar
    $upload_dir = dirname(__DIR__) . '/uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!empty($_FILES['gambar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, atau WEBP.';
        } elseif ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 2MB.';
        } else {
            $gambar_baru = uniqid('prod_') . '.' . $ext;
            $upload_result = move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar_baru);
            if (!$upload_result) {
                $errors[] = 'Gagal menyimpan gambar. Pastikan folder uploads/products/ dapat ditulis (writable).';
                $gambar_baru = $gambar_lama;
            } else {
                // Hapus gambar lama
                if ($gambar_lama && file_exists($upload_dir . $gambar_lama)) {
                    unlink($upload_dir . $gambar_lama);
                }
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE products SET category_id=?, nama=?, harga=?, stok=?, gambar=?, deskripsi=? WHERE id=?");
            $stmt->execute([$cat_id ?: null, $nama, $harga, $stok, $gambar_baru, $deskripsi, $id]);
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO products (category_id, nama, harga, stok, gambar, deskripsi) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$cat_id ?: null, $nama, $harga, $stok, $gambar_baru ?: null, $deskripsi]);
        }
        header('Location: index.php?msg=' . ($id ? 'updated' : 'added'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit' : 'Tambah' ?> Produk — TokoKu Admin</title>
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
        .form-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem; max-width: 580px; }
        .form-label { font-size: .75rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .form-control, .form-select { border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; padding: 9px 12px; }
        .form-control:focus, .form-select:focus { border-color: #888; box-shadow: none; }
        .btn-save { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: .875rem; }
        .btn-save:hover { background: #333; color: #fff; }
        .img-preview { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">⚙ TokoKu Admin</div>
    <nav>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="index.php" class="active"><i class="bi bi-box-seam"></i> Produk</a></li>
            <li class="nav-item"><a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a></li>
            <li class="nav-item"><a href="pesanan.php"><i class="bi bi-receipt"></i> Pesanan</a></li>
            <li class="nav-item"><a href="../index.php" target="_blank"><i class="bi bi-shop"></i> Lihat toko</a></li>
        </ul>
    </nav>
</div>

<div class="main">
    <div class="mb-4">
        <a href="index.php" class="text-muted text-decoration-none" style="font-size:.85rem">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <h1 style="font-size:1.2rem;font-weight:700;letter-spacing:-.5px;margin-top:.5rem">
            <?= $id ? 'Edit produk' : 'Tambah produk' ?>
        </h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;border-radius:8px;max-width:580px">
            <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nama produk *</label>
                <input type="text" name="nama" class="form-control" 
                       value="<?= htmlspecialchars($_POST['nama'] ?? $produk['nama'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Harga (Rp) *</label>
                    <input type="number" name="harga" class="form-control" min="0" step="500"
                           value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga'] ?? '') ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Stok</label>
                    <input type="number" name="stok" class="form-control" min="0"
                           value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok'] ?? 0) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">— Pilih kategori —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" 
                            <?= (($_POST['category_id'] ?? $produk['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $produk['deskripsi'] ?? '') ?></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label">Gambar produk <span class="text-muted" style="font-size:.7rem;text-transform:none">(JPG/PNG/WEBP, maks 2MB)</span></label>
                <?php if (!empty($produk['gambar']) && file_exists('../uploads/products/' . $produk['gambar'])): ?>
                    <div class="mb-2">
                        <img src="../uploads/products/<?= htmlspecialchars($produk['gambar']) ?>" class="img-preview" alt="">
                        <div style="font-size:.75rem;color:#999;margin-top:4px">Gambar saat ini</div>
                    </div>
                <?php endif; ?>
                <input type="file" name="gambar" class="form-control" accept="image/*" id="gambarInput">
                <img id="imgPreview" class="img-preview mt-2" style="display:none" alt="Preview">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-save">
                    <i class="bi bi-check-lg"></i> <?= $id ? 'Simpan perubahan' : 'Tambah produk' ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary" style="border-radius:8px;font-size:.875rem">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('gambarInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('imgPreview');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>