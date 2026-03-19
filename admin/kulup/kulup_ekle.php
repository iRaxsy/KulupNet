<?php
$page_title = 'Yeni Kulüp Ekle - Admin Paneli';
$header_title = 'Yeni Kulüp Ekle';
$active_page = 'kulupler';
require_once '../header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = $_POST['ad'];
    $aciklama = $_POST['aciklama'];

    if (!empty($ad)) {
        $stmt = $db->prepare("INSERT INTO kulupler (ad, aciklama) VALUES (?, ?)");
        $stmt->execute([$ad, $aciklama]);
        header("Location: kulupler.php?mesaj=eklendi");
        exit;
    } else {
        $error = "Lütfen kulüp adını giriniz.";
    }
}
?>

<div class="form-card">
    <?php if ($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="" method="POST">
        <div class="form-group">
            <label for="ad">Kulüp Adı</label>
            <input type="text" id="ad" name="ad" required>
        </div>
        <div class="form-group">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama"></textarea>
        </div>
        <button type="submit" class="btn">Kaydet</button>
    </form>
</div>

<?php require_once '../footer.php'; ?>
