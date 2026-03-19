<?php
$page_title = 'Kulüp Etkinlikleri - Admin Paneli';
$active_page = 'kulupler';

if (!isset($_GET['kulup_id'])) {
    header("Location: kulupler.php");
    exit;
}

$kulup_id = (int)$_GET['kulup_id'];

require_once '../header.php';

// Kulüp bilgisini getir
$stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
$stmt->execute([$kulup_id]);
$kulup = $stmt->fetch();

if (!$kulup) {
    header("Location: kulupler.php");
    exit;
}

$header_title = htmlspecialchars($kulup['ad']) . ' - Etkinlikler';

// Kulübe ait etkinlikleri getir
$stmt = $db->prepare("SELECT * FROM etkinlikler WHERE kulup_id = ? ORDER BY tarih DESC");
$stmt->execute([$kulup_id]);
$etkinlikler = $stmt->fetchAll();
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="margin: 0; font-size: 1.25rem;"><?php echo htmlspecialchars($kulup['ad']); ?> Kulübünün Etkinlikleri</h2>
    <a href="kulupler.php" class="btn-sm btn-primary">← Kulüplere Dön</a>
</div>

<?php if (empty($etkinlikler)): ?>
    <div style="background: #1e293b; padding: 2rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); text-align: center; color: #94a3b8;">
        Bu kulübe ait henüz etkinlik bulunmuyor.
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Başlık</th>
                    <th>Tarih</th>
                    <th>Konum</th>
                    <th>Açıklama</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etkinlikler as $etkinlik): 
                    $bitmis = strtotime($etkinlik['tarih']) < time();
                ?>
                    <tr style="<?php echo $bitmis ? 'opacity: 0.55; background: rgba(239, 68, 68, 0.05);' : 'background: rgba(16, 185, 129, 0.05);'; ?>">
                        <td><?php echo $etkinlik['id']; ?></td>
                        <td><?php echo htmlspecialchars($etkinlik['baslik']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['tarih'])); ?></td>
                        <td><?php echo htmlspecialchars($etkinlik['konum']); ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($etkinlik['aciklama'], 0, 80, "...")); ?></td>
                        <td>
                            <?php if ($bitmis): ?>
                                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">Bitti</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">Aktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../footer.php'; ?>
