<?php
$page_title = 'Toplantılar - Admin Paneli';
$header_title = 'Toplantı Yönetimi';
$active_page = 'toplantilar';
require_once '../header.php';

// Tabloyu otomatik oluştur
$db->exec("
    CREATE TABLE IF NOT EXISTS toplantilar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kulup_id INT NOT NULL,
        konu VARCHAR(200) NOT NULL,
        aciklama TEXT,
        tarih DATETIME NOT NULL,
        konum VARCHAR(200) NOT NULL,
        hedef_rol VARCHAR(100) NOT NULL DEFAULT 'Herkes',
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Sil
if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $db->prepare("DELETE FROM toplantilar WHERE id = ?")->execute([$sil_id]);
    header("Location: toplantilar.php?mesaj=silindi");
    exit;
}

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sayfalama
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Toplam kayıt sayısı (Aramaya göre)
if (!empty($aranan)) {
    $count_stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM toplantilar t 
        JOIN kulupler k ON t.kulup_id = k.id 
        WHERE t.konu LIKE ? OR t.aciklama LIKE ? OR t.konum LIKE ? OR k.ad LIKE ?
    ");
    $count_stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM toplantilar");
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Kayıtlar (Aramaya göre ve Sayfalı)
if (!empty($aranan)) {
    $stmt = $db->prepare("
        SELECT t.*, k.ad as kulup_adi 
        FROM toplantilar t 
        JOIN kulupler k ON t.kulup_id = k.id 
        WHERE t.konu LIKE ? OR t.aciklama LIKE ? OR t.konum LIKE ? OR k.ad LIKE ?
        ORDER BY t.tarih DESC 
        LIMIT $sayfa_kayit_sayisi OFFSET $offset
    ");
    $stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%", "%$aranan%"]);
    $kayitlar = $stmt->fetchAll();
} else {
    $kayitlar = $db->query("
        SELECT t.*, k.ad as kulup_adi 
        FROM toplantilar t 
        JOIN kulupler k ON t.kulup_id = k.id 
        ORDER BY t.tarih DESC 
        LIMIT $sayfa_kayit_sayisi OFFSET $offset
    ")->fetchAll();
}

$rol_renkleri = [
    'Herkes' => ['bg' => 'rgba(99,102,241,0.15)', 'color' => '#818cf8'],
    'Yönetici' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Başkan' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Başkan Yardımcısı' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
];
?>

<style>
    /* Exact styles from kulupler.php */
    .page-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.75rem;
        animation: fadeInUp 0.5s ease forwards;
    }

    .search-form {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper .search-icon {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: #475569;
        pointer-events: none;
    }

    .search-input-wrapper input {
        padding: 0.65rem 1rem 0.65rem 2.5rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(30, 41, 59, 0.8);
        backdrop-filter: blur(10px);
        color: #f1f5f9;
        outline: none;
        font-size: 0.85rem;
        font-family: 'Inter', sans-serif;
        width: 260px;
        transition: all 0.3s ease;
    }

    .search-input-wrapper input::placeholder {
        color: #475569;
    }

    .search-input-wrapper input:focus {
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background: rgba(30, 41, 59, 1);
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 1.25rem;
        border-radius: 0.625rem;
        font-size: 0.8rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-modern svg {
        width: 16px;
        height: 16px;
    }

    .btn-modern-primary {
        background: linear-gradient(135deg, #6366f1, #7c3aed);
        color: white;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
    }

    .btn-modern-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(99, 102, 241, 0.4);
    }

    .btn-modern-search {
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .btn-modern-search:hover {
        background: rgba(99, 102, 241, 0.25);
    }

    .btn-modern-clear {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-modern-clear:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    /* Table styles */
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
    .btn-delete { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2); }
    .btn-delete:hover { background: rgba(239, 68, 68, 0.2); transform: translateY(-1px); }
    .btn-edit { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
    .btn-edit:hover { background: rgba(16,185,129,0.2); transform: translateY(-1px); }
    .btn-view { background: rgba(99, 102, 241, 0.1); color: #818cf8; border-color: rgba(99, 102, 241, 0.2); }
    .btn-view:hover { background: rgba(99,102,241,0.2); transform: translateY(-1px); }
    .btn-users { background: rgba(6, 182, 212, 0.1); color: #22d3ee; border-color: rgba(6, 182, 212, 0.2); }
    .btn-users:hover { background: rgba(6, 182, 212, 0.2); transform: translateY(-1px); }

    .role-badge { padding: 0.15rem 0.5rem; border-radius: 1rem; font-size: 0.65rem; font-weight: 600; display: inline-block; margin: 0.1rem; }
    .konu-cell { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .status-badge { padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.65rem; font-weight: 600; }
    .status-yaklasan { background: rgba(16,185,129,0.15); color: #34d399; }
    .status-gecmis { background: rgba(148,163,184,0.15); color: #94a3b8; }
    
    .search-result-info {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #94a3b8;
        font-size: 0.825rem;
        margin-bottom: 1.25rem;
        animation: fadeInUp 0.4s ease forwards;
    }

    .search-result-info .result-count {
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
        font-weight: 700;
        padding: 0.15rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.75rem;
    }

    .table-container {
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Toolbar: Search + Add (Exact structure from kulupler.php) -->
<div class="page-toolbar">
    <form action="" method="GET" class="search-form">
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Toplantı veya kulüp ara...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="toplantilar.php" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php endif; ?>
    </form>
    <a href="toplanti_ekle.php" class="btn-modern btn-modern-primary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Yeni Toplantı Oluştur
    </a>
</div>

<!-- Search Result Info -->
<?php if ($aranan): ?>
    <div class="search-result-info">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        "<strong><?php echo htmlspecialchars($aranan); ?></strong>" için
        <span class="result-count"><?php echo $kayit_sayisi; ?></span>
        sonuç bulundu
    </div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kulüp</th>
                <th>Konu</th>
                <th>Tarih</th>
                <th>Konum</th>
                <th>Hedef Rol</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kayitlar)): ?>
                <?php foreach ($kayitlar as $k): 
                    $gecmis_mi = strtotime($k['tarih']) < time();
                    $roller = explode(',', $k['hedef_rol']);
                ?>
                <tr>
                    <td><?php echo $k['id']; ?></td>
                    <td style="color:#818cf8; font-weight:600;"><?php echo htmlspecialchars($k['kulup_adi']); ?></td>
                    <td class="konu-cell" title="<?php echo htmlspecialchars($k['konu']); ?>"><?php echo htmlspecialchars($k['konu']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($k['tarih'])); ?></td>
                    <td><?php echo htmlspecialchars($k['konum']); ?></td>
                    <td>
                        <?php foreach($roller as $r): 
                            $r = trim($r);
                            $rc = $rol_renkleri[$r] ?? $rol_renkleri['Herkes'];
                        ?>
                            <span class="role-badge" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>;"><?php echo $r; ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $gecmis_mi ? 'status-gecmis' : 'status-yaklasan'; ?>">
                            <?php echo $gecmis_mi ? 'Bitti' : 'Aktif'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="toplanti_detay.php?id=<?php echo $k['id']; ?>" class="action-btn btn-view">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Detay
                            </a>
                            <a href="toplanti_katilanlar.php?id=<?php echo $k['id']; ?>" class="action-btn btn-users">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                Katılanlar
                            </a>
                            <a href="toplanti_duzenle.php?id=<?php echo $k['id']; ?>" class="action-btn btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Düzenle
                            </a>
                            <a href="javascript:void(0);" class="action-btn btn-delete" onclick="confirmAction('?sil=<?php echo $k['id']; ?>', 'Silmek istediğinize emin misiniz?', 'Bu toplantı kalıcı olarak silinecektir.', 'warning', 'Evet, Sil', 'Vazgeç')">
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
                <tr>
                    <td colspan="8">
                        <div style="text-align:center; padding:3rem; color:#64748b;">
                            <div style="margin-bottom:1rem; opacity:0.5;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:48px;height:48px;margin:0 auto;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <div style="font-weight:600;"><?php echo $aranan ? 'Sonuç Bulunamadı' : 'Henüz Toplantı Yok'; ?></div>
                            <div style="font-size:0.85rem; margin-top:0.25rem;"><?php echo $aranan ? 'Aramanızla eşleşen toplantı bulunamadı.' : 'Yeni bir toplantı oluşturarak başlayın.'; ?></div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'toplantilar.php';
$ekstra_params = $aranan ? 'aranan=' . urlencode($aranan) : '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
