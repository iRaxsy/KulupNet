<?php
// admin/auth.php
require_once __DIR__ . '/../includes/csrf.php';

// Çıkış: artık yalnızca POST + geçerli CSRF token ile yapılır (CSRF'ye kapalı GET ?logout kaldırıldı)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    csrf_zorunlu();
    // Oturumu tamamen kapat (kullanıcı ve admin aynı PHPSESSID'i paylaşabilir)
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false
        || strpos($current_path, '/etkinlik/') !== false
        || strpos($current_path, '/yonetici/') !== false
        || strpos($current_path, '/uye/') !== false
        || strpos($current_path, '/uyeler/') !== false
        || strpos($current_path, '/yasakli/') !== false
        || strpos($current_path, '/oneri_sikayet/') !== false
        || strpos($current_path, '/duyuru/') !== false
        || strpos($current_path, '/toplanti/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';

    header("Location: $login_path");
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false
        || strpos($current_path, '/etkinlik/') !== false
        || strpos($current_path, '/yonetici/') !== false
        || strpos($current_path, '/uye/') !== false
        || strpos($current_path, '/uyeler/') !== false
        || strpos($current_path, '/yasakli/') !== false
        || strpos($current_path, '/oneri_sikayet/') !== false
        || strpos($current_path, '/duyuru/') !== false
        || strpos($current_path, '/toplanti/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';
    header("Location: $login_path");
    exit;
}
