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

$aranan = trim($_GET['aranan'] ?? '');

// WHERE Sorgusu
$where_sql = "WHERE k.durum = 'Yasakli'";
$params = [];
if (!empty($aranan)) {
    $where_sql .= " AND (k.ad LIKE ? OR k.soyad LIKE ? OR k.kullanici_adi LIKE ? OR k.id LIKE ?)";
    $params = ["%$aranan%", "%$aranan%", "%$aranan%", "%$aranan%"];
}

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

$count_stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar k $where_sql");
$count_stmt->execute($params);
$kayit_sayisi = $count_stmt->fetchColumn();

$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Üyeleri listele
$stmt = $db->prepare("
    SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi 
    FROM kullanicilar k 
    $where_sql
    ORDER BY k.id DESC 
    LIMIT $sayfa_kayit_sayisi OFFSET $offset
");
$stmt->execute($params);
$uyeler = $stmt->fetchAll();
?>

<style>
    /* Page Toolbar */
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
        width: 280px;
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

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.8rem;
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
        background: rgba(99,102,241,0.1);
        color: #818cf8;
        border-color: rgba(99,102,241,0.2);
    }
    .btn-view:hover {
        background: rgba(99,102,241,0.2);
        transform: translateY(-1px);
    }

    .btn-unban {
        background: rgba(16,185,129,0.1);
        color: #34d399;
        border-color: rgba(16,185,129,0.2);
    }
    .btn-unban:hover {
        background: rgba(16,185,129,0.2);
        transform: translateY(-1px);
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

<div class="page-toolbar">

    <form action="" method="GET" class="search-form">
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Ad, soyad veya kullanıcı adı...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="yasaklilar.php" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php
endif; ?>
    </form>
</div>

<?php if ($aranan): ?>
    <div class="search-result-info">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        "<strong><?php echo htmlspecialchars($aranan); ?></strong>" için
        <span class="result-count"><?php echo $kayit_sayisi; ?></span>
        sonuç bulundu
    </div>
<?php
endif; ?>

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
                    <td colspan="6">
                        <div style="text-align:center; padding:3rem; color:#64748b;">
                            <div style="margin-bottom:1rem; opacity:0.5;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:48px;height:48px;margin:0 auto;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <div style="font-weight:600;"><?php echo $aranan ? 'Sonuç Bulunamadı' : 'Yasaklı Kullanıcı Yok'; ?></div>
                            <div style="font-size:0.85rem; margin-top:0.25rem;"><?php echo $aranan ? 'Aramanızla eşleşen yasaklı kullanıcı bulunamadı.' : 'Şu anda sistemde yasaklı hiçbir kullanıcı bulunmuyor.'; ?></div>
                        </div>
                    </td>
                </tr>
            <?php
else: ?>
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
                            <div class="action-btns" style="display:flex; gap:0.5rem;">
                                <a href="../../profil.php?id=<?php echo $uye['id']; ?>" class="action-btn btn-view">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    Profil
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirmForm(this, 'Yasağı Kaldır', 'Bu kullanıcının yasağını kaldırmak istediğinize emin misiniz?', 'question', 'Evet, Kaldır', 'İptal')">
                                    <input type="hidden" name="uye_id" value="<?php echo $uye['id']; ?>">
                                    <input type="hidden" name="yasagi_kaldir" value="1">
                                    <button type="submit" class="action-btn btn-unban">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Yasağı Kaldır
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php
    endforeach; ?>
            <?php
endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'yasaklilar.php';
$ekstra_params = ($aranan ? 'aranan=' . urlencode($aranan) : '');
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
