<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

if (!isset($_GET['kulup_id'])) {
    header("Location: kulupler.php");
    exit;
}

$kulup_id = (int)$_GET['kulup_id'];

// Kulüp bilgisini getir
$stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
$stmt->execute([$kulup_id]);
$kulup = $stmt->fetch();

if (!$kulup) {
    header("Location: kulupler.php");
    exit;
}

$page_title = 'Kulüp Etkinlikleri - Admin Paneli';
$active_page = 'kulupler';
$header_title = htmlspecialchars($kulup['ad']) . ' - Etkinlikler';
require_once '../header.php';

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
    return "<a href='$url'>$etiket$ok</a>";
}
?>

<style>
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 1.25rem;
        border-radius: 0.625rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        color: #94a3b8;
        background: rgba(51, 65, 85, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: rgba(51, 65, 85, 0.8);
        color: #e2e8f0;
        transform: translateY(-1px);
    }

    .btn-back svg {
        width: 16px;
        height: 16px;
    }

    /* Page Toolbar */
    .page-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.1s;
        opacity: 0;
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

    .btn-modern svg {
        width: 16px;
        height: 16px;
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

    /* Search Result */
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

    /* Table Panel */
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

    .table-panel tbody tr:last-child {
        border-bottom: none;
    }

    .table-panel tbody td {
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        color: #cbd5e1;
        vertical-align: middle;
    }

    .table-panel tbody tr.row-ended {
        opacity: 0.5;
        border-left: 3px solid rgba(239, 68, 68, 0.3);
    }

    .table-panel tbody tr.row-active {
        border-left: 3px solid #10b981;
    }

    /* ID Badge */
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

    /* Event Name */
    .event-name {
        font-weight: 600;
        color: #e2e8f0;
    }

    /* Date/Time */
    .datetime-cell {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .datetime-cell .date-main {
        font-weight: 600;
        color: #e2e8f0;
        font-size: 0.825rem;
    }

    .datetime-cell .time-sub {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: #64748b;
        font-size: 0.75rem;
    }

    .datetime-cell .time-sub svg {
        width: 12px;
        height: 12px;
    }

    /* Location */
    .location-cell {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: #64748b;
        font-size: 0.8rem;
    }

    .location-cell svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        color: #475569;
    }

    /* Description */
    .desc-text {
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.4;
        max-width: 250px;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .status-badge svg {
        width: 12px;
        height: 12px;
    }

    .status-badge.status-active {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-badge.status-ended {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.15);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
    }

    .empty-state-card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 3rem 2rem;
        text-align: center;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
    }

    .empty-state .empty-icon,
    .empty-state-card .empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        background: rgba(99, 102, 241, 0.1);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-state .empty-icon svg,
    .empty-state-card .empty-icon svg {
        width: 28px;
        height: 28px;
        color: #475569;
    }

    .empty-state .empty-title,
    .empty-state-card .empty-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .empty-state .empty-desc,
    .empty-state-card .empty-desc {
        font-size: 0.8rem;
        color: #475569;
    }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        .search-input-wrapper input {
            width: 100%;
        }
        .table-panel {
            overflow-x: auto;
        }
    }
</style>

<!-- Search Toolbar -->
<div class="page-toolbar">
    <a href="kulupler.php" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
        Kulüplere Dön
    </a>
    <form action="" method="GET" class="search-form">
        <input type="hidden" name="kulup_id" value="<?php echo $kulup_id; ?>">
        <input type="hidden" name="siralama" value="<?php echo htmlspecialchars($siralama); ?>">
        <input type="hidden" name="yon" value="<?php echo htmlspecialchars($yon); ?>">
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Etkinlik ara...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="kulup_etkinlikleri.php?kulup_id=<?php echo $kulup_id; ?>" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Search Result -->
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

<!-- Content -->
<?php if (empty($etkinlikler)): ?>
    <div class="empty-state-card">
        <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>
        <div class="empty-title">
            <?php echo $aranan ? 'Sonuç Bulunamadı' : 'Henüz Etkinlik Yok'; ?>
        </div>
        <div class="empty-desc">
            <?php echo $aranan ? 'Aramanızla eşleşen etkinlik bulunamadı.' : 'Bu kulübe ait henüz etkinlik bulunmuyor.'; ?>
        </div>
    </div>
<?php else: ?>
    <div class="table-panel">
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;"><?php echo siralama_link_ke('id', 'ID', $kulup_id, $siralama, $yon, $aranan); ?></th>
                    <th><?php echo siralama_link_ke('baslik', 'Başlık', $kulup_id, $siralama, $yon, $aranan); ?></th>
                    <th><?php echo siralama_link_ke('tarih', 'Tarih', $kulup_id, $siralama, $yon, $aranan); ?></th>
                    <th>Konum</th>
                    <th>Açıklama</th>
                    <th style="width: 90px;">Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etkinlikler as $etkinlik):
                    $bitmis = strtotime($etkinlik['tarih']) < time();
                    $ts = strtotime($etkinlik['tarih']);
                    $tarih_str = date('d.m.Y', $ts);
                    $saat = date('H:i', $ts);
                ?>
                    <tr class="<?php echo $bitmis ? 'row-ended' : 'row-active'; ?>">
                        <td>
                            <span class="id-badge">#<?php echo $etkinlik['id']; ?></span>
                        </td>
                        <td>
                            <span class="event-name"><?php echo htmlspecialchars($etkinlik['baslik']); ?></span>
                        </td>
                        <td>
                            <div class="datetime-cell">
                                <span class="date-main"><?php echo $tarih_str; ?></span>
                                <span class="time-sub">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <?php echo $saat; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="location-cell">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <?php echo htmlspecialchars($etkinlik['konum']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="desc-text"><?php echo htmlspecialchars(mb_strimwidth($etkinlik['aciklama'], 0, 80, "...")); ?></span>
                        </td>
                        <td>
                            <?php if ($bitmis): ?>
                                <span class="status-badge status-ended">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Bitti
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-active">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Aktif
                                </span>
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
