<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

if (!isset($_GET['id'])) {
    header("Location: etkinlikler.php");
    exit;
}

$id = (int)$_GET['id'];

// Get Event Info with Club Name
$stmt = $db->prepare("
    SELECT e.*, k.ad as kulup_adi 
    FROM etkinlikler e 
    LEFT JOIN kulupler k ON e.kulup_id = k.id 
    WHERE e.id = ?
");
$stmt->execute([$id]);
$etkinlik = $stmt->fetch();

if (!$etkinlik) {
    header("Location: etkinlikler.php?mesaj=bulunamadi");
    exit;
}

// Get Participant Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM etkinlik_katilimlari WHERE etkinlik_id = ?");
$stmt->execute([$id]);
$toplam_katilimci = $stmt->fetchColumn();

// Gender distribution
$stmt = $db->prepare("
    SELECT k.cinsiyet, COUNT(*) as sayi 
    FROM etkinlik_katilimlari ek 
    JOIN kullanicilar k ON ek.kullanici_id = k.id 
    WHERE ek.etkinlik_id = ? 
    GROUP BY k.cinsiyet
");
$stmt->execute([$id]);
$cinsiyet_dağılımı = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$erkek_sayisi = $cinsiyet_dağılımı['Erkek'] ?? 0;
$kadin_sayisi = $cinsiyet_dağılımı['Kadin'] ?? 0;

// Get Participant List
$limit = 10;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $limit;
$toplam_sayfa = ceil($toplam_katilimci / $limit);

$stmt = $db->prepare("
    SELECT k.id, k.ad, k.soyad, k.kullanici_adi, ek.katilma_tarihi 
    FROM etkinlik_katilimlari ek 
    JOIN kullanicilar k ON ek.kullanici_id = k.id 
    WHERE ek.etkinlik_id = ? 
    ORDER BY ek.katilma_tarihi DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$id]);
$katilimcilar = $stmt->fetchAll();

$page_title = $etkinlik['baslik'] . ' Detayları - Admin Paneli';
$header_title = 'Etkinlik Yönetimi';
$active_page = 'etkinlikler';
require_once '../header.php';

$tarih_format = date('d.m.Y H:i', strtotime($etkinlik['tarih']));
$bitmis = strtotime($etkinlik['tarih']) < time();
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

    .badge-status {
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-active { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .badge-ended { background: rgba(239, 68, 68, 0.15); color: #f87171; }

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

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .stat-icon svg { width: 24px; height: 24px; }
    .icon-total { background: rgba(99, 102, 241, 0.1); color: #818cf8; }
    .icon-male { background: rgba(14, 165, 233, 0.1); color: #38bdf8; }
    .icon-female { background: rgba(236, 72, 153, 0.1); color: #f472b6; }

    .stat-value { font-size: 1.75rem; font-weight: 800; color: #f1f5f9; margin-bottom: 0.25rem; }
    .stat-label { font-size: 0.8rem; color: #94a3b8; font-weight: 500; }

    .list-container {
        margin-top: 5rem;
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
        min-height: 550px; /* Sabit yükseklik sağlamak için */
    }

    .list-header {
        padding: 1.25rem 1.5rem;
        background: rgba(15, 23, 42, 0.6);
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .list-header h3 { font-size: 1.1rem; font-weight: 600; color: #e2e8f0; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .list-header h3 svg { width: 20px; height: 20px; color: #818cf8; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .participant-table { width: 100%; border-collapse: collapse; }
    .participant-table th { background: rgba(51, 65, 85, 0.3); padding: 0.75rem 1.5rem; text-align: left; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .participant-table td { padding: 0.75rem 1.5rem; color: #cbd5e1; font-size: 0.85rem; border-bottom: 1px solid rgba(255, 255, 255, 0.04); }
    .participant-table tr:last-child td { border-bottom: none; }
    .participant-table tr:hover { background: rgba(255, 255, 255, 0.02); }

    .empty-state { padding: 3rem; text-align: center; color: #64748b; }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

    /* Pagination Styles */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: rgba(15, 23, 42, 0.4);
        border-top: 1px solid rgba(255, 255, 255, 0.04);
        margin-top: auto; /* İtmeli dikey sabitleme */
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
    <a href="etkinlikler.php" class="btn-modern btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Etkinliklere Dön
    </a>
    <a href="etkinlik_duzenle.php?id=<?php echo $etkinlik['id']; ?>" class="btn-modern btn-edit">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
        </svg>
        Etkinliği Düzenle
    </a>
</div>

<div class="grid-container">
    <!-- Event Info Panel -->
    <div class="panel">
        <div class="panel-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <h2>Etkinlik Bilgileri</h2>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Başlık ve Durum</div>
                <div class="info-value" style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; color: #818cf8;"><?php echo htmlspecialchars($etkinlik['baslik']); ?></span>
                    <span class="badge-status <?php echo $bitmis ? 'badge-ended' : 'badge-active'; ?>">
                        <?php echo $bitmis ? 'Bitti' : 'Aktif'; ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Kulüp</div>
                <div class="info-value"><?php echo htmlspecialchars($etkinlik['kulup_adi'] ?: 'Genel'); ?></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="info-item">
                    <div class="info-label">Tarih</div>
                    <div class="info-value"><?php echo $tarih_format; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Konum</div>
                    <div class="info-value"><?php echo htmlspecialchars($etkinlik['konum'] ?: '-'); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Açıklama</div>
                <div class="info-value" style="font-size: 0.9rem; color: #cbd5e1;">
                    <?php echo nl2br(htmlspecialchars($etkinlik['aciklama'] ?: 'Açıklama bulunmuyor.')); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Panel -->
    <div class="panel">
        <div class="panel-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            <h2>Katılım İstatistikleri</h2>
        </div>
        
        <div class="stat-grid">
            <div class="stat-box" style="grid-column: span 2;">
                <div class="stat-icon icon-total">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($toplam_katilimci); ?></div>
                <div class="stat-label">Toplam Katılımcı</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon icon-male">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($erkek_sayisi); ?></div>
                <div class="stat-label">Erkek</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon icon-female">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($kadin_sayisi); ?></div>
                <div class="stat-label">Kadın</div>
            </div>
        </div>
    </div>
</div>

<!-- Participant List -->
<div class="list-container">
    <div class="list-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
            Katılımcı Listesi
        </h3>
    </div>
    <div class="table-responsive">
        <?php if (empty($katilimcilar)): ?>
            <div class="empty-state">Henüz katılımcı bulunmuyor.</div>
        <?php else: ?>
            <table class="participant-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>Kullanıcı Adı</th>
                        <th>Katılma Tarihi</th>
                        <th style="text-align: right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($katilimcilar as $katilimci): ?>
                        <tr>
                            <td><span style="color: #818cf8; font-weight: 700;">#<?php echo $katilimci['id']; ?></span></td>
                            <td><span style="font-weight: 600;"><?php echo htmlspecialchars($katilimci['ad'] . ' ' . $katilimci['soyad']); ?></span></td>
                            <td><code style="color: #94a3b8;"><?php echo htmlspecialchars($katilimci['kullanici_adi']); ?></code></td>
                            <td><span style="font-size: 0.8rem; color: #64748b;"><?php echo date('d.m.Y H:i', strtotime($katilimci['katilma_tarihi'])); ?></span></td>
                            <td style="text-align: right;">
                                <a href="../uye/uye_detay.php?id=<?php echo $katilimci['id']; ?>" style="color: #818cf8; text-decoration: none; font-weight: 600; font-size: 0.8rem;">Detay</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="pagination-footer">
        <a href="?id=<?php echo $id; ?>&sayfa=<?php echo $sayfa - 1; ?>" 
           class="page-link <?php echo $sayfa <= 1 ? 'disabled' : ''; ?>"
           <?php echo $sayfa <= 1 ? 'onclick="return false;"' : ''; ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Geri
        </a>
        <span class="page-info"><?php echo $toplam_sayfa > 0 ? "$sayfa / $toplam_sayfa" : "0 / 0"; ?></span>
        <a href="?id=<?php echo $id; ?>&sayfa=<?php echo $sayfa + 1; ?>" 
           class="page-link <?php echo $sayfa >= $toplam_sayfa ? 'disabled' : ''; ?>"
           <?php echo $sayfa >= $toplam_sayfa ? 'onclick="return false;"' : ''; ?>>
            İleri
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>
</div>

<?php require_once '../footer.php'; ?>
