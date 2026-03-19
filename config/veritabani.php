<?php
// config/veritabani.php

$host = 'localhost';
$dbname = 'kulup_yonetimi';
$username = 'root';
$password = ''; // XAMPP varsayılanı boştur

try {
    date_default_timezone_set('Europe/Istanbul');
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
