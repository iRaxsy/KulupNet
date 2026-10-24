<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
$giris_yapildi = kullanici_giris_kontrol();

// Kulübe katıl / ayrıl
if ($giris_yapildi && isset($_POST['kulup_islem'])) {
    csrf_zorunlu();
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
    <link rel="stylesheet" href="content/css/tokens.css">
    <link rel="stylesheet" href="content/css/ui.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        .container { padding: calc(var(--nav-height) + var(--sp-12)) var(--sp-8) var(--sp-12); }
        .btn-join { padding: 0.4rem 1rem; border-radius: var(--r-md); font-size: 0.8rem; font-weight: var(--fw-semibold); border: none; cursor: pointer; transition: all var(--dur-fast); font-family: var(--font-family); }
        .btn-join.join { background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; }
        .btn-join.join:hover { box-shadow: var(--shadow-glow); }
        .btn-join.leave { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-join.leave:hover { background: rgba(239,68,68,0.2); }
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
            <a href="kulupler.php" class="active">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
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
    <!-- Mobil Drawer -->
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header">
            <span style="font-weight:700;font-size:1.1rem;">Menü</span>
            <button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="drawer-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php" class="active">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a><?php endif; ?>
        </div>
        <div class="drawer-auth">
            <?php if ($giris_yapildi): ?><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a>
            <?php else: ?><a href="giris.php" class="btn-ghost">Giriş</a><a href="kayit.php" class="btn btn-primary btn-sm">Kayıt Ol</a><?php endif; ?>
        </div>
    </aside>

    <div class="container" id="main">
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
            <div class="card" data-animate data-delay="<?php echo ($i % 6) + 1; ?>">
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
                            <?php echo csrf_field(); ?>
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
<script src="content/js/theme.js"></script>
<script src="content/js/nav.js"></script>
<script src="content/js/animations.js"></script>
<script src="content/js/toast.js"></script>
</body>
</html>
