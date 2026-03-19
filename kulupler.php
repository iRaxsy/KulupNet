<?php
session_start();
require_once 'config/veritabani.php';
$giris_yapildi = isset($_SESSION['kullanici_id']);

// Kulübe katıl / ayrıl
if ($giris_yapildi && isset($_POST['kulup_islem'])) {
    $kulup_id = (int)$_POST['kulup_id'];
    if ($_POST['kulup_islem'] === 'katil') {
        $stmt = $db->prepare("INSERT IGNORE INTO kulup_uyelikleri (kullanici_id, kulup_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['kullanici_id'], $kulup_id]);
    } elseif ($_POST['kulup_islem'] === 'ayril') {
        $stmt = $db->prepare("DELETE FROM kulup_uyelikleri WHERE kullanici_id = ? AND kulup_id = ?");
        $stmt->execute([$_SESSION['kullanici_id'], $kulup_id]);
    }
    header("Location: kulupler.php");
    exit;
}

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sayfalama Ayarları
$sayfa_kayit_sayisi = 9;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Sorgu Oluşturma
$sql = "SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = k.id) as uye_sayisi FROM kulupler k";
$count_sql = "SELECT COUNT(*) FROM kulupler k";
$params = [];

if (!empty($aranan)) {
    $sql .= " WHERE k.ad LIKE ? OR k.aciklama LIKE ?";
    $count_sql .= " WHERE k.ad LIKE ? OR k.aciklama LIKE ?";
    $params = ["%$aranan%", "%$aranan%"];
}

$sql .= " ORDER BY k.ad ASC LIMIT $sayfa_kayit_sayisi OFFSET $offset";

// Toplam kayıt ve sayfa sayısı
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$toplam_kayit = $count_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $sayfa_kayit_sayisi);

// Verileri getir
$stmt = $db->prepare($sql);
$stmt->execute($params);
$kulupler = $stmt->fetchAll();

