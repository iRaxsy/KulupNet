<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
giris_gerekli($db);

$kullanici = kullanici_bilgi($db);
$mesaj = ''; $hata = ''; $tab = $_GET['tab'] ?? 'sifre';

// Şifre değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['islem'])) {
    csrf_zorunlu();
    if ($_POST['islem'] === 'sifre') {
        $mevcut = $_POST['mevcut_sifre'] ?? '';
        $yeni = $_POST['yeni_sifre'] ?? '';
        $yeni_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';
        if (!$kullanici || !password_verify($mevcut, $kullanici['sifre'])) { $hata = 'Mevcut şifre yanlış.'; $tab = 'sifre'; }
        elseif (strlen($yeni) < 6) { $hata = 'Yeni şifre en az 6 karakter olmalıdır.'; $tab = 'sifre'; }
        elseif ($yeni !== $yeni_tekrar) { $hata = 'Yeni şifreler eşleşmiyor.'; $tab = 'sifre'; }
        else {
            $hashed_yeni = password_hash($yeni, PASSWORD_DEFAULT);
            $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?")->execute([$hashed_yeni, $_SESSION['kullanici_id']]);
            // Şifre değişince yeni bir oturum ID'si üret (eski token devre dışı kalsın)
            session_regenerate_id(true);
            $mesaj = 'Şifreniz başarıyla değiştirildi.'; $tab = 'sifre';
        }
    } elseif ($_POST['islem'] === 'eposta') {
        $yeni_eposta = trim($_POST['yeni_eposta'] ?? '');
        if (!filter_var($yeni_eposta, FILTER_VALIDATE_EMAIL)) { $hata = 'Geçerli bir e-posta girin.'; $tab = 'eposta'; }
        else {
            $stmt = $db->prepare("SELECT id FROM kullanicilar WHERE eposta = ? AND id != ?");
            $stmt->execute([$yeni_eposta, $_SESSION['kullanici_id']]);
            if ($stmt->fetch()) { $hata = 'Bu e-posta zaten kullanılıyor.'; $tab = 'eposta'; }
            else {
                $db->prepare("UPDATE kullanicilar SET eposta = ? WHERE id = ?")->execute([$yeni_eposta, $_SESSION['kullanici_id']]);
                $mesaj = 'E-posta adresiniz güncellendi.'; $tab = 'eposta';
            }
        }
    } elseif ($_POST['islem'] === 'gizlilik') {
        $gizli_eposta = isset($_POST['gizli_eposta']) ? 1 : 0;
        $gizli_kulupler = isset($_POST['gizli_kulupler']) ? 1 : 0;
        $gizli_etkinlikler = isset($_POST['gizli_etkinlikler']) ? 1 : 0;
        $gizli_cinsiyet = isset($_POST['gizli_cinsiyet']) ? 1 : 0;
        $db->prepare("UPDATE kullanicilar SET gizli_eposta = ?, gizli_kulupler = ?, gizli_etkinlikler = ?, gizli_cinsiyet = ? WHERE id = ?")
           ->execute([$gizli_eposta, $gizli_kulupler, $gizli_etkinlikler, $gizli_cinsiyet, $_SESSION['kullanici_id']]);
        $mesaj = 'Gizlilik ayarlarınız güncellendi.'; $tab = 'gizlilik';
        // Refresh user data
        $kullanici = kullanici_bilgi($db);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesap Ayarları</title>
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

        /* Tabs */
        .tabs { display: flex; gap: var(--sp-1); margin-bottom: var(--sp-6); background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-md); padding: var(--sp-1); }
        .tab-btn { flex: 1; padding: var(--sp-3) var(--sp-4); border: none; border-radius: var(--r-sm); background: transparent; color: var(--c-text-muted); font-size: var(--fs-sm); font-weight: var(--fw-semibold); cursor: pointer; transition: all var(--dur-fast); font-family: var(--font-family); }
        .tab-btn.active { background: rgba(99,102,241,0.15); color: var(--c-primary-light); }
        .tab-btn:hover:not(.active) { color: var(--c-text-heading); }

        /* Settings Card */
        .settings-card { background: rgba(30,41,59,0.5); border: 1px solid var(--c-border); border-radius: var(--r-lg); padding: var(--sp-8); }
        .settings-card h2 { font-size: var(--fs-lg); font-weight: var(--fw-bold); margin-bottom: var(--sp-2); }
        .settings-card .desc { font-size: var(--fs-sm); color: var(--c-text-subtle); margin-bottom: var(--sp-6); }

        .form-group { margin-bottom: var(--sp-5); }
        .form-group label { display: block; font-size: var(--fs-sm); font-weight: var(--fw-medium); color: var(--c-text-muted); margin-bottom: 0.4rem; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] {
            width: 100%; padding: var(--sp-3) var(--sp-4); border-radius: var(--r-md);
            border: 1px solid var(--c-border); background: rgba(15,23,42,0.6);
            color: var(--c-text); font-size: var(--fs-base); font-family: var(--font-family);
            outline: none; transition: all var(--dur-normal);
        }
        .form-group input:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .submit-btn { padding: var(--sp-3) var(--sp-8); border: none; border-radius: var(--r-md); background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white; font-size: var(--fs-base); font-weight: var(--fw-semibold); cursor: pointer; transition: all var(--dur-normal); font-family: var(--font-family); }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-glow); }

        /* Toggle Switch */
        .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: var(--sp-4) 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .toggle-group:last-of-type { border-bottom: none; }
        .toggle-info .toggle-label { font-size: var(--fs-base); font-weight: var(--fw-semibold); color: var(--c-text-heading); }
        .toggle-info .toggle-desc { font-size: var(--fs-xs); color: var(--c-text-subtle); margin-top: 0.1rem; }
        .toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background: rgba(255,255,255,0.1); border-radius: 12px; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: var(--c-text-muted); border-radius: 50%; transition: 0.3s; }
        .toggle input:checked + .toggle-slider { background: rgba(99,102,241,0.5); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); background: var(--c-primary-light); }

        .tab-content { display: none; }
        .tab-content.active { display: block; }
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
            <a href="oneri_sikayet.php">Öneri/Şikayet</a>
        </div>
        <div class="nav-auth">
            <?php include 'includes/theme_toggle.php'; ?>
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
    </nav>
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header"><span style="font-weight:700;font-size:1.1rem;">Menü</span><button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
        <div class="drawer-links"><a href="index.php">Ana Sayfa</a><a href="kulupler.php">Kulüpler</a><a href="etkinlikler.php">Etkinlikler</a><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a></div>
        <div class="drawer-auth"><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a></div>
    </aside>

    <div class="container" id="main">
        <div class="page-header">
            <h1>⚙️ Hesap Ayarları</h1>
            <p>Şifre, e-posta ve gizlilik ayarlarınızı yönetin</p>
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

        <div class="tabs">
            <button class="tab-btn <?php echo $tab === 'sifre' ? 'active' : ''; ?>" onclick="switchTab('sifre')">🔒 Şifre</button>
            <button class="tab-btn <?php echo $tab === 'eposta' ? 'active' : ''; ?>" onclick="switchTab('eposta')">📧 E-posta</button>
            <button class="tab-btn <?php echo $tab === 'gizlilik' ? 'active' : ''; ?>" onclick="switchTab('gizlilik')">👁️ Gizlilik</button>
        </div>

        <!-- Şifre -->
        <div class="tab-content <?php echo $tab === 'sifre' ? 'active' : ''; ?>" id="tab-sifre">
            <div class="settings-card">
                <h2>Şifre Değiştir</h2>
                <p class="desc">Hesabınızın güvenliği için şifrenizi düzenli olarak değiştirin.</p>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="islem" value="sifre">
                    <div class="form-group">
                        <label>Mevcut Şifre</label>
                        <input type="password" name="mevcut_sifre" required placeholder="Mevcut şifreniz">
                    </div>
                    <div class="form-group">
                        <label>Yeni Şifre</label>
                        <input type="password" name="yeni_sifre" required placeholder="En az 6 karakter">
                    </div>
                    <div class="form-group">
                        <label>Yeni Şifre (Tekrar)</label>
                        <input type="password" name="yeni_sifre_tekrar" required placeholder="Yeni şifrenizi tekrarlayın">
                    </div>
                    <button type="submit" class="submit-btn">Şifreyi Değiştir</button>
                </form>
            </div>
        </div>

        <!-- E-posta -->
        <div class="tab-content <?php echo $tab === 'eposta' ? 'active' : ''; ?>" id="tab-eposta">
            <div class="settings-card">
                <h2>E-posta Değiştir</h2>
                <p class="desc">Mevcut e-posta: <strong><?php echo htmlspecialchars($kullanici['eposta']); ?></strong></p>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="islem" value="eposta">
                    <div class="form-group">
                        <label>Yeni E-posta</label>
                        <input type="email" name="yeni_eposta" required placeholder="Yeni e-posta adresiniz">
                    </div>
                    <button type="submit" class="submit-btn">E-postayı Güncelle</button>
                </form>
            </div>
        </div>

        <!-- Gizlilik -->
        <div class="tab-content <?php echo $tab === 'gizlilik' ? 'active' : ''; ?>" id="tab-gizlilik">
            <div class="settings-card">
                <h2>Gizlilik Ayarları</h2>
                <p class="desc">Profilinizde hangi bilgilerin diğer kullanıcılara gösterilmeyeceğini seçin.</p>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="islem" value="gizlilik">
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">E-postamı Gizle</div>
                            <div class="toggle-desc">Diğer kullanıcılar profilinizde e-posta adresinizi göremez</div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="gizli_eposta" <?php echo $kullanici['gizli_eposta'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Kulüplerimi Gizle</div>
                            <div class="toggle-desc">Diğer kullanıcılar katıldığınız kulüpleri göremez</div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="gizli_kulupler" <?php echo $kullanici['gizli_kulupler'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Etkinliklerimi Gizle</div>
                            <div class="toggle-desc">Diğer kullanıcılar katıldığınız etkinlikleri göremez</div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="gizli_etkinlikler" <?php echo $kullanici['gizli_etkinlikler'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Cinsiyetimi Gizle</div>
                            <div class="toggle-desc">Diğer kullanıcılar profilinizde cinsiyetinizi göremez</div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="gizli_cinsiyet" <?php echo $kullanici['gizli_cinsiyet'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <br>
                    <button type="submit" class="submit-btn">Ayarları Kaydet</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`#tab-${tab}`).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
<script src="content/js/nav.js"></script>
<script src="content/js/form-ux.js"></script>
<script src="content/js/toast.js"></script>
<script src="content/js/theme.js"></script>
</body>
</html>
