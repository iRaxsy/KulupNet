<?php
// includes/csrf.php - CSRF token üretimi/doğrulaması + güvenli oturum başlatma
// Bu dosya, session_start() çağıran her sayfanın EN BAŞINDA include edilmelidir;
// böylece oturum çerezi parametreleri cookie gönderilmeden önce ayarlanmış olur.

if (session_status() === PHP_SESSION_NONE) {
    // Yerel XAMPP/http kurulumunda 'secure' kapalı kalmalı; HTTPS'te açılır.
    $gercek_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $gercek_https,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        session_set_cookie_params(0, '/', '', $gercek_https, true);
    }
    session_start();
}

/**
 * Oturumda bir CSRF token'ı yoksa üretir, varsa döndürür.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Formlara gömülecek hidden input HTML'i.
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Gelen token'ı oturumdakilerle karşılaştırır.
 * POST ve (geçici GET silme köprüleri için) GET üzerinde aranır.
 */
function csrf_dogrula($gelen = null) {
    if ($gelen === null) {
        $gelen = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    if (!is_string($gelen) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $gelen);
}

/**
 * Token geçersizse işlemi 400 ile durdurur.
 */
function csrf_zorunlu() {
    if (!csrf_dogrula()) {
        http_response_code(400);
        exit('Geçersiz veya eksik CSRF token. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}
