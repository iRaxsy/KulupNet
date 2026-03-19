<?php
session_start();
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
giris_gerekli();

$kullanici = kullanici_bilgi($db);
$mesaj = ''; $hata = ''; $tab = $_GET['tab'] ?? 'sifre';

// Şifre değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['islem'])) {
    if ($_POST['islem'] === 'sifre') {
        $mevcut = $_POST['mevcut_sifre'] ?? '';
        $yeni = $_POST['yeni_sifre'] ?? '';
        $yeni_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';
        if ($mevcut !== $kullanici['sifre']) { $hata = 'Mevcut şifre yanlış.'; $tab = 'sifre'; }
        elseif (strlen($yeni) < 6) { $hata = 'Yeni şifre en az 6 karakter olmalıdır.'; $tab = 'sifre'; }
        elseif ($yeni !== $yeni_tekrar) { $hata = 'Yeni şifreler eşleşmiyor.'; $tab = 'sifre'; }
        else {
            $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?")->execute([$yeni, $_SESSION['kullanici_id']]);
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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #f1f5f9; min-height: 100vh; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: rgba(10,14,26,0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.06); padding: 0 2rem; height: 70px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-size: 1.25rem; font-weight: 800; color: #f1f5f9; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand .brand-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .nav-links { display: flex; gap: 0.25rem; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .nav-links a:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
        .nav-auth { display: flex; gap: 0.75rem; align-items: center; }
        .btn-ghost { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.875rem; transition: all 0.2s; border-radius: 0.5rem; }
        .btn-ghost:hover { color: #f1f5f9; }
        .user-avatar-nav { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }
        .container { max-width: 700px; margin: 0 auto; padding: 6rem 2rem 3rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-header p { font-size: 0.875rem; color: #64748b; }

        /* Tabs */
        .tabs { display: flex; gap: 0.25rem; margin-bottom: 1.5rem; background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.75rem; padding: 0.25rem; }
        .tab-btn {
            flex: 1; padding: 0.7rem 1rem; border: none; border-radius: 0.5rem; background: transparent;
            color: #94a3b8; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .tab-btn.active { background: rgba(99,102,241,0.15); color: #818cf8; }
        .tab-btn:hover:not(.active) { color: #e2e8f0; }

        /* Settings Card */
        .settings-card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; padding: 2rem; animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .settings-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .settings-card .desc { font-size: 0.8rem; color: #64748b; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #94a3b8; margin-bottom: 0.4rem; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] {
            width: 100%; padding: 0.75rem 1rem; border-radius: 0.625rem;
            border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);
            color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s;
        }
        .form-group input:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .submit-btn {
            padding: 0.75rem 2rem; border: none; border-radius: 0.625rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }

        /* Toggle Switch */
        .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .toggle-group:last-of-type { border-bottom: none; }
        .toggle-info .toggle-label { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }
        .toggle-info .toggle-desc { font-size: 0.75rem; color: #64748b; margin-top: 0.1rem; }
        .toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0; background: rgba(255,255,255,0.1);
            border-radius: 12px; transition: 0.3s;
        }
        .toggle-slider::before {
            content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px;
            background: #94a3b8; border-radius: 50%; transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: rgba(99,102,241,0.5); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); background: #818cf8; }

        .alert {
            padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @media (max-width: 768px) { .nav-links { display: none; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <a href="toplantilar.php">Toplantılar</a>
            <a href="duyurular.php">Duyurular</a>
            <a href="oneri_sikayet.php">Öneri/Şikayet</a>
        </div>
        <div class="nav-auth">
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

    <div class="container">
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
</body>
</html>
