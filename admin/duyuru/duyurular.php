<?php
$page_title = 'Duyurular - Admin Paneli';
$header_title = 'Duyuru Yönetimi';
$active_page = 'duyurular';
require_once '../header.php';

// Sil
if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $db->prepare("DELETE FROM duyurular WHERE id = ?")->execute([$sil_id]);
    header("Location: duyurular.php?mesaj=silindi");
    exit;
}

// İstatistikler
$toplam = $db->query("SELECT COUNT(*) FROM duyurular")->fetchColumn();
$site_sayisi = $db->query("SELECT COUNT(*) FROM duyurular WHERE kulup_id IS NULL")->fetchColumn();
$kulup_sayisi = $db->query("SELECT COUNT(*) FROM duyurular WHERE kulup_id IS NOT NULL")->fetchColumn();

// Sayfalama
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$kayit_sayisi = $toplam;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Kayıtlar
$kayitlar = $db->query("SELECT * FROM duyurular ORDER BY tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset")->fetchAll();
?>

<style>
    .type-badge {
        padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600;
        display: inline-block; white-space: nowrap;
    }
    .type-site { background: rgba(99,102,241,0.15); color: #818cf8; }
    .type-kulup { background: rgba(16,185,129,0.15); color: #34d399; }
    .duyuru-cell {
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
    .btn-edit {
        background: rgba(245, 158, 11, 0.1);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.2);
    }
    .btn-edit:hover {
        background: rgba(245, 158, 11, 0.2);
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
        <div class="stat-lbl">Toplam Duyuru</div>
    </div>
    <div class="mini-stat">
        <div class="stat-val" style="color:#a78bfa;"><?php echo $site_sayisi; ?></div>
        <div class="stat-lbl">Site Duyurusu</div>
    </div>
    <div class="mini-stat">
        <div class="stat-val" style="color:#34d399;"><?php echo $kulup_sayisi; ?></div>
        <div class="stat-lbl">Kulüp Duyurusu</div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tür</th>
                <th>Duyuru</th>
                <th>Tarih</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kayitlar)): ?>
                <?php foreach ($kayitlar as $k): ?>
                <tr>
                    <td><?php echo $k['id']; ?></td>
                    <td><span class="type-badge <?php echo $k['kulup_id'] ? 'type-kulup' : 'type-site'; ?>"><?php echo htmlspecialchars($k['tur']); ?></span></td>
                    <td class="duyuru-cell" title="<?php echo htmlspecialchars($k['duyuru']); ?>"><?php echo htmlspecialchars($k['duyuru']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($k['tarih'])); ?></td>
                    <td>
                        <div class="action-btns" style="display:flex; gap:0.5rem; justify-content:flex-start;">
                            <a href="duyuru_detay.php?id=<?php echo $k['id']; ?>" class="action-btn btn-view">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Detay
                            </a>
                            <a href="duyuru_duzenle.php?id=<?php echo $k['id']; ?>" class="action-btn btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Düzenle
                            </a>
                            <a href="javascript:void(0);" class="action-btn btn-delete" onclick="confirmAction('?sil=<?php echo $k['id']; ?>', 'Silmek istediğinize emin misiniz?', 'Bu duyuru kalıcı olarak silinecektir.', 'warning', 'Evet, Sil', 'Vazgeç')">
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
                <tr><td colspan="5" style="text-align:center;color:#64748b;padding:2rem;">Henüz duyuru bulunmuyor.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'duyurular.php';
$ekstra_params = '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
