<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

// Hapus produk
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT gambar FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        header('Location: index.php?msg=delete_notfound');
        exit;
    }

    try {
        // Hapus baris produk DULU. Kalau ini gagal (mis. produk masih
        // direferensikan oleh order_items via foreign key), gambar TIDAK
        // ikut terhapus supaya data tidak jadi "setengah kehapus".
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

        // Baru setelah baris DB benar-benar terhapus, hapus file gambarnya.
        if ($prod['gambar'] && file_exists('../uploads/products/' . $prod['gambar'])) {
            unlink('../uploads/products/' . $prod['gambar']);
        }

        header('Location: index.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        // Kemungkinan besar: produk ini sudah pernah masuk pesanan (order_items),
        // sehingga tidak boleh dihapus langsung agar riwayat pesanan tetap utuh.
        header('Location: index.php?msg=delete_failed');
        exit;
    }
}

// Ambil produk + kategori
$search = $_GET['q'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$sql = "SELECT p.*, c.nama AS kategori FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND p.nama LIKE ?";
    $params[] = "%$search%";
}
if ($cat_filter) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_filter;
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$total_produk = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$stok_tipis = $pdo->query("SELECT COUNT(*) FROM products WHERE stok < 10")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --soft: #f5f5f0; --border: #e0e0da; }
        body { background: var(--soft); font-family: 'Segoe UI', sans-serif; }
        
        /* SIDEBAR */
        .sidebar { width: 220px; min-height: 100vh; background: #fff; border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; padding: 1.5rem 1rem; display: flex; flex-direction: column; }
        .sidebar-logo { font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
        .nav-item a { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 8px; color: #666; font-size: .875rem; text-decoration: none; margin-bottom: 2px; }
        .nav-item a:hover, .nav-item a.active { background: var(--soft); color: var(--primary); font-weight: 600; }
        .nav-item a i { font-size: 1rem; }
        .sidebar-footer { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border); }

        /* MAIN */
        .main { margin-left: 220px; padding: 2rem; }
        .page-title { font-size: 1.2rem; font-weight: 700; letter-spacing: -.5px; margin-bottom: .25rem; }

        /* STAT CARDS */
        .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.25rem; }
        .stat-label { font-size: .75rem; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
        .stat-val { font-size: 1.75rem; font-weight: 800; letter-spacing: -1px; }

        /* TABLE CARD */
        .table-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-card-header { padding: .875rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .table th { font-size: .75rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border) !important; }
        .table td { font-size: .875rem; vertical-align: middle; border-color: var(--border); }
        .table tbody tr:hover { background: var(--soft); }
        .product-thumb-sm { width: 44px; height: 44px; background: var(--soft); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-thumb-sm img { width: 100%; height: 100%; object-fit: cover; }
        .badge-cat-sm { font-size: .7rem; background: var(--soft); color: #666; border: 1px solid var(--border); padding: 2px 8px; border-radius: 99px; }
        .stock-ok { color: #16a34a; font-weight: 600; }
        .stock-low { color: #d97706; font-weight: 600; }

        /* BUTTONS */
        .btn-primary-sm { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-size: .8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary-sm:hover { background: #333; color: #fff; }
        .btn-icon { border: 1px solid var(--border); background: #fff; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; color: #555; text-decoration: none; }
        .btn-icon:hover { background: var(--soft); color: var(--primary); }
        .btn-icon.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
        .search-input { border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: .85rem; width: 200px; }
        .search-input:focus { outline: none; border-color: #888; }
        .alert-success-sm { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: 8px; padding: 8px 14px; font-size: .85rem; }
        .alert-danger-sm { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 8px; padding: 8px 14px; font-size: .85rem; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="page-title">Dashboard</h1>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-box-seam"></i> Total produk</div>
                <div class="stat-val"><?= $total_produk ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-tags"></i> Kategori</div>
                <div class="stat-val"><?= $total_kategori ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-exclamation-triangle"></i> Stok menipis</div>
                <div class="stat-val" style="color:#d97706"><?= $stok_tipis ?></div>
            </div>
        </div>
    </div>

    <!-- NOTIF -->
    <?php if (isset($_GET['msg'])): ?>
        <?php $isError = in_array($_GET['msg'], ['delete_failed', 'delete_notfound'], true); ?>
        <div class="<?= $isError ? 'alert-danger-sm' : 'alert-success-sm' ?> mb-3">
            <?php if ($_GET['msg'] === 'added') echo '✅ Produk berhasil ditambahkan!'; ?>
            <?php if ($_GET['msg'] === 'updated') echo '✅ Produk berhasil diperbarui!'; ?>
            <?php if ($_GET['msg'] === 'deleted') echo '🗑 Produk berhasil dihapus.'; ?>
            <?php if ($_GET['msg'] === 'delete_failed') echo '⚠ Produk tidak bisa dihapus karena sudah pernah ada di pesanan pelanggan. Hapus/arsipkan pesanan terkait dulu, atau nonaktifkan produk ini alih-alih menghapusnya.'; ?>
            <?php if ($_GET['msg'] === 'delete_notfound') echo '⚠ Produk tidak ditemukan (mungkin sudah terhapus sebelumnya).'; ?>
        </div>
    <?php endif; ?>

    <!-- TABEL PRODUK -->
    <div class="table-card">
        <div class="table-card-header">
            <span style="font-size:.9rem;font-weight:600">Daftar produk</span>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="q" class="search-input" placeholder="🔍 Cari produk..." value="<?= htmlspecialchars($search) ?>">
                    <select name="cat" class="search-input" style="width:130px" onchange="this.form.submit()">
                        <option value="">Semua kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a href="produk_form.php" class="btn-primary-sm">
                    <i class="bi bi-plus-lg"></i> Tambah
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th style="padding:12px 16px">Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada produk ditemukan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="padding:10px 16px">
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-thumb-sm">
                                    <?php if ($p['gambar'] && file_exists('../uploads/products/' . $p['gambar'])): ?>
                                        <img src="../uploads/products/<?= htmlspecialchars($p['gambar']) ?>" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-image text-muted" style="font-size:1.1rem"></i>
                                    <?php endif; ?>
                                </div>
                                <span style="font-weight:500"><?= htmlspecialchars($p['nama']) ?></span>
                            </div>
                        </td>
                        <td><span class="badge-cat-sm"><?= htmlspecialchars($p['kategori'] ?? '-') ?></span></td>
                        <td><?= formatRupiah($p['harga']) ?></td>
                        <td>
                            <?php if ($p['stok'] < 10): ?>
                                <span class="stock-low"><i class="bi bi-exclamation-triangle"></i> <?= $p['stok'] ?></span>
                            <?php else: ?>
                                <span class="stock-ok"><i class="bi bi-check-circle"></i> <?= $p['stok'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="produk_form.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="index.php?delete=<?= $p['id'] ?>" class="btn-icon danger" title="Hapus"
                                onclick="return confirm('Yakin hapus produk ini?')">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
