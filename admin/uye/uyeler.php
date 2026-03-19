<?php
$page_title = 'Üyeler - Admin Paneli';
$header_title = 'Üye Yönetimi';
$active_page = 'uyeler';
require_once '../header.php';

$kulup_id = (int)($_GET['kulup_id'] ?? 0);
$kulup_adi = '';
$aranan = trim($_GET['aranan'] ?? '');

if ($kulup_id > 0) {
    $stmt = $db->prepare("SELECT ad FROM kulupler WHERE id = ?");
    $stmt->execute([$kulup_id]);
    $kulup_adi = $stmt->fetchColumn();
    if ($kulup_adi) {
        $header_title = $kulup_adi . ' Üye Listesi';
    }
}

// Rol güncelle
if (isset($_POST['rol_guncelle'])) {
    $uye_id = (int)$_POST['uye_id'];
    $yeni_rol = $_POST['yeni_rol'];
    $gecerli_roller = ['Admin', 'Yetkili', 'Organizatör', 'Üye'];
    if (in_array($yeni_rol, $gecerli_roller)) {
        $stmt = $db->prepare("UPDATE kullanicilar SET rol = ? WHERE id = ?");
        $stmt->execute([$yeni_rol, $uye_id]);
        header("Location: uyeler.php?mesaj=guncellendi" . ($kulup_id ? "&kulup_id=$kulup_id" : "") . ($aranan ? "&aranan=" . urlencode($aranan) : ""));
        exit;
    }
}

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// WHERE Sorgusu Oluştur
$where_clauses = [];
$params = [];

if ($kulup_id > 0) {
    $where_clauses[] = "id IN (SELECT kullanici_id FROM kulup_uyelikleri WHERE kulup_id = ?)";
    $params[] = $kulup_id;
}

if (!empty($aranan)) {
    $where_clauses[] = "(ad LIKE ? OR soyad LIKE ? OR kullanici_adi LIKE ? OR eposta LIKE ?)";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Toplam kayıt sayısı
$count_stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar $where_sql");
$count_stmt->execute($params);
$kayit_sayisi = $count_stmt->fetchColumn();

// Üyeleri listele
$stmt = $db->prepare("
    SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi 
    FROM kullanicilar k 
    $where_sql 
    ORDER BY k.id ASC 
    LIMIT $sayfa_kayit_sayisi OFFSET $offset
");
$stmt->execute($params);
$uyeler = $stmt->fetchAll();

$rol_renkleri = [
    'Admin' => '#f87171',
    'Yetkili' => '#fbbf24',
    'Organizatör' => '#22d3ee',
    'Üye' => '#818cf8',
];
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
    }

    .search-result-info .result-count {
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
        font-weight: 700;
        padding: 0.15rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.75rem;
    }

    .role-select {
        padding: 0.35rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: #0f172a;
        color: #f1f5f9;
        font-size: 0.8rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        cursor: pointer;
    }
    .role-select:focus { border-color: #6366f1; }
    
    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.5rem;
        border: none;
        background: #6366f1;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .btn-save:hover { 
        background: #4f46e5;
        transform: translateY(-1px);
    }
    .btn-save svg { width: 14px; height: 14px; }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
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

    .filter-info { margin-bottom: 1rem; color: #94a3b8; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
    .btn-clear-filter { 
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #f87171; 
        text-decoration: none; 
        font-weight: 600; 
        background: rgba(239, 68, 68, 0.1); 
        padding: 0.35rem 0.75rem; 
        border-radius: 0.5rem; 
        font-size: 0.75rem; 
        border: 1px solid rgba(239, 68, 68, 0.2); 
        transition: all 0.2s; 
    }
    .btn-clear-filter:hover { 
        background: rgba(239, 68, 68, 0.2); 
        transform: translateY(-1px);
    }
    .btn-clear-filter svg { width: 14px; height: 14px; }
</style>

<div class="page-toolbar">
    <form action="" method="GET" class="search-form">
        <?php if ($kulup_id): ?>
            <input type="hidden" name="kulup_id" value="<?php echo $kulup_id; ?>">
        <?php endif; ?>
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Üye adı, e-posta veya kullanıcı adı...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="uyeler.php<?php echo $kulup_id ? "?kulup_id=$kulup_id" : ""; ?>" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php endif; ?>
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
<?php endif; ?>

<?php if ($kulup_id > 0 && $kulup_adi): ?>
<div class="filter-info">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;color:#818cf8;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
    </svg>
    <strong><?php echo htmlspecialchars($kulup_adi); ?></strong> kulübünün üyeleri gösteriliyor.
    <a href="uyeler.php<?php echo $aranan ? "?aranan=" . urlencode($aranan) : ""; ?>" class="btn-clear-filter">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
        Kulüp Filtresini Kaldır
    </a>
</div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Kulüp</th>
                <th>Cinsiyet</th>
                <th>Rol</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($uyeler)): ?>
                <?php foreach ($uyeler as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['ad'] . ' ' . $u['soyad']); ?></td>
                    <td><?php echo htmlspecialchars($u['kullanici_adi']); ?></td>
                    <td><?php echo htmlspecialchars($u['eposta']); ?></td>
                    <td><?php echo $u['kulup_sayisi']; ?> kulüp</td>
                    <td><?php echo ($u['cinsiyet'] === 'Kadin') ? 'Kadın' : htmlspecialchars($u['cinsiyet']); ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:0.5rem;align-items:center;">
                            <input type="hidden" name="uye_id" value="<?php echo $u['id']; ?>">
                            <select name="yeni_rol" class="role-select">
                                <?php foreach (['Admin', 'Yetkili', 'Organizatör', 'Üye'] as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo $u['rol'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="rol_guncelle" value="1" class="btn-save">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Kaydet
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="uye_detay.php?id=<?php echo $u['id']; ?>" class="action-btn btn-view">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            Detay
                        </a>
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
                            <div style="font-weight:600;"><?php echo $aranan ? 'Sonuç Bulunamadı' : 'Henüz Üye Yok'; ?></div>
                            <div style="font-size:0.85rem; margin-top:0.25rem;"><?php echo $aranan ? 'Aramanızla eşleşen üye bulunamadı.' : 'Henüz kayıtlı üye bulunmuyor.'; ?></div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'uyeler.php';
$ekstra_params = ($kulup_id > 0 ? 'kulup_id=' . $kulup_id : '') . ($aranan ? ($kulup_id > 0 ? '&' : '') . 'aranan=' . urlencode($aranan) : '');
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
