<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';

// Jika sudah login, arahkan ke beranda khusus user
if (isset($_SESSION['user_id'])) {
    header('Location: user/indexUser.php');
    exit;
}

// Ambil produk unggulan (6 produk terbaru)
$stmt = $pdo->query("
    SELECT p.*, c.nama AS kategori 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC 
    LIMIT 6
");
$products = $stmt->fetchAll();

// Ambil semua kategori
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TokoKu — Belanja Mudah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --accent: #e8ff00;
            --soft: #f5f5f0;
            --border: #e0e0da;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }

        /* HERO */
        .hero { background: var(--soft); border-radius: 16px; padding: 3.5rem 3rem; margin: 1.5rem 0; }
        .hero h1 { font-size: 2.5rem; font-weight: 800; line-height: 1.15; letter-spacing: -1px; }
        .hero p { color: #666; font-size: 1rem; max-width: 380px; }
        .hero .highlight { background: var(--accent); padding: 0 6px; border-radius: 4px; }
        .btn-dark { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 0.875rem; }
        .btn-dark:hover { background: #333; color: #fff; }
        .hero-badge { 
            display: inline-block; background: var(--primary); color: #fff; 
            font-size: 0.7rem; padding: 4px 12px; border-radius: 99px; 
            letter-spacing: 1px; text-transform: uppercase; margin-bottom: 1rem; 
        }

        /* PRODUK */
        .section-title { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.3px; }
        .product-card { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .product-thumb { height: 160px; background: var(--soft); display: flex; align-items: center; justify-content: center; }
        .product-thumb img { max-height: 140px; object-fit: contain; }
        .product-thumb .no-img { font-size: 3rem; color: #ccc; }
        .product-body { padding: 12px 14px; }
        .product-cat { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: .5px; }
        .product-name { font-size: 0.9rem; font-weight: 600; margin: 4px 0; }
        .product-price { font-size: 0.95rem; font-weight: 700; }
        .badge-cat { font-size: 0.68rem; background: var(--soft); color: #555; border: 1px solid var(--border); padding: 3px 10px; border-radius: 99px; cursor: pointer; }
        .badge-cat.active, .badge-cat:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* FOOTER */
        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<?php $base = ''; $activeNav = 'beranda'; include 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="container">
    <div class="hero d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div>
            <span class="hero-badge">✦ Produk pilihan</span>
            <h1>Belanja mudah,<br><span class="highlight">harga terjangkau</span></h1>
            <p class="mt-3">Temukan berbagai produk pilihan dengan kualitas terbaik dan harga yang bersahabat.</p>
            <a href="katalog.php" class="btn btn-dark mt-2">Lihat katalog →</a>
        </div>
        <div style="font-size: 6rem; line-height:1">🛒</div>
    </div>

    <!-- FILTER KATEGORI -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <span class="section-title me-2">Produk unggulan</span>
        <span class="badge-cat active" data-cat="">Semua</span>
        <?php foreach ($categories as $cat): ?>
            <span class="badge-cat" data-cat="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama']) ?></span>
        <?php endforeach; ?>
    </div>

    <!-- GRID PRODUK -->
    <div class="row g-3 mb-5" id="product-grid">
        <?php foreach ($products as $p): ?>
        <div class="col-6 col-md-4 col-lg-2 product-item" data-cat="<?= $p['category_id'] ?>">
            <div class="product-card h-100">
                <div class="product-thumb">
                    <?php if (!empty($p['gambar'])): ?>
                        <img src="uploads/products/<?= htmlspecialchars($p['gambar']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                        <i class="bi bi-box-seam no-img" style="display:none"></i>
                    <?php else: ?>
                        <i class="bi bi-box-seam no-img"></i>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <div class="product-cat"><?= htmlspecialchars($p['kategori'] ?? '-') ?></div>
                    <div class="product-name"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="product-price"><?= formatRupiah($p['harga']) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="col-12 text-center text-muted py-5">Belum ada produk.</div>
        <?php endif; ?>
    </div>
</div>

<?php
    include 'footer.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter kategori
document.querySelectorAll('.badge-cat').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.badge-cat').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.dataset.cat;
        document.querySelectorAll('.product-item').forEach(item => {
            item.style.display = (!cat || item.dataset.cat === cat) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>