// Kullanıcının katıldığı kulüpler
$katildigi = [];
if ($giris_yapildi) {
    $stmt = $db->prepare("SELECT kulup_id FROM kulup_uyelikleri WHERE kullanici_id = ?");
    $stmt->execute([$_SESSION['kullanici_id']]);
    $katildigi = array_column($stmt->fetchAll(), 'kulup_id');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kulüpler - KulüpNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #f1f5f9; min-height: 100vh; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: rgba(10,14,26,0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.06); padding: 0 2rem; height: 70px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-size: 1.25rem; font-weight: 800; color: #f1f5f9; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand .brand-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .nav-links { display: flex; gap: 0.25rem; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .nav-links a:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
        .nav-links a.active { color: #818cf8; background: rgba(99,102,241,0.1); }
        .nav-auth { display: flex; gap: 0.75rem; align-items: center; }
        .btn-ghost { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.875rem; transition: all 0.2s; border-radius: 0.5rem; }
        .btn-ghost:hover { color: #f1f5f9; }
        .btn-primary-nav { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-decoration: none; }
        .user-avatar-nav { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }
        .container { max-width: 1100px; margin: 0 auto; padding: 6rem 2rem 3rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-header p { font-size: 0.875rem; color: #64748b; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; padding: 1.5rem; transition: all 0.3s; position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,0.2); box-shadow: 0 12px 40px rgba(0,0,0,0.2); }
        .card-top { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .card-avatar { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #e2e8f0; margin-bottom: 0.25rem; }
        .card-subtitle { font-size: 0.8rem; color: #64748b; }
        .card-desc { font-size: 0.85rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .card-meta { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #64748b; }
        .card-meta svg { width: 14px; height: 14px; }
        .card-btn { padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.2s; color: #818cf8; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); display: inline-block; }
        .card-btn:hover { background: rgba(99,102,241,0.2); }
        .btn-join { padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; }
        .btn-join.join { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; }
        .btn-join.join:hover { box-shadow: 0 4px 15px rgba(99,102,241,0.4); }
        .btn-join.leave { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-join.leave:hover { background: rgba(239,68,68,0.2); }
        .empty-state { text-align: center; padding: 3rem; color: #475569; grid-column: 1/-1; }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 1rem; color: #334155; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        .card:nth-child(1) { animation-delay: 0.05s; } .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.15s; } .card:nth-child(4) { animation-delay: 0.2s; }
        .card:nth-child(5) { animation-delay: 0.25s; } .card:nth-child(6) { animation-delay: 0.3s; }

        /* Search & Pagination */
        .search-container { margin-bottom: 2rem; position: relative; max-width: 500px; }
        .search-input { width: 100%; padding: 0.8rem 1rem 0.8rem 2.8rem; border-radius: 0.75rem; background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.08); color: white; font-family: 'Inter', sans-serif; transition: all 0.3s; }
        .search-input:focus { outline: none; border-color: #6366f1; background: rgba(30,41,59,0.8); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #64748b; width: 18px; height: 18px; }
        
        .pagination { display: flex; justify-content: center; align-items: center; gap: 1.5rem; margin-top: 3rem; grid-column: 1/-1; }
        .page-btn { padding: 0.6rem 1.25rem; border-radius: 0.6rem; background: rgba(30,41,59,0.5); color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.06); transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; }
        .page-btn:hover:not(.disabled) { background: rgba(99,102,241,0.1); color: #818cf8; border-color: rgba(99,102,241,0.2); }
        .page-btn.disabled { opacity: 0.3; cursor: not-allowed; }
        .page-info { font-size: 0.85rem; color: #64748b; font-weight: 500; }

        @media (max-width: 768px) { .nav-links { display: none; } .cards-grid { grid-template-columns: 1fr; } .search-container { max-width: 100%; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php" class="active">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?>
        </div>
        <div class="nav-auth">
            <?php if ($giris_yapildi): ?>
                <a href="profil.php" class="user-avatar-nav"><?php echo mb_strtoupper(mb_substr($_SESSION['kullanici_ad'], 0, 1)); ?></a>
                <a href="ayarlar.php" class="btn-ghost">Ayarlar</a>
                <a href="cikis.php" class="btn-ghost">Çıkış</a>
            <?php else: ?>
                <a href="giris.php" class="btn-ghost">Giriş</a>
                <a href="kayit.php" class="btn-primary-nav">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1>🎯 Kulüpler</h1>
                    <p>Tüm kulüpleri keşfedin, ilgi alanlarınıza göre katılın</p>
                </div>
                <form action="" method="GET" class="search-container">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="text" name="aranan" class="search-input" placeholder="Kulüp ara..." value="<?php echo htmlspecialchars($aranan); ?>">
                </form>
            </div>
        </div>
        <div class="cards-grid">
            <?php
            $renkler = [
                ['bg'=>'rgba(99,102,241,0.15)','color'=>'#818cf8'], ['bg'=>'rgba(16,185,129,0.15)','color'=>'#34d399'],
                ['bg'=>'rgba(245,158,11,0.15)','color'=>'#fbbf24'], ['bg'=>'rgba(236,72,153,0.15)','color'=>'#f472b6'],
                ['bg'=>'rgba(59,130,246,0.15)','color'=>'#60a5fa'], ['bg'=>'rgba(6,182,212,0.15)','color'=>'#22d3ee'],
            ];
            if (!empty($kulupler)):
                foreach ($kulupler as $i => $k):
                    $renk = $renkler[$i % count($renkler)];
                    $harf = mb_strtoupper(mb_substr($k['ad'], 0, 1));
                    $uye_mi = in_array($k['id'], $katildigi);
            ?>
            <div class="card">
                <div class="card-top">
                    <div class="card-avatar" style="background:<?php echo $renk['bg']; ?>;color:<?php echo $renk['color']; ?>;"><?php echo $harf; ?></div>
                    <div>
                        <div class="card-title"><?php echo htmlspecialchars($k['ad']); ?></div>
                        <div class="card-subtitle"><?php echo $k['uye_sayisi']; ?> üye</div>
                    </div>
                </div>
                <div class="card-desc"><?php echo htmlspecialchars($k['aciklama'] ?? 'Açıklama bulunmuyor.'); ?></div>
                <div class="card-footer">
                    <a href="kulup_detay.php?id=<?php echo $k['id']; ?>" class="card-btn">Detay →</a>
                    <?php if ($giris_yapildi): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="kulup_id" value="<?php echo $k['id']; ?>">
                            <?php if ($uye_mi): ?>
                                <button type="submit" name="kulup_islem" value="ayril" class="btn-join leave">Ayrıl</button>
                            <?php else: ?>
                                <button type="submit" name="kulup_islem" value="katil" class="btn-join join">Katıl</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                <p><?php echo !empty($aranan) ? '"' . htmlspecialchars($aranan) . '" için sonuç bulunamadı.' : 'Henüz kulüp bulunmuyor.'; ?></p>
            </div>
            <?php endif; ?>

            <?php if ($toplam_sayfa > 1): ?>
            <div class="pagination">
                <a href="?sayfa=<?php echo $sayfa - 1; ?><?php echo !empty($aranan) ? '&aranan='.urlencode($aranan) : ''; ?>" class="page-btn <?php echo $sayfa <= 1 ? 'disabled' : ''; ?>">← Geri</a>
                <div class="page-info"><?php echo $sayfa; ?> / <?php echo $toplam_sayfa; ?></div>
                <a href="?sayfa=<?php echo $sayfa + 1; ?><?php echo !empty($aranan) ? '&aranan='.urlencode($aranan) : ''; ?>" class="page-btn <?php echo $sayfa >= $toplam_sayfa ? 'disabled' : ''; ?>">İleri →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
