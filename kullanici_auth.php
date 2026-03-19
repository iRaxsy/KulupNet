<?php
// kullanici_auth.php - Kullanıcı oturum kontrol
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/veritabani.php';

function kullanici_giris_kontrol() {
    return isset($_SESSION['kullanici_id']);
}

function kullanici_bilgi($db) {
    if (!kullanici_giris_kontrol()) return null;
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$_SESSION['kullanici_id']]);
    return $stmt->fetch();
}

function giris_gerekli() {
    if (!kullanici_giris_kontrol()) {
        header("Location: giris.php");
        exit;
    }
}
?>
