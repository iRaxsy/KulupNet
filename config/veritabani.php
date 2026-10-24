<?php
// config/veritabani.php
// Kimlik bilgileri ortam değişkenlerinden okunur; repoda sabit parola tutulmaz.
// XAMPP gibi yerel kurulumlarda varsayılanlar çalışmaya devam eder.

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Üretimde ham hata/stack trace ifşasını engelle
// Gerekirse web sunucusu hata günlüğünü ayarlasın; yoksa PHP varsayılan logu kullanılır.

$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'kulupnet';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     !== false ? getenv('DB_PASS') : ''; // XAMPP varsayılanı boştur

try {
    date_default_timezone_set('Europe/Istanbul');
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    // Bağlantı ayrıntılarını (host/user/schema) ziyaretçiye ifşa etme; log'a yaz.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Veritabanı bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin.');
}
