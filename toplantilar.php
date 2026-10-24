<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';

$giris_yapildi = kullanici_giris_kontrol();

// Toplantıya katıl / vazgeç
if ($giris_yapildi && isset($_POST['toplanti_islem'])) {
    csrf_zorunlu();
    $toplanti_id = (int)$_POST['toplanti_id'];

    // Yasaklı kullanıcı kontrolü
    $stmt_user = $db->prepare("SELECT durum FROM kullanicilar WHERE id = ?");
    $stmt_user->execute([$_SESSION['kullanici_id']]);
    $user_status = $stmt_user->fetchColumn();

    if ($user_status === 'Yasakli') {
        header("Location: toplantilar.php?hata=yasakli"); exit;
    }

    // IDOR/BOLA kapatması: toplantı var mı, kullanıcı üye mi, hedef role uyuyor mu — sunucu tarafında doğrula
    $stmt_t = $db->prepare("SELECT kulup_id, hedef_rol FROM toplantilar WHERE id = ?");
    $stmt_t->execute([$toplanti_id]);
    $toplanti = $stmt_t->fetch();
    if (!$toplanti) {
        header("Location: toplantilar.php?hata=gecersiz"); exit;
    }

    $stmt_u = $db->prepare("SELECT kulup_rol FROM kulup_uyelikleri WHERE kullanici_id = ? AND kulup_id = ?");
    $stmt_u->execute([$_SESSION['kullanici_id'], $toplanti['kulup_id']]);
    $uye_rolu = $stmt_u->fetchColumn();
    if ($uye_rolu === false || $uye_rolu === null || $uye_rolu === '') {
        header("Location: toplantilar.php?hata=uye_degil"); exit;
    }

    $hedef_roller_arr = array_map('trim', explode(',', (string)$toplanti['hedef_rol']));
    if ($toplanti['hedef_rol'] !== 'Herkes' && !in_array($uye_rolu, $hedef_roller_arr, true)) {
        header("Location: toplantilar.php?hata=rol_yetersiz"); exit;
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
    <link rel="stylesheet" href="content/css/tokens.css">
    <link rel="stylesheet" href="content/css/ui.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        .container { max-width: 900px; margin: 0 auto; padding: calc(var(--nav-height) + var(--sp-12)) var(--sp-8) var(--sp-12); }
        .section-title { font-size: var(--fs-md); font-weight: var(--fw-bold); color: var(--c-text-heading); margin-bottom: var(--sp-4); display: flex; align-items: center; gap: var(--sp-2); }
        .section-title svg { width: 20px; height: 20px; color: var(--c-primary-light); }
        .meeting-card { background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); padding: var(--sp-6); margin-bottom: var(--sp-4); transition: all var(--dur-normal) var(--ease-out); position: relative; overflow: hidden; }
        .meeting-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background: linear-gradient(180deg, var(--c-primary), var(--c-secondary)); }
        .meeting-card:hover { border-color: rgba(99,102,241,0.2); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .meeting-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .meeting-konu { font-size: var(--fs-lg); font-weight: var(--fw-bold); color: var(--c-text); }
        .meeting-kulup { font-size: 0.7rem; font-weight: var(--fw-semibold); padding: 0.2rem 0.6rem; border-radius: var(--r-pill); background: rgba(99,102,241,0.15); color: var(--c-primary-light); }
        .meeting-desc { font-size: var(--fs-sm); color: var(--c-text-muted); line-height: 1.5; margin-bottom: var(--sp-4); }
        .meeting-meta { display: flex; flex-wrap: wrap; gap: 1.25rem; }
        .meta-item { display: flex; align-items: center; gap: 0.4rem; font-size: var(--fs-sm); color: var(--c-text-subtle); }
        .meta-item svg { width: 16px; height: 16px; color: var(--c-text-subtle); }
        .meta-item strong { color: var(--c-text-heading); font-weight: var(--fw-semibold); }
        .role-tags { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .role-tag { font-size: 0.65rem; font-weight: var(--fw-bold); padding: 0.15rem 0.5rem; border-radius: var(--r-pill); display: inline-block; }
        .btn-join { padding: var(--sp-2) var(--sp-4); border-radius: var(--r-md); font-size: 0.8rem; font-weight: var(--fw-bold); border: none; cursor: pointer; transition: all var(--dur-fast); font-family: var(--font-family); text-transform: uppercase; letter-spacing: 0.025em; }
        .btn-join.join { background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; }
        .btn-join.join:hover { box-shadow: var(--shadow-glow); transform: translateY(-1px); }
        .btn-join.leave { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-join.leave:hover { background: rgba(239,68,68,0.2); }
        .login-prompt { text-align: center; padding: var(--sp-16) var(--sp-8); background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); }
        .login-prompt h2 { margin-bottom: 0.75rem; font-size: var(--fs-xl); }
        .login-prompt p { color: var(--c-text-muted); margin-bottom: var(--sp-6); }
        .login-prompt a { display: inline-block; padding: 0.65rem 1.5rem; border-radius: var(--r-md); background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; text-decoration: none; font-weight: var(--fw-semibold); }
        .gecmis-section { margin-top: var(--sp-10); }
        .gecmis-card { background: rgba(30,41,59,0.3); border: 1px solid rgba(255,255,255,0.04); border-radius: var(--r-md); padding: var(--sp-4) var(--sp-5); margin-bottom: 0.75rem; opacity: 0.7; transition: opacity var(--dur-fast); }
        .gecmis-card:hover { opacity: 1; }
        .gecmis-top { display: flex; justify-content: space-between; align-items: center; }
        .gecmis-konu { font-size: var(--fs-base); font-weight: var(--fw-semibold); color: var(--c-text-heading); }
        .gecmis-meta { font-size: var(--fs-xs); color: var(--c-text-subtle); margin-top: var(--sp-1); }
    </style>
</head>
<body>
    <a href="#main" class="skip-link">İçeriğe geç</a>
    <nav class="navbar" aria-label="Ana navigasyon">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <button class="mobile-menu-btn" aria-label="Menüyü aç" aria-expanded="false">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
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
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header"><span style="font-weight:700;font-size:1.1rem;">Menü</span><button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
        <div class="drawer-links"><a href="index.php">Ana Sayfa</a><a href="kulupler.php">Kulüpler</a><a href="etkinlikler.php">Etkinlikler</a><?php if ($giris_yapildi): ?><a href="toplantilar.php" class="active">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?></div>
        <div class="drawer-auth"><?php if ($giris_yapildi): ?><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a><?php else: ?><a href="giris.php" class="btn-ghost">Giriş</a><a href="kayit.php" class="btn btn-primary btn-sm">Kayıt Ol</a><?php endif; ?></div>
    </aside>

    <div class="container" id="main">
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
                <div class="meeting-card" data-animate>
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
                                        <span class="role-tag" style="background: <?php echo $renk; ?>20; color: <?php echo $renk; ?>;"><?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="flex-shrink: 0; margin-bottom: 0.2rem;">
                            <form method="POST" style="margin:0;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="toplanti_id" value="<?php echo (int)$t['id']; ?>">
                                <?php if (in_array($t['id'], $katilinanlar)): ?>
                                    <button type="submit" name="toplanti_islem" value="ayril" class="btn-join leave" onclick="return confirm('Bu toplantıdan ayrılmak istediğinize emin misiniz?');">Vazgeç</button>
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
<script src="content/js/nav.js"></script>
<script src="content/js/animations.js"></script>
<script src="content/js/toast.js"></script>
</body>
</html>
