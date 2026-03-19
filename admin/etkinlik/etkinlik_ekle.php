<?php
$page_title = 'Yeni Etkinlik Ekle - Admin Paneli';
$header_title = 'Yeni Etkinlik Ekle';
$active_page = 'etkinlikler';
require_once '../header.php';

$error = '';
$kulupler = $db->query("SELECT id, ad FROM kulupler ORDER BY ad ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'];
    $kulup_id = $_POST['kulup_id'] ?: null;
    $aciklama = $_POST['aciklama'];
    $tarih = $_POST['tarih'];
    $konum = $_POST['konum'];

    if (!empty($baslik) && !empty($tarih)) {
        $stmt = $db->prepare("INSERT INTO etkinlikler (kulup_id, baslik, aciklama, tarih, konum) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$kulup_id, $baslik, $aciklama, $tarih, $konum]);
        header("Location: etkinlikler.php?mesaj=eklendi");
        exit;
    } else {
        $error = "Lütfen başlık ve tarih alanlarını doldurunuz.";
    }
}
?>

<div class="form-card">
    <?php if ($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="" method="POST">
        <div class="form-group">
            <label for="baslik">Etkinlik Başlığı</label>
            <input type="text" id="baslik" name="baslik" required>
        </div>
        <div class="form-group">
            <label for="kulup_id">Kulüp</label>
            <select id="kulup_id" name="kulup_id" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); background-color: var(--bg-dark); color: white; outline: none;">
                <option value="">Genel Etkinlik</option>
                <?php foreach ($kulupler as $kulup): ?>
                    <option value="<?php echo $kulup['id']; ?>"><?php echo htmlspecialchars($kulup['ad']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tarih">Etkinlik Tarihi</label>
            <input type="datetime-local" id="tarih" name="tarih" required>
        </div>
        <div class="form-group">
            <label for="konum">Konum</label>
            <input type="text" id="konum" name="konum">
        </div>
        <div class="form-group">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama"></textarea>
        </div>
        <button type="submit" class="btn">Kaydet</button>
    </form>
</div>

<?php require_once '../footer.php'; ?>
