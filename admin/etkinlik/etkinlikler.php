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

$etkinlikler = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id ORDER BY e.tarih DESC")->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
    <a href="etkinlik_ekle.php" class="btn btn-primary">Yeni Etkinlik Ekle</a>
</div>

<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Etkinlik başarıyla silindi.
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
                            <a href="etkinlikler.php?sil=<?php echo $etkinlik['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Bu etkinliği silmek istediğinize emin misiniz?')">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($etkinlikler)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">Henüz kayıtlı etkinlik yok.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../footer.php'; ?>
