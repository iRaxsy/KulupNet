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

$kulupler = $db->query("SELECT * FROM kulupler ORDER BY id DESC")->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
    <a href="kulup_ekle.php" class="btn btn-primary">Yeni Kulüp Ekle</a>
</div>

<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        Kulüp başarıyla silindi.
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
                            <a href="kulupler.php?sil=<?php echo $kulup['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Bu kulübü silmek istediğinize emin misiniz?')">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($kulupler)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: #94a3b8;">Henüz kayıtlı kulüp yok.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../footer.php'; ?>
