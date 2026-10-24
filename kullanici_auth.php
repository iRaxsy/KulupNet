<?php
// kullanici_auth.php - Kullanıcı oturum / durum / rol kontrolü (tek merkez)
// Sayfalar buradaki fonksiyonları kullanmalı; dağınık `isset($_SESSION['kullanici_id'])`
// kontrolleri Yasakli kullanıcıyı kaçırabiliyordu.
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/includes/csrf.php'; // güvenli cookie parametreleriyle session başlat
}

require_once __DIR__ . '/config/veritabani.php';

/**
 * Kullanıcı giriş yapmış mı ve hesabı aktif mi?
 * (giris.php oturum açıldığında `kullanici_durum`'u session'a yazar;
 *  Yetkili/Admin profil.php üzerinden ban'ladığında session bir sonraki
 *  sayfa yüklemesinde geçersiz sayılır.)
 */
function kullanici_giris_kontrol() {
    if (!isset($_SESSION['kullanici_id'])) return false;
    if (($_SESSION['kullanici_durum'] ?? 'Aktif') === 'Yasakli') return false;
    return true;
}

function kullanici_bilgi($db) {
    if (!isset($_SESSION['kullanici_id'])) return null;
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$_SESSION['kullanici_id']]);
    $u = $stmt->fetch();
    if (!$u) return null;
    // DB'deki güncel durum oturumla senkronize edilsin (ban'lanmışsa hemen etkisiz olsun)
    if (($u['durum'] ?? 'Aktif') === 'Yasakli') {
        $_SESSION['kullanici_durum'] = 'Yasakli';
        return null;
    }
    $_SESSION['kullanici_durum'] = $u['durum'];
    $_SESSION['kullanici_rol']     = $u['rol'];
    return $u;
}

/**
 * Zorunlu giriş: giriş yoksa veya kullanıcı yasaklıysa giris.php'ye yönlendir.
 */
function giris_gerekli($db = null) {
    if (!kullanici_giris_kontrol()) {
        header("Location: giris.php");
        exit;
    }
    // DB verildiyse canlı durum kontrolü yap (ban'lanmışsa çıkışa zorlar)
    if ($db !== null && kullanici_bilgi($db) === null) {
        // Session'daki kullanıcı artık yok ya da yasaklı
        unset($_SESSION['kullanici_id'], $_SESSION['kullanici_adi'],
              $_SESSION['kullanici_ad'], $_SESSION['kullanici_rol'],
              $_SESSION['kullanici_durum']);
        header("Location: giris.php");
        exit;
    }
}
?>
