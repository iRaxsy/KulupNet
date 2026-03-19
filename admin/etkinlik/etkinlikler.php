<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

// Silme işlemi
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    $stmt = $db->prepare("DELETE FROM etkinlikler WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: etkinlikler.php?mesaj=silindi");
    exit;
}

$page_title = 'Etkinlikler - Admin Paneli';
$header_title = 'Etkinlik Yönetimi';
$active_page = 'etkinlikler';
require_once '../header.php';

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sıralama
$izinli_sutunlar = ['e.id', 'e.baslik', 'k.ad', 'e.tarih'];
$izinli_yonler = ['ASC', 'DESC'];

$siralama = $_GET['siralama'] ?? 'e.tarih';
$yon = strtoupper($_GET['yon'] ?? 'DESC');

if (!in_array($siralama, $izinli_sutunlar)) $siralama = 'e.tarih';
if (!in_array($yon, $izinli_yonler)) $yon = 'DESC';

// Ters yönü hesapla
$ters_yon = ($yon == 'ASC') ? 'DESC' : 'ASC';

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

// Toplam kayıt sayısı
if (!empty($aranan)) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.baslik LIKE ? OR k.ad LIKE ? OR e.konum LIKE ?");
    $count_stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM etkinlikler");
}
$kayit_sayisi = $count_stmt->fetchColumn();

// Verileri getir
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
$order_sql = "ORDER BY $siralama $yon";

if (!empty($aranan)) {
    $stmt = $db->prepare("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.baslik LIKE ? OR k.ad LIKE ? OR e.konum LIKE ? $order_sql LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute(["%$aranan%", "%$aranan%", "%$aranan%"]);
} else {
    $stmt = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id $order_sql LIMIT $sayfa_kayit_sayisi OFFSET $offset");
}
$etkinlikler = $stmt->fetchAll();

// Sıralama linki oluşturucu
function siralama_link($sutun, $etiket, $mevcut_siralama, $mevcut_yon, $aranan) {
    $yon = ($mevcut_siralama == $sutun && $mevcut_yon == 'ASC') ? 'DESC' : 'ASC';
    $ok = '';
    if ($mevcut_siralama == $sutun) {
        $ok = ($mevcut_yon == 'ASC') ? ' ▲' : ' ▼';
    }
    $url = "etkinlikler.php?siralama=" . urlencode($sutun) . "&yon=$yon";
    if ($aranan) $url .= "&aranan=" . urlencode($aranan);
    return "<a href='$url'>$etiket$ok</a>";
}

// Türkçe ay isimleri
$aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
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
        width: 300px;
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

    /* Alert Toasts */
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

    /* Modern Table Panel */
    .table-panel {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.15s;
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

    /* Row styles for active/ended */
    .table-panel tbody tr.row-ended {
        opacity: 0.5;
    }

    .table-panel tbody tr.row-active {
        border-left: 3px solid #10b981;
    }

    .table-panel tbody tr.row-ended {
        border-left: 3px solid rgba(239, 68, 68, 0.3);
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

    /* Event Title Cell */
    .event-name {
        font-weight: 600;
        color: #e2e8f0;
    }

    /* Club Badge */
    .club-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
        padding: 0.3rem 0.7rem;
        border-radius: 0.5rem;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .club-badge svg {
        width: 13px;
        height: 13px;
    }

    /* Location display */
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

    /* Date-time display */
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

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
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

    /* Action Buttons */
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

    .action-btn svg {
        width: 14px;
        height: 14px;
    }

    .action-btn.btn-edit {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.2);
    }

    .action-btn.btn-edit:hover {
        background: rgba(59, 130, 246, 0.2);
        transform: translateY(-1px);
    }

    .action-btn.btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.2);
    }

    .action-btn.btn-delete:hover {
        background: rgba(239, 68, 68, 0.2);
        transform: translateY(-1px);
    }

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

    .empty-state .empty-icon svg {
        width: 28px;
        height: 28px;
        color: #475569;
    }

    .empty-state .empty-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .empty-state .empty-desc {
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
        .actions-cell {
            flex-wrap: wrap;
        }
    }
