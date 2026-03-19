<?php
session_start();
require_once 'config/veritabani.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $eposta = trim($_POST['eposta'] ?? '');
    $cinsiyet = $_POST['cinsiyet'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    if (empty($ad) || empty($soyad) || empty($kullanici_adi) || empty($eposta) || empty($cinsiyet) || empty($sifre)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (!in_array($cinsiyet, ['Erkek', 'Kadin'])) {
        $error = "Geçerli bir cinsiyet seçin.";
    } elseif ($sifre !== $sifre_tekrar) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (strlen($sifre) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta adresi girin.";
    } else {
        // Gelişmiş e-posta doğrulama
        $eposta_parcalari = explode('@', $eposta);
        $yerel_kisim = $eposta_parcalari[0]; // @ öncesi
        $domain = $eposta_parcalari[1]; // @ sonrası
        $domain_parcalari = explode('.', $domain);
        $domain_adi = implode('.', array_slice($domain_parcalari, 0, -1)); // TLD hariç domain
        $tld = end($domain_parcalari); // Son uzantı

        if (strlen($yerel_kisim) < 3) {
            $error = "E-posta adresinin '@' işaretinden önceki kısmı en az 3 karakter olmalıdır.";
        } elseif (strlen($domain_adi) < 3) {
            $error = "E-posta alan adı çok kısa. Lütfen geçerli bir e-posta adresi kullanın.";
        } elseif (strlen($tld) < 2 || strlen($tld) > 10) {
            $error = "E-posta uzantısı geçersiz. Lütfen geçerli bir e-posta adresi kullanın.";
        } else {
            // Sadece onaylı e-posta servisleri ile kayıt
            $onayli_domainler = [
                'gmail.com', 'googlemail.com',
                'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
                'yahoo.com', 'yahoo.com.tr',
                'icloud.com', 'me.com', 'mac.com',
                'yandex.com', 'yandex.com.tr',
                'protonmail.com', 'proton.me',
                'aol.com',
                'edu.tr', 'k12.tr'
            ];

            // Alt domain desteği (örn: ogr.university.edu.tr)
            $domain_lower = strtolower($domain);
            $izinli = false;
            foreach ($onayli_domainler as $onayli) {
                if ($domain_lower === $onayli || str_ends_with($domain_lower, '.' . $onayli)) {
                    $izinli = true;
                    break;
                }
            }

            if (!$izinli) {
                $error = "Yalnızca popüler e-posta servisleri (Gmail, Outlook, Yahoo, iCloud vb.) ile kayıt olabilirsiniz.";
            }
        }
    }

    if (empty($error)) {
        // Kullanıcı adı veya e-posta zaten var mı?
        $stmt = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR eposta = ?");
        $stmt->execute([$kullanici_adi, $eposta]);
        if ($stmt->fetch()) {
            $error = "Bu kullanıcı adı veya e-posta zaten kullanılıyor.";
        } else {
            $stmt = $db->prepare("INSERT INTO kullanicilar (ad, soyad, kullanici_adi, sifre, eposta, cinsiyet) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ad, $soyad, $kullanici_adi, $sifre, $eposta, $cinsiyet]);
            $success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Kulüp & Etkinlik</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0e1a;
            overflow-y: auto;
            position: relative;
        }
        .bg-gradient {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 20%, rgba(139, 92, 246, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(6, 182, 212, 0.05) 0%, transparent 70%);
            animation: bgShift 8s ease-in-out infinite alternate;
        }
        @keyframes bgShift { 0% { opacity: 0.8; transform: scale(1); } 100% { opacity: 1; transform: scale(1.1); } }
        .orb { position: fixed; border-radius: 50%; filter: blur(80px); z-index: 0; animation: orbFloat 6s ease-in-out infinite alternate; }
        .orb-1 { width: 300px; height: 300px; background: rgba(99,102,241,0.2); top: -100px; right: -50px; animation-duration: 7s; }
        .orb-2 { width: 250px; height: 250px; background: rgba(139,92,246,0.15); bottom: -80px; left: -60px; animation-delay: -3s; animation-duration: 8s; }
        .orb-3 { width: 200px; height: 200px; background: rgba(6,182,212,0.12); top: 40%; left: 60%; animation-delay: -1.5s; animation-duration: 9s; }
        @keyframes orbFloat { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(30px,-30px) scale(1.15); } }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .register-wrapper {
            position: relative; z-index: 1; width: 100%; max-width: 480px; padding: 1rem; margin: 2rem 0;
            animation: cardEntrance 0.8s cubic-bezier(0.16,1,0.3,1) forwards; opacity: 0;
        }
        @keyframes cardEntrance { from { opacity: 0; transform: translateY(30px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .register-card {
            background: rgba(15,23,42,0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 1.25rem; padding: 2.5rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05) inset;
            position: relative; overflow: hidden;
        }
        .register-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4); border-radius: 1.25rem 1.25rem 0 0;
        }
        .brand { text-align: center; margin-bottom: 2rem; }
        .brand-icon {
            width: 60px; height: 60px; margin: 0 auto 1rem;
            background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(139,92,246,0.2));
            border: 1px solid rgba(99,102,241,0.3); border-radius: 1rem;
            display: flex; align-items: center; justify-content: center;
            animation: iconPulse 3s ease-in-out infinite;
        }
        @keyframes iconPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.2); } 50% { box-shadow: 0 0 20px 5px rgba(99,102,241,0.15); } }
        .brand-icon svg { width: 28px; height: 28px; color: #818cf8; }
        .brand h1 { font-size: 1.35rem; font-weight: 700; color: #f1f5f9; letter-spacing: -0.02em; }
        .brand p { font-size: 0.8rem; color: #64748b; margin-top: 0.35rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-select {
            width: 100%; padding: 0.75rem 0.85rem 0.75rem 2.75rem; border-radius: 0.625rem;
            border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);
            color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s ease; appearance: none; cursor: pointer;
        }
        .form-select option { background: #1e293b; color: #f1f5f9; }
        .form-select:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: rgba(15,23,42,0.8); }
        .form-select:focus ~ .input-icon { color: #818cf8; }
        .form-group { margin-bottom: 1.25rem; position: relative; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #94a3b8; margin-bottom: 0.4rem; letter-spacing: 0.02em; }
        .input-wrapper { position: relative; }
        .input-wrapper .input-icon {
            position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #475569; pointer-events: none; transition: color 0.3s;
        }
        .input-wrapper input {
            width: 100%; padding: 0.75rem 0.85rem 0.75rem 2.75rem; border-radius: 0.625rem;
            border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);
            color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s ease;
        }
        .input-wrapper input::placeholder { color: #475569; }
        .input-wrapper input:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: rgba(15,23,42,0.8); }
        .input-wrapper input:focus ~ .input-icon { color: #818cf8; }
        .submit-btn {
            width: 100%; padding: 0.8rem; border: none; border-radius: 0.625rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif;
            cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; margin-top: 0.5rem;
        }
        .submit-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #4f46e5, #6d28d9); opacity: 0; transition: opacity 0.3s; }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }
        .submit-btn:hover::before { opacity: 1; }
        .submit-btn:active { transform: translateY(0); }
        .submit-btn span { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .submit-btn span svg { width: 18px; height: 18px; }
        .error-msg {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5;
            padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8rem; margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.5rem; animation: shakeError 0.5s ease;
        }
        .success-msg {
            background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7;
            padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8rem; margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .error-msg svg, .success-msg svg { width: 18px; height: 18px; flex-shrink: 0; }
        .error-msg svg { color: #ef4444; }
        .success-msg svg { color: #10b981; }
        @keyframes shakeError { 0%,100% { transform: translateX(0); } 15% { transform: translateX(-6px); } 30% { transform: translateX(5px); } 45% { transform: translateX(-4px); } 60% { transform: translateX(3px); } 75% { transform: translateX(-2px); } }
        .login-footer { text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,0.06); }
        .login-footer p { font-size: 0.8rem; color: #64748b; }
        .login-footer a { color: #818cf8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .login-footer a:hover { color: #a5b4fc; }
        .toggle-password {
            position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #475569; cursor: pointer; padding: 0; display: flex; transition: color 0.3s;
        }
        .toggle-password:hover { color: #94a3b8; }
        .toggle-password svg { width: 18px; height: 18px; }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <div class="register-wrapper">
        <div class="register-card">
            <div class="brand">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <h1>Kayıt Ol</h1>
                <p>Kulüp & Etkinlik platformuna katılın</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-msg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ad">Ad</label>
                        <div class="input-wrapper">
                            <input type="text" id="ad" name="ad" placeholder="Adınız" required value="<?php echo htmlspecialchars($ad ?? ''); ?>">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="soyad">Soyad</label>
                        <div class="input-wrapper">
                            <input type="text" id="soyad" name="soyad" placeholder="Soyadınız" required value="<?php echo htmlspecialchars($soyad ?? ''); ?>">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <div class="input-wrapper">
                        <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adınız" required value="<?php echo htmlspecialchars($kullanici_adi ?? ''); ?>">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 1 0-2.636 6.364M16.5 12V8.25" />
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eposta">E-posta</label>
                    <div class="input-wrapper">
                        <input type="email" id="eposta" name="eposta" placeholder="E-posta adresiniz" required value="<?php echo htmlspecialchars($eposta ?? ''); ?>">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cinsiyet">Cinsiyet</label>
                    <div class="input-wrapper">
                        <select id="cinsiyet" name="cinsiyet" class="form-select" required>
                            <option value="" disabled <?php echo empty($cinsiyet ?? '') ? 'selected' : ''; ?>>Cinsiyet seçin</option>
                            <option value="Erkek" <?php echo (($cinsiyet ?? '') === 'Erkek') ? 'selected' : ''; ?>>Erkek</option>
                            <option value="Kadin" <?php echo (($cinsiyet ?? '') === 'Kadin') ? 'selected' : ''; ?>>Kadın</option>
                        </select>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sifre">Şifre</label>
                        <div class="input-wrapper">
                            <input type="password" id="sifre" name="sifre" placeholder="En az 6 karakter" required>
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="sifre_tekrar">Şifre Tekrar</label>
                        <div class="input-wrapper">
                            <input type="password" id="sifre_tekrar" name="sifre_tekrar" placeholder="Şifrenizi tekrarlayın" required>
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                        </svg>
                        Kayıt Ol
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p>Zaten hesabınız var mı? <a href="giris.php">Giriş Yapın</a></p>
            </div>
        </div>
    </div>
</body>
</html>
