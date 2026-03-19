<?php
session_start();
// Sadece kullanıcı oturum değişkenlerini temizle
unset($_SESSION['kullanici_id']);
unset($_SESSION['kullanici_adi']);
unset($_SESSION['kullanici_ad']);
unset($_SESSION['kullanici_rol']);
header("Location: index.php");
exit;
?>
