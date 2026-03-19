<?php
session_start();
require_once 'config/veritabani.php';

if (isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giris_verisi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if (empty($giris_verisi) || empty($sifre)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? OR eposta = ?");
        $stmt->execute([$giris_verisi, $giris_verisi]);
        $kullanici = $stmt->fetch();

        if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
            $_SESSION['kullanici_ad'] = $kullanici['ad'];
            $_SESSION['kullanici_rol'] = $kullanici['rol'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Geçersiz giriş bilgileri veya şifre.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Kulüp & Etkinlik</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #0a0e1a; overflow: hidden; position: relative;
        }
        .bg-gradient {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 80%, rgba(99,102,241,0.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 20%, rgba(139,92,246,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(6,182,212,0.05) 0%, transparent 70%);
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
        .login-wrapper {
            position: relative; z-index: 1; width: 100%; max-width: 420px; padding: 1rem;
            animation: cardEntrance 0.8s cubic-bezier(0.16,1,0.3,1) forwards; opacity: 0;
        }
        @keyframes cardEntrance { from { opacity: 0; transform: translateY(30px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .login-card {
            background: rgba(15,23,42,0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 1.25rem; padding: 2.5rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05) inset;
            position: relative; overflow: hidden;
        }
        .login-card::before {
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
        .toggle-password {
            position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #475569; cursor: pointer; padding: 0; display: flex; transition: color 0.3s;
        }
        .toggle-password:hover { color: #94a3b8; }
        .toggle-password svg { width: 18px; height: 18px; }
        .login-btn {
            width: 100%; padding: 0.8rem; border: none; border-radius: 0.625rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif;
            cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; margin-top: 0.5rem;
        }
        .login-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #4f46e5, #6d28d9); opacity: 0; transition: opacity 0.3s; }
        .login-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }
        .login-btn:hover::before { opacity: 1; }
        .login-btn:active { transform: translateY(0); }
        .login-btn span { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .login-btn span svg { width: 18px; height: 18px; }
        .error-msg {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5;
            padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8rem; margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.5rem; animation: shakeError 0.5s ease;
        }
        .error-msg svg { width: 18px; height: 18px; flex-shrink: 0; color: #ef4444; }
        @keyframes shakeError { 0%,100% { transform: translateX(0); } 15% { transform: translateX(-6px); } 30% { transform: translateX(5px); } 45% { transform: translateX(-4px); } 60% { transform: translateX(3px); } 75% { transform: translateX(-2px); } }
        .login-footer { text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,0.06); }
        .login-footer p { font-size: 0.8rem; color: #64748b; }
        .login-footer a { color: #818cf8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .login-footer a:hover { color: #a5b4fc; }

        .home-btn {
            width: 40px; height: 40px; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05); color: #94a3b8; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s; text-decoration: none;
        }
        .home-btn:hover { background: rgba(99,102,241,0.1); color: #818cf8; border-color: rgba(99,102,241,0.3); transform: translateY(-2px); }
        .home-btn svg { width: 20px; height: 20px; }

        /* Light theme overrides for login */
        body.light-theme { background: #f0f2f5; }
        body.light-theme .home-btn { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.08); color: #64748b; }
        body.light-theme .home-btn:hover { background: rgba(99,102,241,0.08); color: #6366f1; border-color: rgba(99,102,241,0.2); }
        body.light-theme .bg-gradient { background: radial-gradient(ellipse 80% 60% at 20% 80%, rgba(99,102,241,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 80% at 80% 20%, rgba(139,92,246,0.05) 0%, transparent 60%); }
        body.light-theme .orb-1 { background: rgba(99,102,241,0.08); }
        body.light-theme .orb-2 { background: rgba(139,92,246,0.06); }
        body.light-theme .orb-3 { background: rgba(6,182,212,0.05); }
        body.light-theme .grid-overlay { background-image: linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px); }
        body.light-theme .login-card { background: rgba(255,255,255,0.85); border-color: rgba(0,0,0,0.08); box-shadow: 0 25px 60px rgba(0,0,0,0.08); }
        body.light-theme .brand h1 { color: #0f172a; }
        body.light-theme .brand p { color: #94a3b8; }
        body.light-theme .form-group label { color: #64748b; }
        body.light-theme .input-wrapper input { background: #f8fafc; border-color: rgba(0,0,0,0.1); color: #1e293b; }
        body.light-theme .input-wrapper input:focus { background: #fff; border-color: rgba(99,102,241,0.5); }
        body.light-theme .input-wrapper input::placeholder { color: #94a3b8; }
        body.light-theme .login-footer { border-top-color: rgba(0,0,0,0.06); }
        body.light-theme .login-footer p { color: #94a3b8; }
        body.light-theme .error-msg { background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.15); color: #dc2626; }
    </style>
</head>
<body>
    <!-- Top Right Controls -->
    <div style="position: fixed; top: 1.5rem; right: 1.5rem; z-index: 50; display: flex; gap: 0.75rem;">
        <?php include 'includes/theme_toggle.php'; ?>
        <a href="index.php" class="home-btn" title="Ana Sayfa">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
        </a>
    </div>
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <h1>Giriş Yap</h1>
                <p>Kullanıcı adı veya e-posta ile giriş yapın</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı veya E-posta</label>
                    <div class="input-wrapper">
                        <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adı veya e-posta girin" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sifre">Şifre</label>
                    <div class="input-wrapper">
                        <input type="password" id="sifre" name="sifre" placeholder="Şifrenizi girin" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        Giriş Yap
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p>Hesabınız yok mu? <a href="kayit.php">Kayıt Olun</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('sifre');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />';
            }
        }
        document.getElementById('kullanici_adi').focus();
    </script>
<script src="content/js/theme.js"></script>
</body>
</html>
