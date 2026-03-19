<?php
$page_title = 'Kulüp Düzenle - Admin Paneli';
$header_title = 'Kulüp Düzenle';
$active_page = 'kulupler';
require_once '../header.php';

$error = '';
$kulup = null;

// Kulüp bilgilerini getir
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
    $stmt->execute([$id]);
    $kulup = $stmt->fetch();
    
    if (!$kulup) {
        header("Location: kulupler.php");
        exit;
    }
} else {
    header("Location: kulupler.php");
    exit;
}

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    
    if (empty($ad)) {
        $error = 'Kulüp adı boş bırakılamaz.';
    } else {
        $stmt = $db->prepare("UPDATE kulupler SET ad = ?, aciklama = ? WHERE id = ?");
        $stmt->execute([$ad, $aciklama, $kulup['id']]);
        header("Location: kulupler.php?mesaj=guncellendi");
        exit;
    }
}
?>

<?php if ($error): ?>
    <p style="color: #ef4444; margin-bottom: 1rem;"><?php echo $error; ?></p>
<?php endif; ?>

<div class="form-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="ad">Kulüp Adı</label>
            <input type="text" id="ad" name="ad" value="<?php echo htmlspecialchars($kulup['ad']); ?>" required>
        </div>
        <div class="form-group">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama"><?php echo htmlspecialchars($kulup['aciklama']); ?></textarea>
        </div>
        <div class="action-btns">
            <button type="submit" class="btn-sm btn-primary">Güncelle</button>
            <a href="kulupler.php" class="btn-sm btn-danger">İptal</a>
        </div>
    </form>
</div>

<?php require_once '../footer.php'; ?>
