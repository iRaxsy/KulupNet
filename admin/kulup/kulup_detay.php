<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

if (!isset($_GET['id'])) {
    header("Location: kulupler.php");
    exit;
}

$id = (int)$_GET['id'];

// Rol güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rol_guncelle'])) {
    $uye_id = (int)$_POST['uyelik_id'];
    $yeni_rol = $_POST['yeni_rol'];
    $gecerli_roller = ['Yönetici', 'Başkan', 'Başkan Yardımcısı', 'Üye'];
    if (in_array($yeni_rol, $gecerli_roller)) {
        $db->prepare("UPDATE kulup_uyelikleri SET kulup_rol = ? WHERE id = ? AND kulup_id = ?")->execute([$yeni_rol, $uye_id, $id]);
    }
    header("Location: kulup_detay.php?id=$id&mesaj=rol_guncellendi");
    exit;
}

// Get Club Info
$stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
$stmt->execute([$id]);
$kulup = $stmt->fetch();

if (!$kulup) {
    header("Location: kulupler.php?mesaj=bulunamadi");
    exit;
}

// Get Club Stats
// 1. Members count
$stmt = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = ?");
$stmt->execute([$id]);
$uye_sayisi = $stmt->fetchColumn();

// 2. Total events count
$stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ?");
$stmt->execute([$id]);
$toplam_etkinlik = $stmt->fetchColumn();

// 3. Upcoming events count
$stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ? AND tarih >= NOW()");
$stmt->execute([$id]);
$yaklasan_etkinlik = $stmt->fetchColumn();

// 4. Past events count
$gecmis_etkinlik = $toplam_etkinlik - $yaklasan_etkinlik;

// Get recently joined members
$limit = 5;
$uye_sayfa = isset($_GET['uye_sayfa']) ? max(1, (int)$_GET['uye_sayfa']) : 1;
$uye_offset = ($uye_sayfa - 1) * $limit;
$toplam_uye_sayfa = ceil($uye_sayisi / $limit);

