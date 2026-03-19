<?php
$page_title = 'Öneri/Şikayet Detayı - Admin Paneli';
$header_title = 'Öneri/Şikayet Detayı';
$active_page = 'oneri_sikayet';
require_once '../header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: oneri_sikayet.php");
    exit;
}

// Kaydı getir
$stmt = $db->prepare("SELECT os.*, k.ad, k.soyad, k.kullanici_adi, k.eposta 
                      FROM oneri_sikayet os 
                      JOIN kullanicilar k ON os.kullanici_id = k.id 
                      WHERE os.id = ?");
$stmt->execute([$id]);
$kayit = $stmt->fetch();

if (!$kayit) {
    echo "<div style='color:#f87171; text-align:center; padding:2rem;'>Kayıt bulunamadı.</div>";
    require_once '../footer.php';
    exit;
}
?>

<style>
    .detail-container {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .detail-title h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #f1f5f9;
        margin: 0 0 0.5rem 0;
    }

    .type-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    .type-oneri { background: rgba(16,185,129,0.15); color: #34d399; }
    .type-sikayet { background: rgba(239,68,68,0.15); color: #f87171; }

    .detail-section {
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-title svg {
        width: 16px;
        height: 16px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        background: rgba(15, 23, 42, 0.3);
        padding: 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .info-label {
        font-size: 0.75rem;
        color: #64748b;
    }

    .info-val {
        font-size: 0.95rem;
        color: #e2e8f0;
        font-weight: 500;
    }

    .message-box {
        background: rgba(15, 23, 42, 0.3);
        padding: 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.04);
        color: #e2e8f0;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: rgba(255, 255, 255, 0.05);
        color: #e2e8f0;
        text-decoration: none;
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 1.5rem;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-1px);
    }
</style>

<a href="oneri_sikayet.php" class="back-btn">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
    </svg>
    Listeye Dön
</a>

<div class="detail-container">
    <div class="detail-header">
        <div class="detail-title">
            <h2>Kayıt #<?php echo $kayit['id']; ?> Detayı</h2>
            <div style="font-size:0.85rem; color:#64748b; margin-top:0.5rem;">
                Tarih: <?php echo date('d.m.Y H:i:s', strtotime($kayit['tarih'])); ?>
            </div>
        </div>
        <div>
            <span class="type-badge <?php echo $kayit['tur'] === 'Öneri' ? 'type-oneri' : 'type-sikayet'; ?>">
                <?php echo htmlspecialchars($kayit['tur']); ?>
            </span>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            Kullanıcı Bilgileri
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Ad Soyad</div>
                <div class="info-val"><?php echo htmlspecialchars($kayit['ad'] . ' ' . $kayit['soyad']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Kullanıcı Adı</div>
                <div class="info-val">@<?php echo htmlspecialchars($kayit['kullanici_adi']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">E-posta</div>
                <div class="info-val"><?php echo htmlspecialchars($kayit['eposta'] ?? 'Belirtilmedi'); ?></div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z" />
            </svg>
            Mesaj İçeriği
        </div>
        <div class="message-box"><?php echo nl2br(htmlspecialchars($kayit['aciklama'])); ?></div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
