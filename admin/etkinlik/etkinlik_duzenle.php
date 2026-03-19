<?php
$page_title = 'Etkinlik Düzenle - Admin Paneli';
$header_title = 'Etkinlik Düzenle';
$active_page = 'etkinlikler';
require_once '../header.php';

$error = '';
$etkinlik = null;

// Etkinlik bilgilerini getir
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE id = ?");
    $stmt->execute([$id]);
    $etkinlik = $stmt->fetch();
    
    if (!$etkinlik) {
        header("Location: etkinlikler.php");
        exit;
    }
} else {
    header("Location: etkinlikler.php");
    exit;
}

$kulupler = $db->query("SELECT id, ad FROM kulupler ORDER BY ad ASC")->fetchAll();

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik'] ?? '');
    $kulup_id = $_POST['kulup_id'] ?: null;
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = $_POST['tarih'] ?? '';
    $konum = trim($_POST['konum'] ?? '');
    
    if (empty($baslik) || empty($tarih)) {
        $error = 'Lütfen başlık ve tarih alanlarını doldurunuz.';
    } else {
        $stmt = $db->prepare("UPDATE etkinlikler SET baslik = ?, kulup_id = ?, aciklama = ?, tarih = ?, konum = ? WHERE id = ?");
        $stmt->execute([$baslik, $kulup_id, $aciklama, $tarih, $konum, $etkinlik['id']]);
        header("Location: etkinlikler.php?mesaj=guncellendi");
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
            <label for="baslik">Etkinlik Başlığı</label>
            <input type="text" id="baslik" name="baslik" value="<?php echo htmlspecialchars($etkinlik['baslik']); ?>" required>
        </div>
        <div class="form-group">
            <label for="kulup_id">Kulüp</label>
            <select id="kulup_id" name="kulup_id" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); background-color: var(--bg-dark); color: white; outline: none;">
                <option value="">Genel Etkinlik</option>
                <?php foreach ($kulupler as $kulup): ?>
                    <option value="<?php echo $kulup['id']; ?>" <?php echo ($etkinlik['kulup_id'] == $kulup['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($kulup['ad']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tarih">Etkinlik Tarihi</label>
            <input type="datetime-local" id="tarih" name="tarih" value="<?php echo date('Y-m-d\TH:i', strtotime($etkinlik['tarih'])); ?>" required>
        </div>
        <div class="form-group">
            <label for="konum">Konum</label>
            <input type="text" id="konum" name="konum" value="<?php echo htmlspecialchars($etkinlik['konum']); ?>">
        </div>
        <div class="form-group">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama"><?php echo htmlspecialchars($etkinlik['aciklama']); ?></textarea>
        </div>
        <div class="action-btns">
            <button type="submit" class="btn-sm btn-primary">Güncelle</button>
            <a href="etkinlikler.php" class="btn-sm btn-danger">İptal</a>
        </div>
    </form>
</div>

<?php require_once '../footer.php'; ?>
