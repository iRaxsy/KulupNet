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
                        <div class="action-btns">
                            <a href="javascript:void(0);" class="btn-sm btn-danger" onclick="confirmAction('?sil=<?php echo $k['id']; ?>', 'Silmek istediğinize emin misiniz?', 'Bu duyuru kalıcı olarak silinecektir.', 'warning', 'Evet, Sil', 'Vazgeç')">Sil</a>
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
