<?php
session_start();
require_once 'config/veritabani.php';

$giris_yapildi = isset($_SESSION['kullanici_id']);

// Tabloyu otomatik oluştur
$db->exec("
    CREATE TABLE IF NOT EXISTS toplantilar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kulup_id INT NOT NULL,
        konu VARCHAR(200) NOT NULL,
        aciklama TEXT,
        tarih DATETIME NOT NULL,
        konum VARCHAR(200) NOT NULL,
        hedef_rol VARCHAR(100) NOT NULL DEFAULT 'Herkes',
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

$db->exec("
    CREATE TABLE IF NOT EXISTS toplanti_katilimlari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        toplanti_id INT NOT NULL,
        katilim_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_katilim (kullanici_id, toplanti_id),
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
        FOREIGN KEY (toplanti_id) REFERENCES toplantilar(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Toplantıya katıl / vazgeç
if ($giris_yapildi && isset($_POST['toplanti_islem'])) {
    $toplanti_id = (int)$_POST['toplanti_id'];
    
    // Yasaklı kullanıcı kontrolü
    $stmt_user = $db->prepare("SELECT durum FROM kullanicilar WHERE id = ?");
    $stmt_user->execute([$_SESSION['kullanici_id']]);
    $user_status = $stmt_user->fetchColumn();
    
    if ($user_status === 'Yasakli') {
        header("Location: toplantilar.php?hata=yasakli"); exit;
    }

    if ($_POST['toplanti_islem'] === 'katil') {
        $db->prepare("INSERT IGNORE INTO toplanti_katilimlari (kullanici_id, toplanti_id) VALUES (?, ?)")->execute([$_SESSION['kullanici_id'], $toplanti_id]);
    } elseif ($_POST['toplanti_islem'] === 'ayril') {
        $db->prepare("DELETE FROM toplanti_katilimlari WHERE kullanici_id = ? AND toplanti_id = ?")->execute([$_SESSION['kullanici_id'], $toplanti_id]);
    }
    header("Location: toplantilar.php"); exit;
}


$toplantilar = [];
$kullanici_kulupleri = [];
$katilinanlar = [];
$kullanici_durum = 'Aktif';


if ($giris_yapildi) {
    $kullanici_id = $_SESSION['kullanici_id'];

    // Kullanıcının üye olduğu kulüpler ve rolleri
    $stmt = $db->prepare("
        SELECT ku.kulup_id, ku.kulup_rol, k.ad as kulup_adi 
        FROM kulup_uyelikleri ku 
        JOIN kulupler k ON ku.kulup_id = k.id 
        WHERE ku.kullanici_id = ?
    ");
    $stmt->execute([$kullanici_id]);
    $kullanici_kulupleri = $stmt->fetchAll();

    // Katıldığı toplantıları çek
    $stmt_kat = $db->prepare("SELECT toplanti_id FROM toplanti_katilimlari WHERE kullanici_id = ?");
    $stmt_kat->execute([$kullanici_id]);
    $katilinanlar = array_column($stmt_kat->fetchAll(), 'toplanti_id');

    // Kullanıcı Durumu
    $stmt_durum = $db->prepare("SELECT durum FROM kullanicilar WHERE id = ?");
    $stmt_durum->execute([$kullanici_id]);
    $kullanici_durum = $stmt_durum->fetchColumn() ?: 'Aktif';

    if (!empty($kullanici_kulupleri)) {
        $kulup_id_roller = [];
        foreach ($kullanici_kulupleri as $kk) {
            $kulup_id_roller[$kk['kulup_id']] = $kk['kulup_rol'];
        }
        $kulup_ids = array_keys($kulup_id_roller);
        $placeholders = implode(',', array_fill(0, count($kulup_ids), '?'));

        // Yaklaşan toplantıları çek
        $stmt = $db->prepare("
            SELECT t.*, k.ad as kulup_adi 
            FROM toplantilar t 
            JOIN kulupler k ON t.kulup_id = k.id 
            WHERE t.kulup_id IN ($placeholders) AND t.tarih >= NOW()
            ORDER BY t.tarih ASC
        ");
        $stmt->execute($kulup_ids);
        $tum_toplantilar = $stmt->fetchAll();

        // Rol filtreleme
        $filtrelenmis_toplantilar = [];
        foreach ($tum_toplantilar as $t) {
            $hedef = $t['hedef_rol'];
            if ($hedef === 'Herkes') {
                $filtrelenmis_toplantilar[] = $t;
            } else {
                $hedef_roller = array_map('trim', explode(',', $hedef));
                $kullanici_rolu = $kulup_id_roller[$t['kulup_id']] ?? '';
                if (in_array($kullanici_rolu, $hedef_roller)) {
                    $filtrelenmis_toplantilar[] = $t;
                }
            }
        }
        
        // Pagination logic for active meetings
        $sayfa_kayit_sayisi = 3;
        $sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
        $toplam_aktif = count($filtrelenmis_toplantilar);
        
        // Slicing the array for pagination
        $offset = ($sayfa - 1) * $sayfa_kayit_sayisi;
        $toplantilar = array_slice($filtrelenmis_toplantilar, $offset, $sayfa_kayit_sayisi);
        $toplam_sayfa = ceil($toplam_aktif / $sayfa_kayit_sayisi);
    }
}

$rol_renkleri = [
    'Herkes' => '#818cf8',
    'Yönetici' => '#f87171',
    'Başkan' => '#fbbf24',
    'Başkan Yardımcısı' => '#22d3ee',
    'Üye' => '#34d399',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplantılar - KulüpNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #f1f5f9; min-height: 100vh; }
        
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: rgba(10,14,26,0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.06); padding: 0 2rem; height: 70px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-size: 1.25rem; font-weight: 800; color: #f1f5f9; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand .brand-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .nav-links { display: flex; gap: 0.25rem; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .nav-links a:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
        .nav-links a.active { color: #818cf8; background: rgba(99,102,241,0.1); }
        .nav-auth { display: flex; gap: 0.75rem; align-items: center; }
        .btn-ghost { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.875rem; border-radius: 0.5rem; transition: all 0.2s; display: inline-flex; align-items: center; }
        .btn-ghost:hover { color: #f1f5f9; }
        .btn-primary-nav { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-decoration: none; }
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }
        .user-menu-name { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }

        .container { max-width: 900px; margin: 0 auto; padding: 6rem 2rem 3rem; }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 800; margin-bottom: 0.5rem; }
        .page-header p { color: #64748b; font-size: 0.9rem; }

        .section-title {
            font-size: 1rem; font-weight: 700; color: #e2e8f0; margin-bottom: 1rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .section-title svg { width: 20px; height: 20px; color: #818cf8; }

        .meeting-card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .meeting-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }
        .meeting-card:hover { border-color: rgba(99,102,241,0.2); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.2); }

        .meeting-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .meeting-konu { font-size: 1.05rem; font-weight: 700; color: #f1f5f9; }
        .meeting-kulup {
            font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.6rem;
            border-radius: 1rem; background: rgba(99,102,241,0.15); color: #818cf8;
        }

        .meeting-desc { font-size: 0.85rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem; }

        .meeting-meta { display: flex; flex-wrap: wrap; gap: 1.25rem; }
        .meta-item { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: #64748b; }
        .meta-item svg { width: 16px; height: 16px; color: #475569; }
        .meta-item strong { color: #cbd5e1; font-weight: 600; }

        .role-tags { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .role-tag {
            font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.5rem;
            border-radius: 1rem; display: inline-block;
        }

        .btn-join {
            padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 700;
            border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif;
            text-transform: uppercase; letter-spacing: 0.025em;
        }
        .btn-join.join { background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; }
        .btn-join.join:hover { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); transform: translateY(-1px); }
        .btn-join.leave { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-join.leave:hover { background: rgba(239, 68, 68, 0.2); }

        .empty-state {
            text-align: center; padding: 3rem; color: #475569;
        }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 1rem; color: #334155; }
        .empty-state p { font-size: 0.9rem; }

        .login-prompt {
            text-align: center; padding: 4rem 2rem;
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem;
        }
        .login-prompt h2 { margin-bottom: 0.75rem; font-size: 1.25rem; }
        .login-prompt p { color: #64748b; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .login-prompt a {
            display: inline-block; padding: 0.65rem 1.5rem; border-radius: 0.625rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            text-decoration: none; font-weight: 600; font-size: 0.9rem;
        }

        .gecmis-section { margin-top: 2.5rem; }
        .gecmis-card {
            background: rgba(30,41,59,0.3); border: 1px solid rgba(255,255,255,0.04);
            border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 0.75rem;
            opacity: 0.7; transition: opacity 0.2s;
        }
        .gecmis-card:hover { opacity: 1; }
        .gecmis-top { display: flex; justify-content: space-between; align-items: center; }
        .gecmis-konu { font-size: 0.9rem; font-weight: 600; color: #cbd5e1; }
        .gecmis-meta { font-size: 0.75rem; color: #475569; margin-top: 0.25rem; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .meeting-card, .gecmis-card { animation: fadeInUp 0.5s ease forwards; }
        @media (max-width: 768px) { .nav-links { display: none; } .meeting-meta { flex-direction: column; gap: 0.5rem; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?>
                <a href="toplantilar.php" class="active">Toplantılar</a>
                <a href="duyurular.php">Duyurular</a>
                <a href="oneri_sikayet.php">Öneri/Şikayet</a>
            <?php endif; ?>
        </div>
        <div class="nav-auth">
            <?php include 'includes/theme_toggle.php'; ?>
            <?php if ($giris_yapildi): ?>
                <div class="user-menu">
                    <?php include 'includes/bildirim.php'; ?>
                    <a href="profil.php" class="user-avatar"><?php echo mb_strtoupper(mb_substr($_SESSION['kullanici_ad'], 0, 1)); ?></a>
                    <a href="profil.php" style="text-decoration:none;"><span class="user-menu-name"><?php echo htmlspecialchars($_SESSION['kullanici_ad']); ?></span></a>
                    <a href="ayarlar.php" class="btn-ghost" title="Ayarlar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </a>
                    <a href="cikis.php" class="btn-ghost" title="Çıkış">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <a href="giris.php" class="btn-ghost">Giriş Yap</a>
                <a href="kayit.php" class="btn-primary-nav">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>📋 Toplantılar</h1>
            <p>Kulüplerinize ait yaklaşan ve geçmiş toplantıları görüntüleyin.</p>
            <?php if (isset($_GET['hata']) && $_GET['hata'] === 'yasakli'): ?>
                <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 500; display: inline-block;">
                    Yasaklı olduğunuz için toplantılara katılım sağlayamazsınız.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$giris_yapildi): ?>
            <div class="login-prompt">
                <h2>Toplantıları görmek için giriş yapın</h2>
                <p>Üye olduğunuz kulüplere ait toplantıları görüntüleyebilirsiniz.</p>
                <a href="giris.php">Giriş Yap</a>
            </div>
        <?php elseif (empty($kullanici_kulupleri)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                <p>Henüz bir kulübe üye değilsiniz. Toplantıları görebilmek için bir kulübe katılın.</p>
            </div>
        <?php else: ?>
            <!-- Yaklaşan Toplantılar -->
            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                Yaklaşan Toplantılar
            </div>

            <?php if (empty($toplantilar)): ?>
                <div class="empty-state" style="padding: 2rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <p>Yaklaşan toplantınız bulunmuyor.</p>
                </div>
            <?php else: ?>
                <?php foreach ($toplantilar as $t): 
                    $hedef_roller = array_map('trim', explode(',', $t['hedef_rol']));
                ?>
                <div class="meeting-card">
                    <div class="meeting-top">
                        <div class="meeting-konu"><?php echo htmlspecialchars($t['konu']); ?></div>
                        <span class="meeting-kulup"><?php echo htmlspecialchars($t['kulup_adi']); ?></span>
                    </div>
                    <?php if ($t['aciklama']): ?>
                        <div class="meeting-desc"><?php echo nl2br(htmlspecialchars($t['aciklama'])); ?></div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
                        <div class="meeting-meta" style="flex: 1;">
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                <strong><?php echo date('d.m.Y H:i', strtotime($t['tarih'])); ?></strong>
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                <strong><?php echo htmlspecialchars($t['konum']); ?></strong>
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                                <div class="role-tags">
                                    <?php foreach ($hedef_roller as $r): 
                                        $renk = $rol_renkleri[$r] ?? '#818cf8';
                                    ?>
                                        <span class="role-tag" style="background: <?php echo $renk; ?>20; color: <?php echo $renk; ?>;"><?php echo $r; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="flex-shrink: 0; margin-bottom: 0.2rem;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="toplanti_id" value="<?php echo $t['id']; ?>">
                                <?php if (in_array($t['id'], $katilinanlar)): ?>
                                    <button type="submit" name="toplanti_islem" value="ayril" class="btn-join leave">Vazgeç</button>
                                <?php elseif ($kullanici_durum === 'Yasakli'): ?>
                                    <span style="font-size: 0.75rem; color: #f87171; font-weight: 600;">Katılım Kısıtlandı</span>
                                <?php else: ?>
                                    <button type="submit" name="toplanti_islem" value="katil" class="btn-join join">Katılacağım</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($toplam_sayfa > 1): ?>
                <div class="pagination" style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    
                    <?php if ($sayfa > 1): ?>
                        <a href="?sayfa=<?php echo $sayfa - 1; ?>" style="
                            padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.85rem; font-weight: 600;
                            color: #94a3b8; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); transition: all 0.2s;
                        " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                            &laquo; Geri
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                        <a href="?sayfa=<?php echo $i; ?>" style="
                            padding: 0.5rem 1rem;
                            border-radius: 0.5rem;
                            text-decoration: none;
                            font-size: 0.85rem;
                            font-weight: 600;
                            color: <?php echo $i == $sayfa ? '#fff' : '#94a3b8'; ?>;
                            background: <?php echo $i == $sayfa ? 'linear-gradient(135deg, #6366f1, #7c3aed)' : 'rgba(255,255,255,0.05)'; ?>;
                            border: 1px solid <?php echo $i == $sayfa ? 'transparent' : 'rgba(255,255,255,0.1)'; ?>;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='<?php echo $i == $sayfa ? 'linear-gradient(135deg, #6366f1, #7c3aed)' : 'rgba(255,255,255,0.1)'; ?>'" onmouseout="this.style.background='<?php echo $i == $sayfa ? 'linear-gradient(135deg, #6366f1, #7c3aed)' : 'rgba(255,255,255,0.05)'; ?>'">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <a href="?sayfa=<?php echo $sayfa + 1; ?>" style="
                            padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.85rem; font-weight: 600;
                            color: #94a3b8; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); transition: all 0.2s;
                        " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                            İleri &raquo;
                        </a>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
    </div>
<script src="content/js/theme.js"></script>
</body>
</html>
