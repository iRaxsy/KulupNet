<?php
$page_title = 'Etkinlikler - Admin Paneli';
$header_title = 'Etkinlik Yönetimi';
$active_page = 'etkinlikler';
require_once '../header.php';

// Silme işlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    $stmt = $db->prepare("DELETE FROM etkinlikler WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: etkinlikler.php?mesaj=silindi");
    exit;
}

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

// Toplam kayıt sayısı
if (!empty($aranan)) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.baslik LIKE ? OR k.ad LIKE ? OR e.konum LIKE ?");
    $count_stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM etkinlikler");
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Verileri getir
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
if (!empty($aranan)) {
    $stmt = $db->prepare("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.baslik LIKE ? OR k.ad LIKE ? OR e.konum LIKE ? ORDER BY e.tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $stmt = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id ORDER BY e.tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
}
$etkinlikler = $stmt->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <form action="" method="GET" style="display: flex; gap: 0.5rem;">
        <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Etkinlik, kulüp veya konum ara..." 
               style="padding: 0.6rem 1rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1); background: #1e293b; color: white; outline: none; font-size: 0.875rem; width: 280px;">
        <button type="submit" class="btn-sm btn-primary" style="padding: 0.6rem 1.25rem;">Ara</button>
        <?php if ($aranan): ?>
            <a href="etkinlikler.php" class="btn-sm btn-danger" style="padding: 0.6rem 1rem;">Temizle</a>
        <?php endif; ?>
    </form>
    <a href="etkinlik_ekle.php" class="btn btn-primary">Yeni Etkinlik Ekle</a>
</div>

<?php if ($aranan): ?>
    <div style="color: #94a3b8; margin-bottom: 1rem; font-size: 0.875rem;">
        "<strong><?php echo htmlspecialchars($aranan); ?></strong>" için <?php echo $kayit_sayisi; ?> sonuç bulundu.
    </div>
<?php endif; ?>

<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Etkinlik başarıyla silindi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'guncellendi'): ?>
    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Etkinlik başarıyla güncellendi.
    </div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Başlık</th>
                <th>Kulüp</th>
                <th>Tarih</th>
                <th>Konum</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etkinlikler as $etkinlik): ?>
                <tr>
                    <td><?php echo $etkinlik['id']; ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['baslik']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['kulup_adi'] ?? 'Genel'); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['tarih'])); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['konum']); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="etkinlik_duzenle.php?id=<?php echo $etkinlik['id']; ?>" class="btn-sm btn-primary">Düzelt</a>
                            <a href="etkinlikler.php?sil=<?php echo $etkinlik['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Bu etkinliği silmek istediğinize emin misiniz?')">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($etkinlikler)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">
                        <?php echo $aranan ? 'Aramanızla eşleşen etkinlik bulunamadı.' : 'Henüz kayıtlı etkinlik yok.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'etkinlikler.php';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
