<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';

if (!isLoggedIn()) {
    header('Location: user/loginUser.php?redirect=' . urlencode('keranjang.php'));
    exit;
}

// Hapus item dari keranjang
if (isset($_GET['remove'])) {
    cartRemove((int)$_GET['remove']);
    header('Location: keranjang.php');
    exit;
}

// Update qty (dari input number di halaman ini, tanpa AJAX, biar simpel & tetap jalan tanpa JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'update_qty') {
    foreach ($_POST['qty'] as $pid => $qty) {
        cartSetQty((int)$pid, (int)$qty);
    }
    header('Location: keranjang.php');
    exit;
}

// Lanjut ke checkout dengan item yang dicentang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'checkout') {
    $selected = $_POST['selected'] ?? [];
    if (empty($selected)) {
        header('Location: keranjang.php?err=nopick');
        exit;
    }
    $_SESSION['checkout_ids'] = array_map('intval', $selected);
    header('Location: checkout.php');
    exit;
}

$items = cartGetItems($pdo);
$total = cartTotal($items);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --accent: #e8ff00; --soft: #f5f5f0; --border: #e0e0da; }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }
        .page-header { padding: 2rem 0 1.5rem; }
        .page-header h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        .cart-item { border: 1px solid var(--border); border-radius: 12px; padding: 14px; margin-bottom: 12px; }
        .cart-thumb { width: 72px; height: 72px; border-radius: 8px; background: var(--soft); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .cart-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .qty-input { width: 70px; }
        .summary-card { border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; position: sticky; top: 90px; }
        .btn-dark-custom { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: .9rem; }
        .btn-dark-custom:hover { background: #333; color: #fff; }
        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<?php $base = ''; $activeNav = 'keranjang'; include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Keranjang Belanja</h1>
        <p class="text-muted mb-0" style="font-size:.9rem">Periksa kembali produk sebelum lanjut ke checkout.</p>
    </div>

    <?php if (isset($_GET['err']) && $_GET['err'] === 'nopick'): ?>
        <div class="alert alert-warning py-2 px-3" style="font-size:.85rem;border-radius:8px">
            Pilih minimal 1 produk untuk lanjut ke checkout.
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-cart-x" style="font-size:3rem;"></i>
            <p class="mt-3 mb-3">Keranjang kamu masih kosong.</p>
            <a href="katalog.php" class="btn btn-dark" style="background:var(--primary);border-radius:8px">Mulai belanja →</a>
        </div>
    <?php else: ?>
    <form method="POST" id="cartForm">
        <input type="hidden" name="form" value="checkout">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="pilihSemua" checked>
                        <label class="form-check-label" for="pilihSemua" style="font-size:.9rem">Pilih semua</label>
                    </div>
                    <a href="cart_action.php?clear=1" onclick="event.preventDefault(); clearCart();" class="text-danger text-decoration-none" style="font-size:.85rem"><i class="bi bi-trash"></i> Kosongkan keranjang</a>
                </div>

                <?php foreach ($items as $it): ?>
                    <div class="cart-item d-flex gap-3 align-items-center">
                        <input class="form-check-input item-check flex-shrink-0" type="checkbox" name="selected[]" value="<?= $it['id'] ?>" checked>
                        <div class="cart-thumb">
                            <?php if (!empty($it['gambar'])): ?>
                                <img src="uploads/products/<?= htmlspecialchars($it['gambar']) ?>" onerror="this.style.display='none'">
                            <?php else: ?>
                                <i class="bi bi-box-seam" style="font-size:1.6rem;color:#ccc"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.9rem"><?= htmlspecialchars($it['nama']) ?></div>
                            <div class="text-muted" style="font-size:.8rem"><?= formatRupiah($it['harga']) ?> / item · stok <?= $it['stok'] ?></div>
                        </div>
                        <div>
                            <input type="number" name="qty[<?= $it['id'] ?>]" class="form-control form-control-sm qty-input item-qty"
                                data-harga="<?= $it['harga'] ?>" min="1" max="<?= $it['stok'] ?>" value="<?= $it['qty'] ?>">
                        </div>
                        <div class="text-end" style="min-width:110px">
                            <div class="fw-bold item-subtotal" style="font-size:.9rem"><?= formatRupiah($it['subtotal']) ?></div>
                            <a href="keranjang.php?remove=<?= $it['id'] ?>" class="text-danger text-decoration-none" style="font-size:.78rem" onclick="return confirm('Hapus produk ini dari keranjang?')">Hapus</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="button" id="btnUpdateQty" class="btn btn-outline-secondary btn-sm mt-1" style="border-radius:8px">
                    <i class="bi bi-arrow-repeat"></i> Perbarui jumlah
                </button>
            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 class="fw-bold mb-3" style="font-size:1rem">Ringkasan Belanja</h5>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.9rem">
                        <span>Subtotal (<span id="subtotalCount"><?= count($items) ?></span> produk)</span>
                        <span id="subtotalValue"><?= formatRupiah($total) ?></span>
                    </div>
                    <p class="text-muted" style="font-size:.78rem">Ongkos kirim & metode pembayaran ditentukan di halaman checkout.</p>
                    <button type="submit" class="btn btn-dark-custom w-100 mt-2">Lanjut ke Checkout →</button>
                    <a href="katalog.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:8px;font-size:.85rem">Tambah produk lain</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Form terpisah untuk update qty (submit ke server, refresh halaman) -->
    <form method="POST" id="updateQtyForm" style="display:none">
        <input type="hidden" name="form" value="update_qty">
    </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function clearCart() {
    if (!confirm('Kosongkan semua produk di keranjang?')) return;
    fetch('cart_action.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=clear' })
        .then(() => location.reload());
}

document.getElementById('pilihSemua')?.addEventListener('change', function() {
    document.querySelectorAll('.item-check').forEach(c => c.checked = this.checked);
});

// Kirim ulang qty ke server (form update_qty) tapi memakai nama field qty[id]
document.getElementById('btnUpdateQty')?.addEventListener('click', function() {
    const targetForm = document.getElementById('updateQtyForm');
    document.querySelectorAll('.item-qty').forEach(inp => {
        const name = inp.getAttribute('name'); // qty[ID]
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name;
        hidden.value = inp.value;
        targetForm.appendChild(hidden);
    });
    targetForm.submit();
});

// Update subtotal live saat qty diubah (estimasi sebelum submit)
document.querySelectorAll('.item-qty').forEach(inp => {
    inp.addEventListener('input', function() {
        const harga = parseFloat(this.dataset.harga);
        const qty = parseInt(this.value || '1', 10);
        const row = this.closest('.cart-item');
        const subtotalEl = row.querySelector('.item-subtotal');
        subtotalEl.textContent = 'Rp ' + (harga * qty).toLocaleString('id-ID');
    });
});
</script>
</body>
</html>
