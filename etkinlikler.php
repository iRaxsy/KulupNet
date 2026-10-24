<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
$giris_yapildi = kullanici_giris_kontrol();

// Etkinliğe katıl / ayrıl
if ($giris_yapildi && isset($_POST['etkinlik_islem'])) {
    csrf_zorunlu();
    $etkinlik_id = (int)$_POST['etkinlik_id'];
    if ($_POST['etkinlik_islem'] === 'katil') {
        // Yasaklı kullanıcı kontrolü
        $stmt_user = $db->prepare("SELECT durum FROM kullanicilar WHERE id = ?");
        $stmt_user->execute([$_SESSION['kullanici_id']]);
        $user_status = $stmt_user->fetchColumn();

        if ($user_status === 'Yasakli') {
            header("Location: etkinlikler.php?hata=yasakli"); exit;
        }

        // Kontenjan + etkinlik varlığı kontrolü (IDOR/5-uyarı kapatması)
        $stmt_check = $db->prepare("SELECT kisi_siniri, (SELECT COUNT(*) FROM etkinlik_katilimlari WHERE etkinlik_id = ?) as mevcut_katilimci FROM etkinlikler WHERE id = ?");
        $stmt_check->execute([$etkinlik_id, $etkinlik_id]);
        $check = $stmt_check->fetch();

        if (!$check) {
            header("Location: etkinlikler.php?hata=gecersiz_etkinlik"); exit;
        }

        if ($check['kisi_siniri'] === null || $check['mevcut_katilimci'] < $check['kisi_siniri']) {
            $db->prepare("INSERT IGNORE INTO etkinlik_katilimlari (kullanici_id, etkinlik_id) VALUES (?, ?)")->execute([$_SESSION['kullanici_id'], $etkinlik_id]);
        } else {
            // Kontenjan dolu
            header("Location: etkinlikler.php?hata=kontenjan"); exit;
        }
    } elseif ($_POST['etkinlik_islem'] === 'ayril') {
        $db->prepare("DELETE FROM etkinlik_katilimlari WHERE kullanici_id = ? AND etkinlik_id = ?")->execute([$_SESSION['kullanici_id'], $etkinlik_id]);
    }
    header("Location: etkinlikler.php"); exit;
}

// Arama
$aranan = trim($_GET['aranan'] ?? '');

// Sayfalama Ayarları
$sayfa_kayit_sayisi = 6;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Sorgu Oluşturma
$where_clauses = ["e.tarih >= NOW()"];
$params = [];

if (!empty($aranan)) {
    $where_clauses[] = "(e.baslik LIKE ? OR e.aciklama LIKE ? OR k.ad LIKE ? OR e.konum LIKE ?)";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
    $params[] = "%$aranan%";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$sql = "SELECT e.*, k.ad as kulup_adi, (SELECT COUNT(*) FROM etkinlik_katilimlari WHERE etkinlik_id = e.id) as katilimci_sayisi 
        FROM etkinlikler e 
        LEFT JOIN kulupler k ON e.kulup_id = k.id 
        $where_sql 
        ORDER BY e.tarih ASC 
        LIMIT $sayfa_kayit_sayisi OFFSET $offset";

$count_sql = "SELECT COUNT(*) FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id $where_sql";

// Toplam kayıt ve sayfa sayısı
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$toplam_kayit = $count_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $sayfa_kayit_sayisi);

// Verileri getir
$stmt = $db->prepare($sql);
$stmt->execute($params);
$etkinlikler = $stmt->fetchAll();

// Kullanıcının katıldığı etkinlikler ve durumu
$katildigi = [];
$kullanici_durum = 'Aktif';
if ($giris_yapildi) {
    // Katılımlar
    $stmt = $db->prepare("SELECT etkinlik_id FROM etkinlik_katilimlari WHERE kullanici_id = ?");
    $stmt->execute([$_SESSION['kullanici_id']]);
    $katildigi = array_column($stmt->fetchAll(), 'etkinlik_id');
    
    // Kullanıcı Durumu (Yasaklı mı?)
    $stmt_durum = $db->prepare("SELECT durum FROM kullanicilar WHERE id = ?");
    $stmt_durum->execute([$_SESSION['kullanici_id']]);
    $kullanici_durum = $stmt_durum->fetchColumn() ?: 'Aktif';
}

$aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlikler - KulüpNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/tokens.css">
    <link rel="stylesheet" href="content/css/ui.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        .container { padding: calc(var(--nav-height) + var(--sp-12)) var(--sp-8) var(--sp-12); }
        .event-card { background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); padding: var(--sp-6); transition: transform var(--dur-normal) var(--ease-out), box-shadow var(--dur-normal), border-color var(--dur-normal); position: relative; overflow: hidden; }
        .event-card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,0.2); box-shadow: var(--shadow-lg); }
        .card-date { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 52px; height: 52px; border-radius: var(--r-md); background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); }
        .card-date .day { font-size: var(--fs-xl); font-weight: var(--fw-bold); color: var(--c-primary-light); line-height: 1; }
        .card-date .month { font-size: 0.6rem; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .card-meta-row { display: flex; gap: var(--sp-4); align-items: center; }
        .card-meta-item { display: flex; align-items: center; gap: var(--sp-1); font-size: var(--fs-xs); color: var(--c-text-subtle); }
        .card-meta-item svg { width: 14px; height: 14px; }
        .btn-join { padding: 0.4rem 1rem; border-radius: var(--r-md); font-size: 0.8rem; font-weight: var(--fw-semibold); border: none; cursor: pointer; transition: all var(--dur-fast); font-family: var(--font-family); }
        .btn-join.join { background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; }
        .btn-join.join:hover { box-shadow: var(--shadow-glow); }
        .btn-join.leave { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-join.leave:hover { background: rgba(239,68,68,0.2); }
        .status-badge { display: inline-flex; align-items: center; gap: var(--sp-1); padding: 0.2rem 0.6rem; border-radius: var(--r-pill); font-size: 0.65rem; font-weight: var(--fw-semibold); }
        .status-upcoming { background: rgba(16,185,129,0.15); color: #34d399; }
        .status-past { background: rgba(100,116,139,0.15); color: #94a3b8; }
        .status-full { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .status-banned { background: rgba(239,68,68,0.15); color: #f87171; }
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
            <a href="etkinlikler.php" class="active">Etkinlikler</a>
            <?php if ($giris_yapildi): ?><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?>
        </div>
        <div class="nav-auth">
            <?php include 'includes/theme_toggle.php'; ?>
            <?php if ($giris_yapildi): ?>
                <div class="user-menu" style="display: flex; align-items: center; gap: 0.75rem;">
                    <?php include 'includes/bildirim.php'; ?>
                    <a href="profil.php" class="user-avatar-nav"><?php echo mb_strtoupper(mb_substr($_SESSION['kullanici_ad'], 0, 1)); ?></a>
                    <a href="profil.php" style="text-decoration:none;"><span class="user-menu-name" style="font-size: 0.875rem; font-weight: 600; color: #e2e8f0;"><?php echo htmlspecialchars($_SESSION['kullanici_ad']); ?></span></a>
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
                <a href="giris.php" class="btn-ghost">Giriş</a>
                <a href="kayit.php" class="btn-primary-nav">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header"><span style="font-weight:700;font-size:1.1rem;">Menü</span><button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
        <div class="drawer-links"><a href="index.php">Ana Sayfa</a><a href="kulupler.php">Kulüpler</a><a href="etkinlikler.php" class="active">Etkinlikler</a><?php if ($giris_yapildi): ?><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?></div>
        <div class="drawer-auth"><?php if ($giris_yapildi): ?><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a><?php else: ?><a href="giris.php" class="btn-ghost">Giriş</a><a href="kayit.php" class="btn btn-primary btn-sm">Kayıt Ol</a><?php endif; ?></div>
    </aside>
    <div class="container" id="main">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1>📅 Etkinlikler</h1>
                    <p>Tüm etkinlikleri görüntüleyin ve katılın</p>
                    <?php if (isset($_GET['hata']) && $_GET['hata'] === 'yasakli'): ?>
                        <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 500; display: inline-block;">
                            Hesabınız yasaklandığı için etkinliklere katılamazsınız.
                        </div>
                    <?php endif; ?>
                </div>
                <form action="" method="GET" class="search-container">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="text" name="aranan" class="search-input" placeholder="Etkinlik, kulüp veya konum ara..." value="<?php echo htmlspecialchars($aranan); ?>">
                </form>
            </div>
        </div>
        <div class="cards-grid">
            <?php if (!empty($etkinlikler)):
                foreach ($etkinlikler as $e):
                    $ts = strtotime($e['tarih']);
                    $gun = date('d', $ts); $ay = $aylar[(int)date('n', $ts) - 1]; $saat = date('H:i', $ts);
                    $gecmis = $ts < time();
                    $katildi = in_array($e['id'], $katildigi);
            ?>
            <div class="event-card" data-animate>
                <div class="card-top">
                    <div class="card-date">
                        <span class="day"><?php echo $gun; ?></span>
                        <span class="month"><?php echo $ay; ?></span>
                    </div>
                    <div>
                        <div class="card-title"><?php echo htmlspecialchars($e['baslik']); ?></div>
                        <div class="card-subtitle">
                            <?php echo htmlspecialchars($e['kulup_adi'] ?? 'Genel'); ?> · <?php echo $saat; ?>
                            <?php if ($gecmis): ?>
                                <span class="status-badge status-past">Bitti</span>
                            <?php else: ?>
                                <span class="status-badge status-upcoming">Aktif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-desc"><?php echo htmlspecialchars($e['aciklama'] ?? 'Açıklama yok.'); ?></div>
                <div class="card-footer">
                    <div class="card-meta-row">
                        <div class="card-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                            <?php echo htmlspecialchars($e['konum'] ?? 'Belirtilmedi'); ?>
                        </div>
                        <div class="card-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            <?php 
                                $ks = $e['kisi_siniri'] ?? null;
                                $sinir_text = $ks ? ' / ' . $ks : '';
                                echo $e['katilimci_sayisi'] . $sinir_text . ' katılımcı'; 
                            ?>
                        </div>
                    </div>
                    <?php 
                        $dolu = ($ks !== null && $e['katilimci_sayisi'] >= $ks);
                        if ($giris_yapildi && !$gecmis): 
                    ?>
                        <form method="POST" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="etkinlik_id" value="<?php echo (int)$e['id']; ?>">
                            <?php if ($katildi): ?>
                                <button type="submit" name="etkinlik_islem" value="ayril" class="btn-join leave">Ayrıl</button>
                            <?php elseif ($kullanici_durum === 'Yasakli'): ?>
                                <span class="status-badge status-banned">Yasaklısınız</span>
                            <?php elseif ($dolu): ?>
                                <span class="status-badge status-full">Kontenjan Dolu</span>
                            <?php else: ?>
                                <button type="submit" name="etkinlik_islem" value="katil" class="btn-join join">Katıl</button>
                            <?php endif; ?>
                        </form>
                    <?php elseif (!$giris_yapildi && !$gecmis && $dolu): ?>
                        <span class="status-badge status-full">Kontenjan Dolu</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                <p><?php echo !empty($aranan) ? '"' . htmlspecialchars($aranan) . '" için sonuç bulunamadı.' : 'Henüz etkinlik bulunmuyor.'; ?></p>
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
<script src="content/js/theme.js"></script>
<script src="content/js/nav.js"></script>
<script src="content/js/animations.js"></script>
<script src="content/js/toast.js"></script>
</body>
</html>
