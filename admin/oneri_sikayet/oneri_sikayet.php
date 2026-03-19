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
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .action-btn svg { width: 14px; height: 14px; }
    .btn-view {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.2);
    }
    .btn-view:hover {
        background: rgba(59, 130, 246, 0.2);
        transform: translateY(-1px);
    }
    .btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.2);
    }
    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.2);
        transform: translateY(-1px);
    }
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
                        <div class="action-btns" style="display:flex; gap:0.5rem; justify-content:flex-start;">
                            <a href="oneri_sikayet_detay.php?id=<?php echo $k['id']; ?>" class="action-btn btn-view">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Detay
                            </a>
                            <a href="javascript:void(0);" class="action-btn btn-delete" onclick="confirmAction('?sil=<?php echo $k['id']; ?>', 'Silmek istediğinize emin misiniz?', 'Bu Öneri/Şikayet kalıcı olarak silinecektir.', 'warning', 'Evet, Sil', 'Vazgeç')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Sil
                            </a>
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
