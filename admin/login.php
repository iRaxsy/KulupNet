<?php
session_start();
require_once '../config/veritabani.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    if (!empty($kullanici_adi) && !empty($sifre)) {
        $stmt = $db->prepare("SELECT * FROM adminler WHERE kullanici_adi = ?");
        $stmt->execute([$kullanici_adi]);
        $admin = $stmt->fetch();

        if ($admin && $sifre === $admin['sifre']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_adi'] = $admin['kullanici_adi'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Geçersiz kullanıcı adı veya şifre.";
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - Kulüp Yönetimi</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>Admin Paneli</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="kullanici_adi">Kullanıcı Adı</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" required>
            </div>
            <div class="form-group">
                <label for="sifre">Şifre</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
