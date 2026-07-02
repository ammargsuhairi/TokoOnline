<?php
// Bootstrap session terpusat.
// Dipakai di semua halaman agar cookie sesi konsisten (path selalu "/"),
// sehingga login tidak "lupa" saat pindah dari /user/... ke halaman root.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
