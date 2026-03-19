<?php
$page_title = 'Dashboard - Kulüp Yönetimi';
$header_title = 'Hoş geldin, ' . (isset($_SESSION['admin_adi']) ? $_SESSION['admin_adi'] : 'Admin');
$active_page = 'dashboard';
require_once 'header.php';

$kulup_sayisi = $db->query("SELECT COUNT(*) FROM kulupler")->fetchColumn();
$etkinlik_sayisi = $db->query("SELECT COUNT(*) FROM etkinlikler")->fetchColumn();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Toplam Kulüp</h3>
        <div class="value"><?php echo $kulup_sayisi; ?></div>
    </div>
    <div class="stat-card">
        <h3>Toplam Etkinlik</h3>
        <div class="value"><?php echo $etkinlik_sayisi; ?></div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
