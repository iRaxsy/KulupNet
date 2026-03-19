<?php
session_start();
require_once 'config/veritabani.php';
$giris_yapildi = isset($_SESSION['kullanici_id']);

$kulup_id = (int)($_GET['id'] ?? 0);
if (!$kulup_id) { header("Location: kulupler.php"); exit; }

// Kulüp bilgisi
$stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
$stmt->execute([$kulup_id]);
$kulup = $stmt->fetch();
if (!$kulup) { header("Location: kulupler.php"); exit; }

// Katıl / Ayrıl
if ($giris_yapildi && isset($_POST['kulup_islem'])) {
    if ($_POST['kulup_islem'] === 'katil') {
        $db->prepare("INSERT IGNORE INTO kulup_uyelikleri (kullanici_id, kulup_id) VALUES (?, ?)")->execute([$_SESSION['kullanici_id'], $kulup_id]);
    } elseif ($_POST['kulup_islem'] === 'ayril') {
        $db->prepare("DELETE FROM kulup_uyelikleri WHERE kullanici_id = ? AND kulup_id = ?")->execute([$_SESSION['kullanici_id'], $kulup_id]);
    }
    header("Location: kulup_detay.php?id=" . $kulup_id); exit;
}

// Sayfalama Ayarları
$sayfa_kayit_sayisi = 5;

// Üyeler Sayfalama
$u_sayfa = isset($_GET['u_sayfa']) ? max(1, (int)$_GET['u_sayfa']) : 1;
$u_offset = ($u_sayfa - 1) * $sayfa_kayit_sayisi;

$toplam_uye_stmt = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = ?");
$toplam_uye_stmt->execute([$kulup_id]);
$toplam_uye = $toplam_uye_stmt->fetchColumn();
$toplam_u_sayfa = ceil($toplam_uye / $sayfa_kayit_sayisi);

$uyeler = $db->prepare("SELECT ku.*, k.ad, k.soyad, k.kullanici_adi FROM kulup_uyelikleri ku JOIN kullanicilar k ON ku.kullanici_id = k.id WHERE ku.kulup_id = ? ORDER BY FIELD(ku.kulup_rol, 'Yönetici','Başkan','Başkan Yardımcısı','Üye'), ku.katilma_tarihi ASC LIMIT $sayfa_kayit_sayisi OFFSET $u_offset");
$uyeler->execute([$kulup_id]);
$uyeler = $uyeler->fetchAll();

// Etkinlikler Sayfalama
$e_sayfa = isset($_GET['e_sayfa']) ? max(1, (int)$_GET['e_sayfa']) : 1;
$e_offset = ($e_sayfa - 1) * $sayfa_kayit_sayisi;

$toplam_etk_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ? AND tarih >= NOW()");
$toplam_etk_stmt->execute([$kulup_id]);
$toplam_etk = $toplam_etk_stmt->fetchColumn();
$toplam_e_sayfa = ceil($toplam_etk / $sayfa_kayit_sayisi);

$etkinlikler = $db->prepare("SELECT * FROM etkinlikler WHERE kulup_id = ? AND tarih >= NOW() ORDER BY tarih ASC LIMIT $sayfa_kayit_sayisi OFFSET $e_offset");
$etkinlikler->execute([$kulup_id]);
$etkinlikler = $etkinlikler->fetchAll();

// Kullanıcı üye mi?
$uye_mi = false;
if ($giris_yapildi) {
    $stmt = $db->prepare("SELECT id FROM kulup_uyelikleri WHERE kullanici_id = ? AND kulup_id = ?");
    $stmt->execute([$_SESSION['kullanici_id'], $kulup_id]);
    $uye_mi = (bool)$stmt->fetch();
}

