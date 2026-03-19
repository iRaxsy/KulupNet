<?php
session_start();
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';
giris_gerekli();

$mesaj = ''; $hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        .user-avatar-nav { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }
        .container { max-width: 700px; margin: 0 auto; padding: 6rem 2rem 3rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-header p { font-size: 0.875rem; color: #64748b; }

        .form-card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; padding: 2rem; margin-bottom: 1.5rem;
        }
        .form-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .form-card .desc { font-size: 0.8rem; color: #64748b; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #94a3b8; margin-bottom: 0.4rem; }

        .radio-group { display: flex; gap: 1rem; margin-bottom: 1.25rem; }
        .radio-option {
            flex: 1; position: relative;
        }
        .radio-option input { position: absolute; opacity: 0; width: 0; height: 0; }
        .radio-option label {
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.75rem 1rem; border-radius: 0.625rem; cursor: pointer;
            border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);
            color: #94a3b8; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;
        }
        .radio-option input:checked + label {
            border-color: rgba(99,102,241,0.5); background: rgba(99,102,241,0.1); color: #818cf8;
        }
        .radio-option label:hover { border-color: rgba(255,255,255,0.15); }

        textarea {
            width: 100%; padding: 0.75rem 1rem; border-radius: 0.625rem;
            border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);
            color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.3s; min-height: 120px; resize: vertical;
        }
        textarea::placeholder { color: #475569; }
        textarea:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

        .submit-btn {
            padding: 0.75rem 2rem; border: none; border-radius: 0.625rem;
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s;
            font-family: 'Inter', sans-serif; width: 100%;
        }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }

        .alert { padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }

        /* History */
        .section-card { background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06); border-radius: 1rem; overflow: hidden; }
        .section-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; }
        .section-card-header h2 { font-size: 1rem; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 0.5rem; }
        .section-card-header h2 svg { width: 18px; height: 18px; color: #818cf8; }
        .count-badge { background: rgba(99,102,241,0.15); color: #818cf8; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 1rem; font-weight: 600; }
        .history-item { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .history-item:last-child { border-bottom: none; }
        .history-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
        .history-type { padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; }
        .type-oneri { background: rgba(16,185,129,0.15); color: #34d399; }
        .type-sikayet { background: rgba(239,68,68,0.15); color: #f87171; }
        .history-date { font-size: 0.7rem; color: #64748b; }
        .history-text { font-size: 0.85rem; color: #94a3b8; line-height: 1.5; }
        .empty-section { text-align: center; padding: 2rem; color: #475569; font-size: 0.85rem; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); }
        .page-link { padding: 0.4rem 0.8rem; border-radius: 0.5rem; background: rgba(15,23,42,0.6); color: #94a3b8; text-decoration: none; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.08); transition: all 0.2s; }
        .page-link:hover { background: rgba(255,255,255,0.05); color: #f1f5f9; }
        .page-link.active { background: rgba(99,102,241,0.15); color: #818cf8; border-color: rgba(99,102,241,0.3); }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .form-card, .section-card { animation: fadeInUp 0.6s ease forwards; }
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
            <a href="oneri_sikayet.php" class="active">Öneri/Şikayet</a>
        </div>
        <div class="nav-auth">
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
    <div class="container">
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

        <div class="form-card">
            <h2>Yeni Geri Bildirim</h2>
            <p class="desc">Öneri veya şikayetinizi aşağıdaki form ile iletebilirsiniz.</p>
            <form method="POST">
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
        <div class="section-card">
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
</body>
</html>
