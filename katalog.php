<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ---- Wajib login untuk membuka halaman katalog ----
if (!isLoggedIn()) {
    // Simpan URL katalog beserta filter yang sedang aktif, agar setelah login
    // pengguna diarahkan kembali ke halaman yang sama
    $currentUrl = 'katalog.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
    header('Location: user/loginUser.php?redirect=' . urlencode($currentUrl));
    exit;
}

// ---- Ambil parameter filter dari URL ----
$q       = trim($_GET['q'] ?? '');
$catId   = $_GET['kategori'] ?? '';
$sort    = $_GET['sort'] ?? 'terbaru';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// ---- Bangun query dinamis ----
$where  = [];
$params = [];

if ($q !== '') {
    $where[] = 'p.nama LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
if ($catId !== '' && $catId !== 'semua') {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $catId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderMap = [
    'terbaru'    => 'p.id DESC',
    'nama_asc'   => 'p.nama ASC',
    'nama_desc'  => 'p.nama DESC',
    'harga_asc'  => 'p.harga ASC',
    'harga_desc' => 'p.harga DESC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['terbaru'];

// ---- Hitung total data (untuk pagination) ----
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$totalData  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalData / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ---- Ambil data produk ----
$sql = "
    SELECT p.*, c.nama AS kategori
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSql
    ORDER BY $orderSql
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// ---- Ambil semua kategori ----
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama ASC")->fetchAll();

// Helper untuk membangun query string saat pindah halaman/sort tanpa kehilangan filter aktif
function buildQuery($override = []) {
    $current = $_GET;
    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    $qs = http_build_query($current);
    return htmlspecialchars($qs === '' ? 'katalog.php' : '?' . $qs);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk — TokoKu</title>
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

        /* NAVBAR */
        .navbar { border-bottom: 1px solid var(--border); background: #fff !important; }
        .navbar-brand { font-weight: 700; font-size: 1.2rem; letter-spacing: -0.5px; }
        .nav-link { font-size: 0.875rem; color: #555 !important; }
        .nav-link:hover, .nav-link.active { color: var(--primary) !important; }

        /* PAGE HEADER */
        .page-header { padding: 2.5rem 0 1.5rem; }
        .page-header h1 { font-size: 1.9rem; font-weight: 800; letter-spacing: -1px; }
        .page-header p { color: #666; font-size: 0.9rem; }

        /* TOOLBAR */
        .toolbar { background: var(--soft); border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .form-control, .form-select { border-color: var(--border); font-size: 0.875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 .15rem rgba(0,0,0,.08); }

        .section-title { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.3px; }
        .badge-cat { font-size: 0.75rem; background: #fff; color: #555; border: 1px solid var(--border); padding: 5px 14px; border-radius: 99px; text-decoration: none; display: inline-block; }
        .badge-cat.active, .badge-cat:hover { background: var(--primary); color: #fff !important; border-color: var(--primary); }

        /* PRODUK */
        .product-card { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: transform .2s, box-shadow .2s; cursor: pointer; background: #fff; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .product-thumb { height: 170px; background: var(--soft); display: flex; align-items: center; justify-content: center; position: relative; }
        .product-thumb img { max-height: 150px; max-width: 90%; object-fit: contain; }
        .product-thumb .no-img { font-size: 3rem; color: #ccc; }
        .stock-badge { position: absolute; top: 8px; right: 8px; font-size: 0.65rem; padding: 3px 9px; border-radius: 99px; font-weight: 600; }
        .stock-ok { background: #e6f7ea; color: #1e7e34; }
        .stock-low { background: #fff4e0; color: #b8790a; }
        .stock-out { background: #fbe7e7; color: #c22; }
        .product-body { padding: 12px 14px; }
        .product-cat { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: .5px; }
        .product-name { font-size: 0.9rem; font-weight: 600; margin: 4px 0; min-height: 2.4em; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-price { font-size: 0.98rem; font-weight: 700; }

        .result-count { font-size: 0.85rem; color: #777; }

        /* MODAL */
        .modal-content { border-radius: 16px; border: none; }
        .modal-img-wrap { background: var(--soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; min-height: 260px; }
        .modal-img-wrap img { max-height: 300px; max-width: 100%; object-fit: contain; }
        #modalDeskripsi { font-size: 0.875rem; color: #444; white-space: pre-line; }

        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛍 TokoKu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto gap-3 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="user/indexUser.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link active" href="katalog.php">Katalog</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Akun') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="user/logoutUser.php">Keluar</a>`</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <h1>Katalog Produk</h1>
        <p>Semua produk yang tersedia di toko kami — cari, saring, dan temukan yang kamu butuhkan.</p>
    </div>

    <!-- TOOLBAR: PENCARIAN & SORT -->
    <form method="get" class="toolbar">
        <?php if ($catId !== ''): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($catId) ?>"><?php endif; ?>
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama produk..." value="<?= htmlspecialchars($q) ?>">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="nama_asc" <?= $sort === 'nama_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                    <option value="nama_desc" <?= $sort === 'nama_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                    <option value="harga_asc" <?= $sort === 'harga_asc' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="harga_desc" <?= $sort === 'harga_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <button type="submit" class="btn btn-dark w-100" style="background:var(--primary)">Cari</button>
            </div>
        </div>
    </form>

    <!-- FILTER KATEGORI -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <span class="section-title me-2">Kategori</span>
        <a href="<?= buildQuery(['kategori' => null, 'page' => null]) ?>" class="badge-cat <?= $catId === '' ? 'active' : '' ?>">Semua</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= buildQuery(['kategori' => $cat['id'], 'page' => null]) ?>" class="badge-cat <?= (string)$catId === (string)$cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['nama']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="result-count mb-3">
        Menampilkan <?= count($products) ?> dari <?= $totalData ?> produk
        <?php if ($q !== ''): ?> untuk pencarian "<strong><?= htmlspecialchars($q) ?></strong>"<?php endif; ?>
    </p>

    <!-- GRID PRODUK -->
    <div class="row g-3 mb-4">
        <?php foreach ($products as $p): ?>
            <?php
                $stok = (int)$p['stok'];
                if ($stok <= 0) { $stockClass = 'stock-out'; $stockLabel = 'Habis'; }
                elseif ($stok <= 5) { $stockClass = 'stock-low'; $stockLabel = 'Stok ' . $stok; }
                else { $stockClass = 'stock-ok'; $stockLabel = 'Stok ' . $stok; }
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100"
                     data-bs-toggle="modal" data-bs-target="#produkModal"
                     data-nama="<?= htmlspecialchars($p['nama']) ?>"
                     data-harga="<?= htmlspecialchars(formatRupiah($p['harga'])) ?>"
                     data-kategori="<?= htmlspecialchars($p['kategori'] ?? '-') ?>"
                     data-stok="<?= $stok ?>"
                     data-deskripsi="<?= htmlspecialchars($p['deskripsi'] ?? '') ?>"
                     data-gambar="<?= !empty($p['gambar']) ? 'uploads/products/' . htmlspecialchars($p['gambar']) : '' ?>">
                    <div class="product-thumb">
                        <span class="stock-badge <?= $stockClass ?>"><?= $stockLabel ?></span>
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
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">Produk tidak ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <nav class="mb-5">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildQuery(['page' => $page - 1]) ?>">«</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildQuery(['page' => $i]) ?>" style="<?= $i === $page ? 'background:var(--primary);border-color:var(--primary)' : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildQuery(['page' => $page + 1]) ?>">»</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- MODAL DETAIL PRODUK -->
<div class="modal fade" id="produkModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="modal-img-wrap">
                            <img id="modalImg" src="" alt="" style="display:none">
                            <i class="bi bi-box-seam" id="modalNoImg" style="font-size:4rem;color:#ccc;"></i>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <span class="product-cat" id="modalKategori"></span>
                        <h4 class="fw-bold mt-1 mb-2" id="modalNama"></h4>
                        <div class="fs-4 fw-bold mb-2" id="modalHarga"></div>
                        <span class="badge rounded-pill mb-3" id="modalStok"></span>
                        <p id="modalDeskripsi"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="container text-center">
        <p>© <?= date('Y') ?> TokoKu — Dibuat dengan ❤️ untuk tugas Pemrograman Web Lanjut</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', () => {
        const img = document.getElementById('modalImg');
        const noImg = document.getElementById('modalNoImg');
        const gambar = card.dataset.gambar;

        document.getElementById('modalNama').textContent = card.dataset.nama;
        document.getElementById('modalHarga').textContent = card.dataset.harga;
        document.getElementById('modalKategori').textContent = card.dataset.kategori;
        document.getElementById('modalDeskripsi').textContent = card.dataset.deskripsi || 'Tidak ada deskripsi untuk produk ini.';

        const stok = parseInt(card.dataset.stok, 10);
        const stokEl = document.getElementById('modalStok');
        if (stok <= 0) {
            stokEl.textContent = 'Stok habis';
            stokEl.className = 'badge rounded-pill mb-3 bg-danger-subtle text-danger';
        } else if (stok <= 5) {
            stokEl.textContent = 'Sisa ' + stok + ' — segera habis';
            stokEl.className = 'badge rounded-pill mb-3 bg-warning-subtle text-warning-emphasis';
        } else {
            stokEl.textContent = 'Stok tersedia: ' + stok;
            stokEl.className = 'badge rounded-pill mb-3 bg-success-subtle text-success';
        }

        if (gambar) {
            img.src = gambar;
            img.style.display = 'block';
            noImg.style.display = 'none';
        } else {
            img.style.display = 'none';
            noImg.style.display = 'block';
        }
    });
});
</script>
</body>
</html>
