<?php
// admin/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['logout'])) {
    session_destroy();
    
    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false || strpos($current_path, '/etkinlik/') !== false || strpos($current_path, '/yonetici/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';
    
    header("Location: $login_path");
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    $current_path = $_SERVER['SCRIPT_NAME'];
    $is_subdir = (strpos($current_path, '/kulup/') !== false || strpos($current_path, '/etkinlik/') !== false || strpos($current_path, '/yonetici/') !== false);
    $login_path = $is_subdir ? '../login.php' : 'login.php';
    header("Location: $login_path");
    exit;
}
?>