$stmt = $db->prepare("
    SELECT k.id, k.ad, k.soyad, ku.id as uyelik_id, ku.kulup_rol, ku.katilma_tarihi 
    FROM kulup_uyelikleri ku 
    JOIN kullanicilar k ON ku.kullanici_id = k.id 
    WHERE ku.kulup_id = ? 
    ORDER BY FIELD(ku.kulup_rol, 'Yönetici','Başkan','Başkan Yardımcısı','Üye'), ku.katilma_tarihi DESC 
    LIMIT $limit OFFSET $uye_offset
");
$stmt->execute([$id]);
$son_uyeler = $stmt->fetchAll();

// PDF için tüm üyeleri çek
$tum_uyeler_stmt = $db->prepare("SELECT k.ad, k.soyad, k.kullanici_adi, ku.kulup_rol, ku.katilma_tarihi FROM kulup_uyelikleri ku JOIN kullanicilar k ON ku.kullanici_id = k.id WHERE ku.kulup_id = ? ORDER BY FIELD(ku.kulup_rol, 'Yönetici','Başkan','Başkan Yardımcısı','Üye'), ku.katilma_tarihi ASC");
$tum_uyeler_stmt->execute([$id]);
$tum_uyeler = $tum_uyeler_stmt->fetchAll();

$rol_renkleri = [
    'Yönetici' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Başkan' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Başkan Yardımcısı' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
];

// Get upcoming/recent events
$etk_sayfa = isset($_GET['etk_sayfa']) ? max(1, (int)$_GET['etk_sayfa']) : 1;
$etk_offset = ($etk_sayfa - 1) * $limit;
$toplam_etk_sayfa = ceil($toplam_etkinlik / $limit);

$stmt = $db->prepare("
    SELECT * 
    FROM etkinlikler 
    WHERE kulup_id = ? 
    ORDER BY tarih ASC 
    LIMIT $limit OFFSET $etk_offset
");
$stmt->execute([$id]);
$son_etkinlikler = $stmt->fetchAll();

$page_title = $kulup['ad'] . ' Detayları - Admin Paneli';
$header_title = 'Kulüp Yönetimi';
$active_page = 'kulupler';
require_once '../header.php';
?>

<style>
    .page-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        animation: fadeInUp 0.5s ease forwards;
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        border-radius: 0.625rem;
        font-size: 0.85rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-modern svg {
        width: 18px;
        height: 18px;
    }

    .btn-back {
        background: rgba(51, 65, 85, 0.5);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
    }

    .btn-back:hover {
        background: rgba(51, 65, 85, 0.8);
        color: white;
        transform: translateX(-3px);
    }

    .btn-edit {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .btn-edit:hover {
        background: rgba(59, 130, 246, 0.2);
        transform: translateY(-2px);
    }

    .grid-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.1s;
        opacity: 0;
    }

    @media (max-width: 992px) {
        .grid-container {
            grid-template-columns: 1fr;
        }
    }

    .panel {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 1.5rem;
        height: 100%;
    }

    .panel-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .panel-header h2 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #e2e8f0;
        margin: 0;
    }

    .panel-header svg {
        width: 24px;
        height: 24px;
        color: #818cf8;
    }

    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .info-item {
        background: rgba(15, 23, 42, 0.4);
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .info-label {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.4rem;
    }

    .info-value {
        font-size: 0.95rem;
        color: #f1f5f9;
        font-weight: 500;
        line-height: 1.5;
    }

    .club-desc {
        background: rgba(15, 23, 42, 0.4);
        padding: 1.25rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.04);
        margin-top: 1rem;
    }

    .club-desc p {
        color: #cbd5e1;
        font-size: 0.9rem;
        line-height: 1.6;
        margin: 0;
    }

    /* Stats Grid */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .stat-box {
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 0.75rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        background: rgba(255, 255, 255, 0.03);
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .stat-icon svg {
        width: 24px;
        height: 24px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #f1f5f9;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* Icon Colors */
    .icon-members { background: rgba(56, 189, 248, 0.1); color: #38bdf8; }
    .icon-events { background: rgba(99, 102, 241, 0.1); color: #818cf8; }
    .icon-upcoming { background: rgba(16, 185, 129, 0.1); color: #34d399; }
    .icon-past { background: rgba(148, 163, 184, 0.1); color: #94a3b8; }

    /* Lists */
    .list-container {
        margin-top: 2rem;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
        display: flex;
        flex-direction: column;
        min-height: 480px; /* Sabit yükseklik sağlamak için */
    }

    .list-header {
        padding: 1.25rem 1.5rem;
        background: rgba(15, 23, 42, 0.6);
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .list-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #e2e8f0;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .list-header h3 svg {
        width: 20px;
        height: 20px;
        color: #818cf8;
    }

    .list-body {
        padding: 0;
        flex: 1;
    }

    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        transition: background 0.2s ease;
    }

    .list-item:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .list-item:last-child {
        border-bottom: none;
    }

    .item-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .item-title {
        font-weight: 600;
        color: #e2e8f0;
        font-size: 0.95rem;
    }

    .item-meta {
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .item-meta span {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .item-meta svg {
        width: 12px;
        height: 12px;
    }

    .item-action {
        background: rgba(255, 255, 255, 0.05);
        color: #cbd5e1;
        padding: 0.4rem 0.8rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .item-action:hover {
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
    }

    .empty-list {
        padding: 2rem;
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Pagination Styles */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: rgba(15, 23, 42, 0.4);
        border-top: 1px solid rgba(255, 255, 255, 0.04);
    }

    .page-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        color: #94a3b8;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .page-link:hover:not(.disabled) {
        background: rgba(255, 255, 255, 0.1);
        color: #f1f5f9;
        border-color: rgba(255, 255, 255, 0.15);
    }

    .page-link.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .page-info {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }
</style>

<div class="page-toolbar">
    <a href="kulupler.php" class="btn-modern btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Kulüplere Dön
    </a>
    <div style="display: flex; gap: 0.75rem;">
        <button onclick="indirPDF()" class="btn-modern" style="background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); cursor: pointer; outline: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Üye Listesi İndir (PDF)
        </button>
        <a href="kulup_duzenle.php?id=<?php echo $kulup['id']; ?>" class="btn-modern btn-edit">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            Kulübü Düzenle
        </a>
    </div>
</div>

<div class="grid-container">
    <!-- Club Info Panel -->
    <div class="panel">
        <div class="panel-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <h2>Kulüp Bilgileri</h2>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Kulüp ID</div>
                <div class="info-value">#<?php echo htmlspecialchars($kulup['id']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Kulüp Adı</div>
                <div class="info-value" style="font-weight: 700; font-size: 1.1rem; color: #818cf8;">
                    <?php echo htmlspecialchars($kulup['ad']); ?>
                </div>
            </div>
        </div>
        
        <div class="club-desc">
            <div class="info-label">Açıklama</div>
            <p><?php echo nl2br(htmlspecialchars($kulup['aciklama'] ?: 'Açıklama bulunmuyor.')); ?></p>
        </div>
    </div>

    <!-- Statistics Panel -->
    <div class="panel">
        <div class="panel-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            <h2>İstatistikler</h2>
        </div>
        
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-icon icon-members">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($uye_sayisi); ?></div>
                <div class="stat-label">Toplam Üye</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon icon-events">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($toplam_etkinlik); ?></div>
                <div class="stat-label">Toplam Etkinlik</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon icon-upcoming">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($yaklasan_etkinlik); ?></div>
                <div class="stat-label">Yaklaşan Etkinlik</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon icon-past">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($gecmis_etkinlik); ?></div>
                <div class="stat-label">Geçmiş Etkinlik</div>
            </div>
        </div>
    </div>
</div>

<div class="grid-container">
    <!-- Recent Members List -->
    <div class="list-container">
        <div class="list-header">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Son Katılan Üyeler
            </h3>
            <a href="../uye/uyeler.php?kulup_id=<?php echo $kulup['id']; ?>" class="item-action">Tümünü Gör</a>
        </div>
        <div class="list-body">
            <?php if (empty($son_uyeler)): ?>
                <div class="empty-list">Henüz bu kulübe katılan üye bulunmamaktadır.</div>
            <?php else: ?>
                <?php foreach ($son_uyeler as $uye): 
                $rc = $rol_renkleri[$uye['kulup_rol']] ?? $rol_renkleri['Üye'];
            ?>
                    <div class="list-item">
                        <div class="item-info">
                            <span class="item-title" style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                                <span style="font-size: 0.6rem; padding: 0.15rem 0.5rem; border-radius: 1rem; font-weight: 600; background: <?php echo $rc['bg']; ?>; color: <?php echo $rc['color']; ?>;">
                                    <?php echo $uye['kulup_rol']; ?>
                                </span>
                            </span>
                            <span class="item-meta">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Katılım: <?php echo date('d.m.Y H:i', strtotime($uye['katilma_tarihi'])); ?>
                                </span>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <form method="POST" style="margin:0; display: flex; align-items: center; gap: 0.4rem;">
                                <input type="hidden" name="uyelik_id" value="<?php echo $uye['uyelik_id']; ?>">
                                <select name="yeni_rol" onchange="this.form.submit()" style="padding: 0.3rem 0.5rem; border-radius: 0.4rem; background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); color: #94a3b8; font-size: 0.7rem; font-family: 'Inter', sans-serif; cursor: pointer;">
                                    <?php foreach (['Üye','Başkan Yardımcısı','Başkan','Yönetici'] as $r): ?>
                                        <option value="<?php echo $r; ?>" <?php echo $uye['kulup_rol'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="rol_guncelle" value="1">
                            </form>
                            <a href="../uye/uye_detay.php?id=<?php echo $uye['id']; ?>" class="item-action">Detay</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="pagination-footer">
            <a href="?id=<?php echo $id; ?>&uye_sayfa=<?php echo $uye_sayfa - 1; ?>&etk_sayfa=<?php echo $etk_sayfa; ?>" 
               class="page-link <?php echo $uye_sayfa <= 1 ? 'disabled' : ''; ?>"
               <?php echo $uye_sayfa <= 1 ? 'onclick="return false;"' : ''; ?>>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Geri
            </a>
            <span class="page-info"><?php echo $toplam_uye_sayfa > 0 ? "$uye_sayfa / $toplam_uye_sayfa" : "0 / 0"; ?></span>
            <a href="?id=<?php echo $id; ?>&uye_sayfa=<?php echo $uye_sayfa + 1; ?>&etk_sayfa=<?php echo $etk_sayfa; ?>" 
               class="page-link <?php echo $uye_sayfa >= $toplam_uye_sayfa ? 'disabled' : ''; ?>"
               <?php echo $uye_sayfa >= $toplam_uye_sayfa ? 'onclick="return false;"' : ''; ?>>
                İleri
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    </div>

    <!-- Recent Events List -->
    <div class="list-container" style="animation-delay: 0.3s;">
        <div class="list-header">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Etkinlikler
            </h3>
            <a href="kulup_etkinlikleri.php?kulup_id=<?php echo $kulup['id']; ?>" class="item-action">Tümünü Gör</a>
        </div>
        <div class="list-body">
            <?php if (empty($son_etkinlikler)): ?>
                <div class="empty-list">Bu kulübe ait henüz etkinlik bulunmamaktadır.</div>
            <?php else: ?>
                <?php foreach ($son_etkinlikler as $etkinlik): 
                    $gecmis = strtotime($etkinlik['tarih']) < time();
                    $tarih_format = date('d M Y - H:i', strtotime($etkinlik['tarih']));
                ?>
                    <div class="list-item">
                        <div class="item-info">
                            <span class="item-title" style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php echo htmlspecialchars($etkinlik['baslik']); ?>
                                <?php if ($gecmis): ?>
                                    <span style="font-size: 0.65rem; background: rgba(148, 163, 184, 0.15); color: #94a3b8; padding: 0.15rem 0.5rem; border-radius: 1rem; font-weight: 500;">Bitti</span>
                                <?php else: ?>
                                    <span style="font-size: 0.65rem; background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 0.15rem 0.5rem; border-radius: 1rem; font-weight: 500;">Yaklaşan</span>
                                <?php endif; ?>
                            </span>
                            <span class="item-meta">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <?php echo $tarih_format; ?>
                                </span>
                            </span>
                        </div>
                        <a href="../etkinlik/etkinlik_duzenle.php?id=<?php echo $etkinlik['id']; ?>" class="item-action">Detay</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="pagination-footer">
            <a href="?id=<?php echo $id; ?>&etk_sayfa=<?php echo $etk_sayfa - 1; ?>&uye_sayfa=<?php echo $uye_sayfa; ?>" 
               class="page-link <?php echo $etk_sayfa <= 1 ? 'disabled' : ''; ?>"
               <?php echo $etk_sayfa <= 1 ? 'onclick="return false;"' : ''; ?>>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Geri
            </a>
            <span class="page-info"><?php echo $toplam_etk_sayfa > 0 ? "$etk_sayfa / $toplam_etk_sayfa" : "0 / 0"; ?></span>
            <a href="?id=<?php echo $id; ?>&etk_sayfa=<?php echo $etk_sayfa + 1; ?>&uye_sayfa=<?php echo $uye_sayfa; ?>" 
               class="page-link <?php echo $etk_sayfa >= $toplam_etk_sayfa ? 'disabled' : ''; ?>"
               <?php echo $etk_sayfa >= $toplam_etk_sayfa ? 'onclick="return false;"' : ''; ?>>
                İleri
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    </div>
</div>

    <!-- Gizli PDF İçeriği -->
    <div id="pdfContainer" style="position: absolute; left: -9999px; top: 0; width: 800px; background: white; z-index: -1;">
        <div id="pdfContent" style="padding: 40px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 25px;">
                <h1 style="color: #111827; margin-bottom: 5px; font-size: 24px; font-weight: bold;"><?php echo htmlspecialchars($kulup['ad']); ?></h1>
                <h2 style="color: #4b5563; font-size: 16px; font-weight: normal; margin: 0;">Üye Listesi</h2>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Ad Soyad</th>
                        <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Kullanıcı Adı</th>
                        <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Rol</th>
                        <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Katılma Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tum_uyeler)): ?>
                    <tr><td colspan="4" style="padding: 12px; border: 1px solid #d1d5db; text-align: center;">Henüz üye yok.</td></tr>
                    <?php else: foreach ($tum_uyeler as $u): ?>
                    <tr>
                        <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo htmlspecialchars($u['ad'] . ' ' . $u['soyad']); ?></td>
                        <td style="padding: 10px 12px; border: 1px solid #d1d5db;">@<?php echo htmlspecialchars($u['kullanici_adi']); ?></td>
                        <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo htmlspecialchars($u['kulup_rol']); ?></td>
                        <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo date('d.m.Y H:i', strtotime($u['katilma_tarihi'])); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 30px; text-align: right; font-size: 12px; color: #6b7280;">
                Oluşturulma Tarihi: <?php echo date('d.m.Y H:i'); ?>
            </div>
        </div>
    </div>

    <!-- html2pdf js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function indirPDF() {
            const element = document.getElementById('pdfContent');
            const opt = {
                margin:       [15, 15, 15, 15],
                filename:     '<?php echo preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($kulup['ad'])); ?>_uyeleri.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
<?php require_once '../footer.php'; ?>
