<?php
function isLoggedIn() {
    return isset($_SESSION['admin_id']) || isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /toko_online/admin/login.php');
        exit;
    }
}

function requireUserLogin() {
    if (!isLoggedIn()) {
        header('Location: /toko_online/user/loginUser.php');
        exit;
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