</style>

<!-- Toolbar: Search + Add -->
<div class="page-toolbar">
    <form action="" method="GET" class="search-form">
        <input type="hidden" name="siralama" value="<?php echo htmlspecialchars($siralama); ?>">
        <input type="hidden" name="yon" value="<?php echo htmlspecialchars($yon); ?>">
        <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="aranan" value="<?php echo htmlspecialchars($aranan); ?>" placeholder="Etkinlik, kulüp veya konum ara...">
        </div>
        <button type="submit" class="btn-modern btn-modern-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            Ara
        </button>
        <?php if ($aranan): ?>
            <a href="etkinlikler.php" class="btn-modern btn-modern-clear">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Temizle
            </a>
        <?php endif; ?>
    </form>
    <a href="etkinlik_ekle.php" class="btn-modern btn-modern-primary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Yeni Etkinlik Ekle
    </a>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
    <div class="alert-toast alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        Etkinlik başarıyla silindi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'guncellendi'): ?>
    <div class="alert-toast alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        Etkinlik başarıyla güncellendi.
    </div>
<?php endif; ?>
<?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'eklendi'): ?>
    <div class="alert-toast alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Yeni etkinlik başarıyla eklendi.
    </div>
<?php endif; ?>

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

<!-- Table -->
<div class="table-panel">
    <table>
        <thead>
            <tr>
                <th style="width: 70px;"><?php echo siralama_link('e.id', 'ID', $siralama, $yon, $aranan); ?></th>
                <th><?php echo siralama_link('e.baslik', 'Başlık', $siralama, $yon, $aranan); ?></th>
                <th><?php echo siralama_link('k.ad', 'Kulüp', $siralama, $yon, $aranan); ?></th>
                <th><?php echo siralama_link('e.tarih', 'Tarih', $siralama, $yon, $aranan); ?></th>
                <th>Konum</th>
                <th style="width: 90px;">Durum</th>
                <th style="width: 180px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etkinlikler as $etkinlik):
                $bitmis = strtotime($etkinlik['tarih']) < time();
                $ts = strtotime($etkinlik['tarih']);
                $gun = date('d', $ts);
                $ay = $aylar[(int)date('n', $ts) - 1];
                $saat = date('H:i', $ts);
                $tarih_str = date('d.m.Y', $ts);
            ?>
                <tr class="<?php echo $bitmis ? 'row-ended' : 'row-active'; ?>">
                    <td>
                        <span class="id-badge">#<?php echo $etkinlik['id']; ?></span>
                    </td>
                    <td>
                        <span class="event-name"><?php echo htmlspecialchars($etkinlik['baslik']); ?></span>
                    </td>
                    <td>
                        <span class="club-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <?php echo htmlspecialchars($etkinlik['kulup_adi'] ?? 'Genel'); ?>
                        </span>
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
                    <td>
                        <div class="actions-cell">
                            <a href="etkinlik_duzenle.php?id=<?php echo $etkinlik['id']; ?>" class="action-btn btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Düzenle
                            </a>
                            <a href="javascript:void(0);" class="action-btn btn-delete" onclick="confirmAction('etkinlikler.php?sil=<?php echo $etkinlik['id']; ?>', 'Etkinliği Sil', 'Bu etkinliği silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Sil
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($etkinlikler)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div class="empty-title">
                                <?php echo $aranan ? 'Sonuç Bulunamadı' : 'Henüz Etkinlik Yok'; ?>
                            </div>
                            <div class="empty-desc">
                                <?php echo $aranan ? 'Aramanızla eşleşen etkinlik bulunamadı.' : 'Yeni bir etkinlik ekleyerek başlayın.'; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$sayfa_url = 'etkinlikler.php';
$ekstra_params = 'siralama=' . urlencode($siralama) . '&yon=' . urlencode($yon);
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
