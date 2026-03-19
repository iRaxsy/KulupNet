<?php
$page_title = 'Öneri/Şikayet - Admin Paneli';
$header_title = 'Öneri & Şikayet Yönetimi';
$active_page = 'oneri_sikayet';
require_once '../header.php';

// Sil
if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $db->prepare("DELETE FROM oneri_sikayet WHERE id = ?")->execute([$sil_id]);
    header("Location: oneri_sikayet.php?mesaj=silindi");
    exit;
}

// İstatistikler (Tümü üzerinden)
$toplam = $db->query("SELECT COUNT(*) FROM oneri_sikayet")->fetchColumn();
$oneri_sayisi = $db->query("SELECT COUNT(*) FROM oneri_sikayet WHERE tur = 'Öneri'")->fetchColumn();
$sikayet_sayisi = $db->query("SELECT COUNT(*) FROM oneri_sikayet WHERE tur = 'Şikayet'")->fetchColumn();

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$kayit_sayisi = $toplam;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Bu sayfanın kayıtları
$kayitlar = $db->query("SELECT os.*, k.ad, k.soyad, k.kullanici_adi FROM oneri_sikayet os JOIN kullanicilar k ON os.kullanici_id = k.id ORDER BY os.tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset")->fetchAll();
?>

<style>
    .type-badge {
        padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600;
        display: inline-block;
    }
    .type-oneri { background: rgba(16,185,129,0.15); color: #34d399; }
    .type-sikayet { background: rgba(239,68,68,0.15); color: #f87171; }
    .aciklama-cell {
        max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .stats-row {
        display: flex; gap: 1rem; margin-bottom: 1.5rem;
    }
    .mini-stat {
        background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.75rem; padding: 1rem 1.5rem; flex: 1; text-align: center;
    }
    .mini-stat .stat-val { font-size: 1.5rem; font-weight: 700; }
    .mini-stat .stat-lbl { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; }
</style>



<div class="stats-row">
    <div class="mini-stat">
        <div class="stat-val" style="color:#818cf8;"><?php echo $toplam; ?></div>
        <div class="stat-lbl">Toplam</div>
    </div>
    <div class="mini-stat">
        <div class="stat-val" style="color:#34d399;"><?php echo $oneri_sayisi; ?></div>
        <div class="stat-lbl">Öneri</div>
    </div>
    <div class="mini-stat">
        <div class="stat-val" style="color:#f87171;"><?php echo $sikayet_sayisi; ?></div>
        <div class="stat-lbl">Şikayet</div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tür</th>
                <th>Kullanıcı</th>
                <th>Açıklama</th>
                <th>Tarih</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kayitlar)): ?>
                <?php foreach ($kayitlar as $k): ?>
                <tr>
                    <td><?php echo $k['id']; ?></td>
                    <td><span class="type-badge <?php echo $k['tur'] === 'Öneri' ? 'type-oneri' : 'type-sikayet'; ?>"><?php echo htmlspecialchars($k['tur']); ?></span></td>
                    <td><?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?><br><small style="color:#64748b;">@<?php echo htmlspecialchars($k['kullanici_adi']); ?></small></td>
                    <td class="aciklama-cell" title="<?php echo htmlspecialchars($k['aciklama']); ?>"><?php echo htmlspecialchars($k['aciklama']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($k['tarih'])); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="javascript:void(0);" class="btn-sm btn-danger" onclick="confirmAction('?sil=<?php echo $k['id']; ?>', 'Silmek istediğinize emin misiniz?', 'Bu Öneri/Şikayet kalıcı olarak silinecektir.', 'warning', 'Evet, Sil', 'Vazgeç')">Sil</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;color:#64748b;padding:2rem;">Henüz öneri veya şikayet bulunmuyor.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'oneri_sikayet.php';
$ekstra_params = '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
