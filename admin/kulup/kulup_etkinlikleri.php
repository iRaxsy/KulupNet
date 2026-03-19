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

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sıralama
$izinli_sutunlar = ['id', 'baslik', 'tarih'];
$izinli_yonler = ['ASC', 'DESC'];

$siralama = $_GET['siralama'] ?? 'tarih';
$yon = strtoupper($_GET['yon'] ?? 'DESC');

if (!in_array($siralama, $izinli_sutunlar)) $siralama = 'tarih';
if (!in_array($yon, $izinli_yonler)) $yon = 'DESC';

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

// Toplam kayıt sayısı
if (!empty($aranan)) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ? AND (baslik LIKE ? OR konum LIKE ? OR aciklama LIKE ?)");
    $count_stmt->execute([$kulup_id, "%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ?");
    $count_stmt->execute([$kulup_id]);
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Verileri getir
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
$order_sql = "$siralama $yon";
if (!empty($aranan)) {
    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE kulup_id = ? AND (baslik LIKE ? OR konum LIKE ? OR aciklama LIKE ?) ORDER BY $order_sql LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute([$kulup_id, "%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE kulup_id = ? ORDER BY $order_sql LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute([$kulup_id]);
}
$etkinlikler = $stmt->fetchAll();

// Sıralama linki oluşturucu
function siralama_link_ke($sutun, $etiket, $kulup_id, $mevcut_siralama, $mevcut_yon, $aranan) {
    $yon = ($mevcut_siralama == $sutun && $mevcut_yon == 'ASC') ? 'DESC' : 'ASC';
    $ok = '';
    if ($mevcut_siralama == $sutun) {
        $ok = ($mevcut_yon == 'ASC') ? ' ▲' : ' ▼';
    }
    $url = "kulup_etkinlikleri.php?kulup_id=$kulup_id&siralama=" . urlencode($sutun) . "&yon=$yon";
    if ($aranan) $url .= "&aranan=" . urlencode($aranan);
    return "<a href='$url' style='color: white; text-decoration: none;'>$etiket$ok</a>";
}
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <h2 style="margin: 0; font-size: 1.25rem;"><?php echo htmlspecialchars($kulup['ad']); ?> Kulübünün Etkinlikleri</h2>
    <a href="kulupler.php" class="btn-sm btn-primary">← Kulüplere Dön</a>
</div>

<div style="margin-bottom: 1.5rem;">
    <form action="" method="GET" style="display: flex; gap: 0.5rem;">
        <input type="hidden" name="kulup_id" value="<?php echo $kulup_id; ?>">
        <input type="hidden" name="siralama" value="<?php echo htmlspecialchars($siralama); ?>">
        <input type="hidden" name="yon" value="<?php echo htmlspecialchars($yon); ?>">
        <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Etkinlik ara..."
               style="padding: 0.6rem 1rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1); background: #1e293b; color: white; outline: none; font-size: 0.875rem; width: 250px;">
        <button type="submit" class="btn-sm btn-primary" style="padding: 0.6rem 1.25rem;">Ara</button>
        <?php if ($aranan): ?>
            <a href="kulup_etkinlikleri.php?kulup_id=<?php echo $kulup_id; ?>" class="btn-sm btn-danger" style="padding: 0.6rem 1rem;">Temizle</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($aranan): ?>
    <div style="color: #94a3b8; margin-bottom: 1rem; font-size: 0.875rem;">
        "<strong><?php echo htmlspecialchars($aranan); ?></strong>" için <?php echo $kayit_sayisi; ?> sonuç bulundu.
    </div>
<?php endif; ?>

<?php if (empty($etkinlikler)): ?>
    <div style="background: #1e293b; padding: 2rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); text-align: center; color: #94a3b8;">
        <?php echo $aranan ? 'Aramanızla eşleşen etkinlik bulunamadı.' : 'Bu kulübe ait henüz etkinlik bulunmuyor.'; ?>
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?php echo siralama_link_ke('id', 'ID', $kulup_id, $siralama, $yon, $aranan); ?></th>
                    <th><?php echo siralama_link_ke('baslik', 'Başlık', $kulup_id, $siralama, $yon, $aranan); ?></th>
                    <th><?php echo siralama_link_ke('tarih', 'Tarih', $kulup_id, $siralama, $yon, $aranan); ?></th>
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

    <?php
    $sayfa_url = 'kulup_etkinlikleri.php';
    $ekstra_params = 'kulup_id=' . $kulup_id . '&siralama=' . urlencode($siralama) . '&yon=' . urlencode($yon);
    require_once '../sayfalama.php';
    ?>
<?php endif; ?>

<?php require_once '../footer.php'; ?>
