<?php
$page_title = 'Yasaklı Kullanıcılar - Admin Paneli';
$header_title = 'Yasaklı Kullanıcı Yönetimi';
$active_page = 'yasaklilar';
require_once '../header.php';

// Yasağı Kaldır İşlemi
if (isset($_POST['yasagi_kaldir'])) {
    $uye_id = (int)$_POST['uye_id'];
    $stmt = $db->prepare("UPDATE kullanicilar SET durum = 'Aktif' WHERE id = ?");
    $stmt->execute([$uye_id]);
    header("Location: yasaklilar.php?mesaj=guncellendi");
    exit;
}

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

$count_stmt = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE durum = 'Yasakli'");
$kayit_sayisi = $count_stmt->fetchColumn();
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Üyeleri listele
$uyeler = $db->query("
    SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi 
    FROM kullanicilar k 
    WHERE k.durum = 'Yasakli'
    ORDER BY k.id DESC 
    LIMIT $sayfa_kayit_sayisi OFFSET $offset
")->fetchAll();

$toplam_sayfa = ceil($kayit_sayisi / $sayfa_kayit_sayisi);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2 style="color: #f1f5f9; font-size: 1.25rem;">
        Sistemden Yasaklanan Tüm Kullanıcılar
    </h2>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>Rol</th>
                <th>Kulüp S.</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($uyeler)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">
                        Şu anda sistemde yasaklı hiçbir kullanıcı bulunmuyor.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($uyeler as $uye): ?>
                    <tr>
                        <td>#<?php echo $uye['id']; ?></td>
                        <td>
                            <div style="font-weight: 600; color: #f1f5f9;">
                                <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #ef4444; font-weight: 500; margin-top: 0.2rem;">Yasaklı</div>
                        </td>
                        <td>@<?php echo htmlspecialchars($uye['kullanici_adi']); ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; 
                                <?php echo $uye['rol'] == 'Admin' ? 'background: rgba(239, 68, 68, 0.1); color: #f87171;' : 'background: rgba(148, 163, 184, 0.1); color: #94a3b8;'; ?>">
                                <?php echo htmlspecialchars($uye['rol']); ?>
                            </span>
                        </td>
                        <td><?php echo $uye['kulup_sayisi']; ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="../../profil.php?id=<?php echo $uye['id']; ?>" class="btn-sm btn-primary">Profile Git</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirmForm(this, 'Yasağı Kaldır', 'Bu kullanıcının yasağını kaldırmak istediğinize emin misiniz?', 'question', 'Evet, Kaldır', 'İptal')">
                                    <input type="hidden" name="uye_id" value="<?php echo $uye['id']; ?>">
                                    <input type="hidden" name="yasagi_kaldir" value="1">
                                    <button type="submit" class="btn-sm" style="background: #10b981; color: white; border: none;">Yasağı Kaldır</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'yasaklilar.php';
$ekstra_params = '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
