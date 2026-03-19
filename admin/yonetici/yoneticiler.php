<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

// Silme işlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    if ($id !== $_SESSION['admin_id']) { // Kendi kendini silmeyi engelle
        $stmt = $db->prepare("DELETE FROM adminler WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: yoneticiler.php?mesaj=silindi");
        exit;
    } else {
        header("Location: yoneticiler.php?mesaj=hata_kendi_hesabiniz");
        exit;
    }
}

$page_title = 'Yöneticiler - Admin Paneli';
$header_title = 'Yönetici Yönetimi';
$active_page = 'yoneticiler';
require_once '../header.php';

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sıralama
$yon = strtoupper($_GET['yon'] ?? 'DESC');
if (!in_array($yon, ['ASC', 'DESC'])) $yon = 'DESC';
$ters_yon = ($yon == 'ASC') ? 'DESC' : 'ASC';

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

// Toplam kayıt sayısı
if (!empty($aranan)) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM adminler WHERE kullanici_adi LIKE ?");
    $count_stmt->execute(["%$aranan%"]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM adminler");
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Verileri getir
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
if (!empty($aranan)) {
    $stmt = $db->prepare("SELECT id, kullanici_adi FROM adminler WHERE kullanici_adi LIKE ? ORDER BY id $yon LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute(["%$aranan%"]);
} else {
    $stmt = $db->query("SELECT id, kullanici_adi FROM adminler ORDER BY id $yon LIMIT $sayfa_kayit_sayisi OFFSET $offset");
}
$yoneticiler = $stmt->fetchAll();
?>

<style>
    /* Page Header Bar */
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

    /* Alert Messages */
    .alert-toast {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        margin-bottom: 1.25rem;
        font-size: 0.85rem;
        font-weight: 500;
        animation: slideInRight 0.4s ease forwards;
        backdrop-filter: blur(10px);
    }

    .alert-toast svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .alert-toast.alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .alert-toast.alert-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    /* Search Result Badge */
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

    /* Modern Table Panel */
    .table-panel {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
    }

    .table-panel table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-panel thead th {
        background: rgba(51, 65, 85, 0.5);
        padding: 1rem 1.25rem;
        text-align: left;
        color: #94a3b8;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .table-panel thead th a {
        color: #94a3b8;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: color 0.2s;
    }

    .table-panel thead th a:hover {
        color: #e2e8f0;
    }

    .table-panel tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        transition: background 0.2s ease;
    }

    .table-panel tbody tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .table-panel tbody td {
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        color: #cbd5e1;
        vertical-align: middle;
    }

    .club-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .club-cell .club-avatar {
        width: 38px;
        height: 38px;
        border-radius: 0.625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .club-cell .club-name {
        font-weight: 600;
        color: #e2e8f0;
    }

    .id-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(99, 102, 241, 0.1);
        color: #818cf8;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.3rem 0.7rem;
        border-radius: 0.5rem;
        min-width: 28px;
    }

    .actions-cell {
        display: flex;
        gap: 0.4rem;
        align-items: center;
    }

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
    }

    .action-btn svg { width: 14px; height: 14px; }

    .action-btn.btn-edit {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.2);
    }

    .action-btn.btn-edit:hover { background: rgba(59, 130, 246, 0.2); transform: translateY(-1px); }

    .action-btn.btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.2);
    }

    .action-btn.btn-delete:hover { background: rgba(239, 68, 68, 0.2); transform: translateY(-1px); }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }

    .empty-state .empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        background: rgba(99, 102, 241, 0.1);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-state .empty-icon svg { width: 28px; height: 28px; color: #475569; }

    .empty-state .empty-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .empty-state .empty-desc { font-size: 0.8rem; color: #475569; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Toolbar -->
<div class="page-toolbar">
    <form action="" method="GET" class="search-form">
        <input type="hidden" name="yon" value="<?php echo htmlspecialchars($yon); ?>">
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Yönetici ara...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="yoneticiler.php" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php endif; ?>
    </form>
    <a href="yonetici_ekle.php" class="btn-modern btn-modern-primary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Yeni Yönetici Ekle
    </a>
</div>

<!-- Olay Mesajları -->
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div class="alert-toast alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        Yönetici başarıyla silindi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'eklendi'): ?>
    <div class="alert-toast alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Yeni yönetici başarıyla eklendi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'guncellendi'): ?>
    <div class="alert-toast alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        Yönetici başarıyla güncellendi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'hata_kendi_hesabiniz'): ?>
    <div class="alert-toast alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        Kendi hesabınızı silemezsiniz!
    </div>
<?php endif; ?>

<!-- Table -->
<div class="table-panel">
    <table>
        <thead>
            <tr>
                <th style="width: 70px;">
                    <?php
                    $id_ok = ($yon == 'ASC') ? ' ▲' : ' ▼';
                    $id_url = 'yoneticiler.php?yon=' . $ters_yon;
                    if ($aranan) $id_url .= '&aranan=' . urlencode($aranan);
                    ?>
                    <a href="<?php echo $id_url; ?>">ID<?php echo $id_ok; ?></a>
                </th>
                <th>Kullanıcı Adı</th>
                <th style="width: 220px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $renkler = [
                ['bg' => 'rgba(99, 102, 241, 0.15)', 'color' => '#818cf8'],
                ['bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#34d399'],
                ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#fbbf24']
            ];
            foreach ($yoneticiler as $i => $yonetici):
                $renk = $renkler[$i % count($renkler)];
                $harf = mb_strtoupper(mb_substr($yonetici['kullanici_adi'], 0, 1));
            ?>
                <tr>
                    <td>
                        <span class="id-badge">#<?php echo $yonetici['id']; ?></span>
                    </td>
                    <td>
                        <div class="club-cell">
                            <div class="club-avatar" style="background: <?php echo $renk['bg']; ?>; color: <?php echo $renk['color']; ?>;">
                                <?php echo $harf; ?>
                            </div>
                            <span class="club-name"><?php echo htmlspecialchars($yonetici['kullanici_adi']); ?></span>
                            <?php if ($yonetici['id'] == $_SESSION['admin_id']): ?>
                                <span style="margin-left:8px; font-size:0.7rem; color:#10b981; background:rgba(16,185,129,0.1); padding:0.15rem 0.4rem; border-radius:1rem;">Siz</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="yonetici_duzenle.php?id=<?php echo $yonetici['id']; ?>" class="action-btn btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Düzenle
                            </a>
                            <a href="javascript:void(0);" class="action-btn btn-delete" onclick="confirmAction('yoneticiler.php?sil=<?php echo $yonetici['id']; ?>', 'Yöneticiyi Sil', 'Bu yöneticiyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Sil
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($yoneticiler)): ?>
                <tr>
                    <td colspan="3">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                            <div class="empty-title">
                                <?php echo $aranan ? 'Sonuç Bulunamadı' : 'Henüz Yönetici Yok'; ?>
                            </div>
                            <div class="empty-desc">
                                <?php echo $aranan ? 'Aramanızla eşleşen yönetici bulunamadı.' : 'Yeni bir yönetici ekleyerek başlayın.'; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'yoneticiler.php';
$ekstra_params = 'yon=' . urlencode($yon);
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
