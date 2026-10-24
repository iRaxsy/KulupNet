<?php
require_once 'includes/csrf.php';

// Çıkış: tüm oturumu kapat (kullanıcı + admin aynı PHPSESSID'i paylaşabilir,
// sadece kullanıcı anahtarlarını unset'lemek admin tarafında oturumu açık bırakıyordu).
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header("Location: index.php");
exit;
