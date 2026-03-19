<?php
$page_title = 'Kulüpler - Admin Paneli';
$header_title = 'Kulüp Yönetimi';
$active_page = 'kulupler';
require_once '../header.php';

// Silme işlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    $stmt = $db->prepare("DELETE FROM kulupler WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: kulupler.php?mesaj=silindi");
    exit;
}

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

// Toplam kayıt sayısı
if (!empty($aranan)) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM kulupler WHERE ad LIKE ? OR aciklama LIKE ?");
    $count_stmt->execute(["%$aranan%", "%$aranan%"]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM kulupler");
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Verileri getir
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
if (!empty($aranan)) {
    $stmt = $db->prepare("SELECT * FROM kulupler WHERE ad LIKE ? OR aciklama LIKE ? ORDER BY id DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute(["%$aranan%", "%$aranan%"]);
} else {
    $stmt = $db->query("SELECT * FROM kulupler ORDER BY id DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
}
$kulupler = $stmt->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <form action="" method="GET" style="display: flex; gap: 0.5rem;">
        <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Kulüp ara..." 
               style="padding: 0.6rem 1rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1); background: #1e293b; color: white; outline: none; font-size: 0.875rem; width: 220px;">
        <button type="submit" class="btn-sm btn-primary" style="padding: 0.6rem 1.25rem;">Ara</button>
        <?php if ($aranan): ?>
            <a href="kulupler.php" class="btn-sm btn-danger" style="padding: 0.6rem 1rem;">Temizle</a>
        <?php endif; ?>
    </form>
    <a href="kulup_ekle.php" class="btn btn-primary">Yeni Kulüp Ekle</a>
</div>

<?php if ($aranan): ?>
    <div style="color: #94a3b8; margin-bottom: 1rem; font-size: 0.875rem;">
        "<strong><?php echo htmlspecialchars($aranan); ?></strong>" için <?php echo $kayit_sayisi; ?> sonuç bulundu.
    </div>
<?php endif; ?>

<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Kulüp başarıyla silindi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'guncellendi'): ?>
    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Kulüp başarıyla güncellendi.
    </div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kulüp Adı</th>
                <th>Açıklama</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kulupler as $kulup): ?>
                <tr>
                    <td><?php echo $kulup['id']; ?></td>
                    <td><?php echo htmlspecialchars($kulup['ad']); ?></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($kulup['aciklama'], 0, 100, "...")); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="kulup_etkinlikleri.php?kulup_id=<?php echo $kulup['id']; ?>" class="btn-sm" style="background: #10b981; color: white; border: none;">Etkinlikler</a>
                            <a href="kulup_duzenle.php?id=<?php echo $kulup['id']; ?>" class="btn-sm btn-primary">Düzelt</a>
                            <a href="kulupler.php?sil=<?php echo $kulup['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Bu kulübü silmek istediğinize emin misiniz?')">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($kulupler)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: #94a3b8;">
                        <?php echo $aranan ? 'Aramanızla eşleşen kulüp bulunamadı.' : 'Henüz kayıtlı kulüp yok.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'kulupler.php';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
