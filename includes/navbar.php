<?php
/**
 * NAVBAR BERSAMA
 * ---------------
 * Dipakai di semua halaman (root maupun folder user/) supaya menu, link,
 * dan tampilan navbar selalu konsisten — tidak lagi copy-paste per halaman.
 *
 * Cara pakai, di halaman pemanggil (sebelum tag <body> ditutup / tepat sesudahnya):
 *
 *   <?php
 *   $base      = '';          // '' untuk halaman di root, '../' untuk halaman di folder user/
 *   $activeNav = 'katalog';   // salah satu: beranda | katalog | keranjang | profil | riwayat | '' (tidak ada yang aktif)
 *   include __DIR__ . '/includes/navbar.php';     // dari root
 *   include __DIR__ . '/../includes/navbar.php';  // dari folder user/
 *   ?>
 *
 * Prasyarat: includes/session.php sudah di-include sebelumnya di halaman pemanggil.
 * Opsional: includes/cart.php ikut di-include supaya badge jumlah keranjang otomatis muncul.
 */

$base      = $base ?? '';
$activeNav = $activeNav ?? '';
$loggedIn  = isset($_SESSION['user_id']);
$namaUser  = $_SESSION['user_nama'] ?? 'Akun';
$jumlahCart = ($loggedIn && function_exists('cartCount')) ? cartCount() : 0;
?>
<style>
    .navbar { border-bottom: 1px solid var(--border, #e0e0da); background: #fff !important; }
    .navbar-brand { font-weight: 700; font-size: 1.2rem; letter-spacing: -0.5px; color: var(--primary, #1a1a1a) !important; }
    .navbar .nav-link { font-size: 0.875rem; color: #555 !important; }
    .navbar .nav-link:hover, .navbar .nav-link.active { color: var(--primary, #1a1a1a) !important; }
    .navbar .user-toggle { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: var(--primary, #1a1a1a) !important; font-weight: 600; }
    .navbar .user-toggle i.bi-person-circle { font-size: 1.3rem; color: #555; }
    .navbar .dropdown-menu { border: 1px solid var(--border, #e0e0da); border-radius: 10px; font-size: 0.875rem; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .navbar .dropdown-item { padding: 8px 16px; }
    .navbar .dropdown-item i { width: 18px; }
    .navbar .cart-badge { font-size: .62rem; vertical-align: top; margin-left: 2px; }
</style>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?><?= $loggedIn ? 'user/indexUser.php' : 'index.php' ?>">🛍 TokoKu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto gap-3 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'beranda' ? 'active' : '' ?>" href="<?= $base ?><?= $loggedIn ? 'user/indexUser.php' : 'index.php' ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'katalog' ? 'active' : '' ?>" href="<?= $base ?>katalog.php">Katalog</a>
                </li>

                <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'keranjang' ? 'active' : '' ?>" href="<?= $base ?>keranjang.php">
                            <i class="bi bi-cart3"></i> Keranjang
                            <span class="badge rounded-pill bg-dark cart-badge <?= $jumlahCart > 0 ? '' : 'd-none' ?>" id="cartBadge"><?= $jumlahCart ?></span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link user-toggle dropdown-toggle <?= in_array($activeNav, ['profil', 'riwayat'], true) ? 'active' : '' ?>" href="#" id="userDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span><?= htmlspecialchars($namaUser) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item <?= $activeNav === 'profil' ? 'active' : '' ?>" href="<?= $base ?>user/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                            <li><a class="dropdown-item <?= $activeNav === 'riwayat' ? 'active' : '' ?>" href="<?= $base ?>user/riwayat_pesanan.php"><i class="bi bi-clock-history"></i> Riwayat Pesanan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base ?>user/logoutUser.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>user/loginUser.php">Masuk</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
