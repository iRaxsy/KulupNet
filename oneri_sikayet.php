<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
giris_gerekli($db);

$mesaj = ''; $hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $tur = $_POST['tur'] ?? '';
    $aciklama = trim($_POST['aciklama'] ?? '');

    if (empty($aciklama)) {
        $hata = 'Lütfen açıklama alanını doldurun.';
    } elseif (!in_array($tur, ['Öneri', 'Şikayet'])) {
        $hata = 'Geçersiz tür seçimi.';
    } else {
        $stmt = $db->prepare("INSERT INTO oneri_sikayet (kullanici_id, tur, aciklama) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['kullanici_id'], $tur, $aciklama]);
        $mesaj = 'Geri bildiriminiz başarıyla gönderildi. Teşekkür ederiz!';
    }
}

// Kullanıcının geçmiş gönderileri - Sayfalama
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

$toplam_stmt = $db->prepare("SELECT COUNT(*) FROM oneri_sikayet WHERE kullanici_id = ?");
$toplam_stmt->execute([$_SESSION['kullanici_id']]);
$toplam_kayit = $toplam_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $sayfa_kayit_sayisi);
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

$stmt = $db->prepare("SELECT * FROM oneri_sikayet WHERE kullanici_id = ? ORDER BY tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
$stmt->execute([$_SESSION['kullanici_id']]);
$gecmis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öneri / Şikayet - KulüpNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/tokens.css">
    <link rel="stylesheet" href="content/css/ui.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        .container { max-width: 700px; margin: 0 auto; padding: calc(var(--nav-height) + var(--sp-12)) var(--sp-8) var(--sp-12); }
        .page-header { margin-bottom: var(--sp-8); }
        .page-header h1 { font-size: var(--fs-2xl); font-weight: var(--fw-bold); margin-bottom: var(--sp-1); }
        .page-header p { font-size: var(--fs-base); color: var(--c-text-subtle); }

        .form-card { background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); padding: var(--sp-8); margin-bottom: var(--sp-6); }
        .form-card h2 { font-size: var(--fs-lg); font-weight: var(--fw-bold); margin-bottom: var(--sp-2); }
        .form-card .desc { font-size: var(--fs-sm); color: var(--c-text-subtle); margin-bottom: var(--sp-6); }

        .radio-group { display: flex; gap: var(--sp-4); margin-bottom: var(--sp-5); }
        .radio-option { flex: 1; position: relative; }
        .radio-option input { position: absolute; opacity: 0; width: 0; height: 0; }
        .radio-option label { display: flex; align-items: center; justify-content: center; gap: var(--sp-2); padding: var(--sp-3) var(--sp-4); border-radius: var(--r-md); cursor: pointer; border: 1px solid var(--c-border); background: rgba(15,23,42,0.6); color: var(--c-text-muted); font-size: var(--fs-sm); font-weight: var(--fw-semibold); transition: all var(--dur-fast); }
        .radio-option input:checked + label { border-color: rgba(99,102,241,0.5); background: rgba(99,102,241,0.1); color: var(--c-primary-light); }
        .radio-option label:hover { border-color: var(--c-border-strong); }

        textarea { width: 100%; padding: var(--sp-3) var(--sp-4); border-radius: var(--r-md); border: 1px solid var(--c-border); background: rgba(15,23,42,0.6); color: var(--c-text); font-size: var(--fs-base); font-family: var(--font-family); outline: none; transition: all var(--dur-normal); min-height: 120px; resize: vertical; }
        textarea::placeholder { color: #475569; }
        textarea:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

        .submit-btn { padding: var(--sp-3) var(--sp-8); border: none; border-radius: var(--r-md); background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; font-size: var(--fs-base); font-weight: var(--fw-semibold); cursor: pointer; transition: all var(--dur-normal); font-family: var(--font-family); width: 100%; }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-glow); }

        /* History */
        .section-card { background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); overflow: hidden; }
        .section-card-header { padding: var(--sp-5) var(--sp-6); border-bottom: 1px solid var(--c-border); display: flex; justify-content: space-between; align-items: center; }
        .section-card-header h2 { font-size: var(--fs-md); font-weight: var(--fw-semibold); color: var(--c-text-heading); display: flex; align-items: center; gap: var(--sp-2); }
        .section-card-header h2 svg { width: 18px; height: 18px; color: var(--c-primary-light); }
        .count-badge { background: rgba(99,102,241,0.15); color: var(--c-primary-light); font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: var(--r-pill); font-weight: var(--fw-semibold); }
        .history-item { padding: var(--sp-4) var(--sp-6); border-bottom: 1px solid rgba(255,255,255,0.04); }
        .history-item:last-child { border-bottom: none; }
        .history-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-2); }
        .history-type { padding: 0.2rem 0.6rem; border-radius: var(--r-pill); font-size: 0.7rem; font-weight: var(--fw-semibold); }
        .type-oneri { background: rgba(16,185,129,0.15); color: #34d399; }
        .type-sikayet { background: rgba(239,68,68,0.15); color: #f87171; }
        .history-date { font-size: 0.7rem; color: var(--c-text-subtle); }
        .history-text { font-size: var(--fs-sm); color: var(--c-text-muted); line-height: 1.5; }
        .empty-section { text-align: center; padding: var(--sp-8); color: #475569; font-size: var(--fs-sm); }

        .pagination { display: flex; justify-content: center; gap: var(--sp-2); padding: var(--sp-5); border-top: 1px solid var(--c-border); }
        .page-link { padding: 0.4rem 0.8rem; border-radius: var(--r-sm); background: rgba(15,23,42,0.6); color: var(--c-text-muted); text-decoration: none; font-size: 0.8rem; border: 1px solid var(--c-border); transition: all var(--dur-fast); }
        .page-link:hover { background: var(--c-surface-hover); color: var(--c-text); }
        .page-link.active { background: rgba(99,102,241,0.15); color: var(--c-primary-light); border-color: rgba(99,102,241,0.3); }
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
            <a href="toplantilar.php">Toplantılar</a>
            <a href="duyurular.php">Duyurular</a>
            <a href="oneri_sikayet.php" class="active">Öneri/Şikayet</a>
        </div>
        <div class="nav-auth">
            <?php include 'includes/theme_toggle.php'; ?>
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
        </div>
    </nav>
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header"><span style="font-weight:700;font-size:1.1rem;">Menü</span><button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
        <div class="drawer-links"><a href="index.php">Ana Sayfa</a><a href="kulupler.php">Kulüpler</a><a href="etkinlikler.php">Etkinlikler</a><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php" class="active">Öneri/Şikayet</a></div>
        <div class="drawer-auth"><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a></div>
    </aside>

    <div class="container" id="main">
        <div class="page-header">
            <h1>💬 Öneri / Şikayet</h1>
            <p>Görüşlerinizi bizimle paylaşın, platformu birlikte geliştirelim</p>
        </div>

        <?php if ($mesaj): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <?php echo $mesaj; ?>
            </div>
        <?php endif; ?>
        <?php if ($hata): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <?php echo $hata; ?>
            </div>
        <?php endif; ?>

        <div class="form-card" data-animate>
            <h2>Yeni Geri Bildirim</h2>
            <p class="desc">Öneri veya şikayetinizi aşağıdaki form ile iletebilirsiniz.</p>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <label style="font-size:0.8rem;font-weight:500;color:#94a3b8;margin-bottom:0.5rem;display:block;">Tür Seçin</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" name="tur" value="Öneri" id="tur_oneri" checked>
                        <label for="tur_oneri">💡 Öneri</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" name="tur" value="Şikayet" id="tur_sikayet">
                        <label for="tur_sikayet">⚠️ Şikayet</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="aciklama">Açıklama</label>
                    <textarea name="aciklama" id="aciklama" placeholder="Öneri veya şikayetinizi detaylı bir şekilde yazın..." required></textarea>
                </div>
                <button type="submit" class="submit-btn">Gönder</button>
            </form>
        </div>

        <?php if (!empty($gecmis)): ?>
        <div class="section-card" data-animate>
            <div class="section-card-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    Geçmiş Gönderilerim
                </h2>
                <span class="count-badge"><?php echo $toplam_kayit; ?></span>
            </div>
            <?php foreach ($gecmis as $g): ?>
            <div class="history-item">
                <div class="history-top">
                    <span class="history-type <?php echo $g['tur'] === 'Öneri' ? 'type-oneri' : 'type-sikayet'; ?>"><?php echo htmlspecialchars($g['tur']); ?></span>
                    <span class="history-date"><?php echo date('d.m.Y H:i', strtotime($g['tarih'])); ?></span>
                </div>
                <div class="history-text"><?php echo nl2br(htmlspecialchars($g['aciklama'])); ?></div>
            </div>
            <?php endforeach; ?>
            
            <?php if ($toplam_sayfa > 1): ?>
            <div class="pagination">
                <?php if ($sayfa > 1): ?>
                    <a href="?sayfa=1" class="page-link" title="İlk Sayfa">&laquo;&laquo;</a>
                    <a href="?sayfa=<?php echo ($sayfa - 1); ?>" class="page-link">&laquo;</a>
                <?php endif; ?>
                
                <?php 
                $aralik = 2; // Gösterilecek sayfa aralığı
                $baslangic = max(1, $sayfa - $aralik);
                $bitis = min($toplam_sayfa, $sayfa + $aralik);
                
                if ($baslangic > 1) { echo '<span style="color:#64748b;padding:0.4rem;">...</span>'; }
                
                for ($i = $baslangic; $i <= $bitis; $i++): ?>
                    <a href="?sayfa=<?php echo $i; ?>" class="page-link <?php echo $i == $sayfa ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; 
                
                if ($bitis < $toplam_sayfa) { echo '<span style="color:#64748b;padding:0.4rem;">...</span>'; }
                ?>

                <?php if ($sayfa < $toplam_sayfa): ?>
                    <a href="?sayfa=<?php echo ($sayfa + 1); ?>" class="page-link">&raquo;</a>
                    <a href="?sayfa=<?php echo $toplam_sayfa; ?>" class="page-link" title="Son Sayfa">&raquo;&raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<script src="content/js/nav.js"></script>
<script src="content/js/animations.js"></script>
<script src="content/js/toast.js"></script>
<script src="content/js/theme.js"></script>
</body>
</html>
