<?php
// admin/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['logout'])) {
    // Sadece admin oturum değişkenlerini temizle
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_adi']);
    
    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false || strpos($current_path, '/etkinlik/') !== false || strpos($current_path, '/yonetici/') !== false || strpos($current_path, '/uyeler/') !== false || strpos($current_path, '/oneri_sikayet/') !== false || strpos($current_path, '/duyuru/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';
    
    header("Location: $login_path");
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false || strpos($current_path, '/etkinlik/') !== false || strpos($current_path, '/yonetici/') !== false || strpos($current_path, '/uyeler/') !== false || strpos($current_path, '/oneri_sikayet/') !== false || strpos($current_path, '/duyuru/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';
    header("Location: $login_path");
    exit;
}
?>