$kulup_rol_renkleri = [
    'Yönetici' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Başkan' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Başkan Yardımcısı' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kulup['ad']); ?> - Kulüp Detay</title>
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
        .btn-ghost { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.875rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-ghost:hover { color: #f1f5f9; }
        .btn-primary-nav { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-decoration: none; }
        .user-avatar-nav { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }
        .container { max-width: 900px; margin: 0 auto; padding: 6rem 2rem 3rem; }

        /* Club Header */
        .club-header {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1.25rem; padding: 2.5rem; margin-bottom: 1.5rem; position: relative; overflow: hidden;
        }
        .club-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4); }
        .club-top { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem; }
        .club-avatar-lg {
            width: 72px; height: 72px; border-radius: 1rem;
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3));
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.75rem; color: #818cf8; flex-shrink: 0;
        }
        .club-info h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .club-info .club-stats { font-size: 0.8rem; color: #64748b; display: flex; gap: 1rem; }
        .club-desc { font-size: 0.9rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; }
        .club-actions { display: flex; gap: 0.75rem; }
        .btn-action {
            padding: 0.6rem 1.5rem; border-radius: 0.625rem; font-size: 0.85rem; font-weight: 600;
            border: none; cursor: pointer; transition: all 0.3s; font-family: 'Inter', sans-serif;
        }
        .btn-action.join { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; }
        .btn-action.join:hover { box-shadow: 0 8px 25px rgba(99,102,241,0.4); transform: translateY(-1px); }
        .btn-action.leave { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-action.leave:hover { background: rgba(239,68,68,0.2); }
        .btn-back { color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 1.5rem; transition: color 0.2s; }
        .btn-back:hover { color: #a5b4fc; }

        /* Section Card */
        .section-card { background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06); border-radius: 1rem; margin-bottom: 1.5rem; overflow: hidden; }
        .section-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; }
        .section-card-header h2 { font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; color: #e2e8f0; }
        .section-card-header h2 svg { width: 18px; height: 18px; color: #818cf8; }
        .count-badge { background: rgba(99,102,241,0.15); color: #818cf8; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 1rem; font-weight: 600; }
        .list-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s; }
        .list-item:hover { background: rgba(255,255,255,0.02); }
        .list-item:last-child { border-bottom: none; }
        .list-item-avatar { width: 40px; height: 40px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
        .list-item-info { flex: 1; min-width: 0; }
        .list-item-name { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }
        .list-item-name a { color: #e2e8f0; text-decoration: none; }
        .list-item-name a:hover { color: #818cf8; }
        .list-item-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.1rem; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 600; }
        .empty-section { text-align: center; padding: 2rem; color: #475569; font-size: 0.85rem; }
        .empty-section svg { width: 40px; height: 40px; display: block; margin: 0 auto 0.5rem; color: #334155; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .club-header, .section-card { animation: fadeInUp 0.6s ease forwards; }
        
        /* Simple Pagination UI */
        .simple-pagination { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.04); background: rgba(15,23,42,0.2); }
        .page-btn { padding: 0.5rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.05); color: #94a3b8; text-decoration: none; font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.08); transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; }
        .page-btn:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; border-color: rgba(255,255,255,0.15); }
        .page-btn.disabled { opacity: 0.3; pointer-events: none; cursor: not-allowed; }
        
        @media (max-width: 768px) { .nav-links { display: none; } .club-top { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php" class="active">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?>
        </div>
        <div class="nav-auth">
            <?php if ($giris_yapildi): ?>
                <a href="profil.php" class="user-avatar-nav"><?php echo mb_strtoupper(mb_substr($_SESSION['kullanici_ad'], 0, 1)); ?></a>
                <a href="cikis.php" class="btn-ghost">Çıkış</a>
            <?php else: ?>
                <a href="giris.php" class="btn-ghost">Giriş</a>
                <a href="kayit.php" class="btn-primary-nav">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container">
        <a href="kulupler.php" class="btn-back">← Kulüplere Dön</a>
        <div class="club-header">
            <div class="club-top">
                <div class="club-avatar-lg"><?php echo mb_strtoupper(mb_substr($kulup['ad'], 0, 1)); ?></div>
                <div class="club-info">
                    <h1><?php echo htmlspecialchars($kulup['ad']); ?></h1>
                    <div class="club-stats">
                        <span><?php echo $toplam_uye; ?> üye</span>
                        <span><?php echo $toplam_etk; ?> etkinlik</span>
                    </div>
                </div>
            </div>
            <div class="club-desc"><?php echo nl2br(htmlspecialchars($kulup['aciklama'] ?? 'Açıklama bulunmuyor.')); ?></div>
            <?php if ($giris_yapildi): ?>
                <div class="club-actions">
                    <form method="POST">
                        <?php if ($uye_mi): ?>
                            <button type="submit" name="kulup_islem" value="ayril" class="btn-action leave">Kulüpten Ayrıl</button>
                        <?php else: ?>
                            <button type="submit" name="kulup_islem" value="katil" class="btn-action join">Kulübe Katıl</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Üyeler -->
        <div class="section-card">
            <div class="section-card-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    Üyeler
                </h2>
                <span class="count-badge"><?php echo $toplam_uye; ?></span>
            </div>
            <?php if (empty($uyeler)): ?>
                <div class="empty-section"><p>Henüz üye yok.</p></div>
            <?php else: ?>
                <?php foreach ($uyeler as $u):
                    $kr = $kulup_rol_renkleri[$u['kulup_rol']] ?? $kulup_rol_renkleri['Üye'];
                ?>
                <div class="list-item">
                    <div class="list-item-avatar" style="background:rgba(99,102,241,0.15);color:#818cf8;"><?php echo mb_strtoupper(mb_substr($u['ad'], 0, 1)); ?></div>
                    <div class="list-item-info">
                        <div class="list-item-name"><a href="profil.php?id=<?php echo $u['kullanici_id']; ?>"><?php echo htmlspecialchars($u['ad'] . ' ' . $u['soyad']); ?></a></div>
                        <div class="list-item-meta">@<?php echo htmlspecialchars($u['kullanici_adi']); ?> · Katılma: <?php echo date('d.m.Y', strtotime($u['katilma_tarihi'])); ?></div>
                    </div>
                    <span class="badge" style="background:<?php echo $kr['bg']; ?>;color:<?php echo $kr['color']; ?>;"><?php echo htmlspecialchars($u['kulup_rol']); ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="simple-pagination">
                    <a href="?id=<?php echo $kulup_id; ?>&u_sayfa=<?php echo ($u_sayfa - 1); ?>&e_sayfa=<?php echo $e_sayfa; ?>" class="page-btn <?php echo ($u_sayfa <= 1) ? 'disabled' : ''; ?>">← Geri</a>
                    <span style="font-size:0.75rem;color:#475569;font-weight:500;">Üyeler (<?php echo $u_sayfa; ?>/<?php echo max(1, $toplam_u_sayfa); ?>)</span>
                    <a href="?id=<?php echo $kulup_id; ?>&u_sayfa=<?php echo ($u_sayfa + 1); ?>&e_sayfa=<?php echo $e_sayfa; ?>" class="page-btn <?php echo ($u_sayfa >= $toplam_u_sayfa) ? 'disabled' : ''; ?>">İleri →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Etkinlikler -->
        <div class="section-card">
            <div class="section-card-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    Etkinlikler
                </h2>
                <span class="count-badge"><?php echo $toplam_etk; ?></span>
            </div>
            <?php if (empty($etkinlikler)): ?>
                <div class="empty-section"><p>Henüz etkinlik yok.</p></div>
            <?php else: ?>
                <?php foreach ($etkinlikler as $e): ?>
                <div class="list-item">
                    <div class="list-item-avatar" style="background:rgba(16,185,129,0.15);color:#34d399;"><?php echo date('d', strtotime($e['tarih'])); ?></div>
                    <div class="list-item-info">
                        <div class="list-item-name"><?php echo htmlspecialchars($e['baslik']); ?></div>
                        <div class="list-item-meta"><?php echo date('d.m.Y H:i', strtotime($e['tarih'])); ?> · <?php echo htmlspecialchars($e['konum'] ?? 'Belirtilmedi'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="simple-pagination">
                    <a href="?id=<?php echo $kulup_id; ?>&e_sayfa=<?php echo ($e_sayfa - 1); ?>&u_sayfa=<?php echo $u_sayfa; ?>" class="page-btn <?php echo ($e_sayfa <= 1) ? 'disabled' : ''; ?>">← Geri</a>
                    <span style="font-size:0.75rem;color:#475569;font-weight:500;">Etkinlikler (<?php echo $e_sayfa; ?>/<?php echo max(1, $toplam_e_sayfa); ?>)</span>
                    <a href="?id=<?php echo $kulup_id; ?>&e_sayfa=<?php echo ($e_sayfa + 1); ?>&u_sayfa=<?php echo $u_sayfa; ?>" class="page-btn <?php echo ($e_sayfa >= $toplam_e_sayfa) ? 'disabled' : ''; ?>">İleri →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
