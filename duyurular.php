<?php
require_once 'includes/csrf.php';
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';

giris_gerekli($db);

$kullanici = kullanici_bilgi($db);
$kullanici_id = $_SESSION['kullanici_id'];
$giris_yapildi = true;

// Kullanıcının rolü
$kullanici_rol = $kullanici['rol'] ?? 'Üye';

// Kullanıcının yönetici/başkan/bşk yrd olduğu kulüpler
$yonetim_kulupler = $db->prepare("
    SELECT ku.kulup_id, ku.kulup_rol, k.ad as kulup_adi
    FROM kulup_uyelikleri ku
    JOIN kulupler k ON ku.kulup_id = k.id
    WHERE ku.kullanici_id = ? AND ku.kulup_rol IN ('Yönetici','Başkan','Başkan Yardımcısı')
");
$yonetim_kulupler->execute([$kullanici_id]);
$yonetim_kulupler = $yonetim_kulupler->fetchAll();

// Site duyurusu atabilir mi?
$site_duyuru_yetkisi = in_array($kullanici_rol, ['Admin', 'Yetkili']);

// Duyuru oluşturma
$mesaj = '';
$mesaj_tur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duyuru_olustur'])) {
    csrf_zorunlu();
    $duyuru_metni = trim($_POST['duyuru'] ?? '');
    $duyuru_turu = $_POST['duyuru_turu'] ?? '';

    if (empty($duyuru_metni)) {
        $mesaj = 'Duyuru metni boş bırakılamaz.';
        $mesaj_tur = 'error';
    } elseif ($duyuru_turu === 'site') {
        if (!$site_duyuru_yetkisi) {
            $mesaj = 'Site duyurusu oluşturma yetkiniz yok.';
            $mesaj_tur = 'error';
        } else {
            $stmt = $db->prepare("INSERT INTO duyurular (tur, kulup_id, duyuru) VALUES (?, NULL, ?)");
            $stmt->execute(['Site Duyurusu', $duyuru_metni]);
            $mesaj = 'Site duyurusu başarıyla oluşturuldu!';
            $mesaj_tur = 'success';
        }
    } elseif (strpos($duyuru_turu, 'kulup_') === 0) {
        $kulup_id = (int)substr($duyuru_turu, 6);
        // Yetkisi var mı kontrol
        $yetki_var = false;
        foreach ($yonetim_kulupler as $yk) {
            if ((int)$yk['kulup_id'] === $kulup_id) {
                $yetki_var = true;
                $kulup_adi = $yk['kulup_adi'];
                break;
            }
        }
        if (!$yetki_var) {
            $mesaj = 'Bu kulüp için duyuru oluşturma yetkiniz yok.';
            $mesaj_tur = 'error';
        } else {
            $tur_etiketi = $kulup_adi . ' Duyurusu';
            $stmt = $db->prepare("INSERT INTO duyurular (tur, kulup_id, duyuru) VALUES (?, ?, ?)");
            $stmt->execute([$tur_etiketi, $kulup_id, $duyuru_metni]);
            $mesaj = 'Kulüp duyurusu başarıyla oluşturuldu!';
            $mesaj_tur = 'success';
        }
    } else {
        $mesaj = 'Geçersiz duyuru türü.';
        $mesaj_tur = 'error';
    }
}

// Kullanıcının üye olduğu kulüp ID'leri
$uye_kulup_ids = $db->prepare("SELECT kulup_id FROM kulup_uyelikleri WHERE kullanici_id = ?");
$uye_kulup_ids->execute([$kullanici_id]);
$uye_kulup_idler = $uye_kulup_ids->fetchAll(PDO::FETCH_COLUMN);

// Kayıt tarihi
$kayit_tarihi = $kullanici['kayit_tarihi'];

// Sayfalama Ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Duyuruları çek
// Site duyuruları: kulup_id IS NULL ve tarih >= kayit_tarihi
// Kulüp duyuruları: kulup_id IN (üye olunan kulüpler) ve tarih >= kayit_tarihi
$duyurular = [];
$toplam_kayit = 0;

if (!empty($uye_kulup_idler)) {
    $placeholders = implode(',', array_fill(0, count($uye_kulup_idler), '?'));
    $params = array_merge([$kayit_tarihi], $uye_kulup_idler, [$kayit_tarihi]);
    
    // Toplam kayıt sayısı
    $count_sql = "
        SELECT COUNT(*) FROM duyurular
        WHERE (kulup_id IS NULL AND tarih >= ?)
           OR (kulup_id IN ($placeholders) AND tarih >= ?)
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $toplam_kayit = $count_stmt->fetchColumn();

    // Verileri çek
    $sql = "
        SELECT * FROM duyurular
        WHERE (kulup_id IS NULL AND tarih >= ?)
           OR (kulup_id IN ($placeholders) AND tarih >= ?)
        ORDER BY tarih DESC
        LIMIT $sayfa_kayit_sayisi OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $duyurular = $stmt->fetchAll();
} else {
    // Sadece site duyuruları
    // Toplam kayıt sayısı
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM duyurular WHERE kulup_id IS NULL AND tarih >= ?");
    $count_stmt->execute([$kayit_tarihi]);
    $toplam_kayit = $count_stmt->fetchColumn();

    // Verileri çek
    $stmt = $db->prepare("SELECT * FROM duyurular WHERE kulup_id IS NULL AND tarih >= ? ORDER BY tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $offset");
    $stmt->execute([$kayit_tarihi]);
    $duyurular = $stmt->fetchAll();
}

$toplam_sayfa = ceil($toplam_kayit / $sayfa_kayit_sayisi);

$duyuru_formu_goster = $site_duyuru_yetkisi || !empty($yonetim_kulupler);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyurular - KulüpNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/tokens.css">
    <link rel="stylesheet" href="content/css/ui.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <style>
        .container { max-width: 900px; margin: 0 auto; padding: calc(var(--nav-height) + var(--sp-12)) var(--sp-8) var(--sp-12); }

        /* Page Header */
        .page-header { margin-bottom: var(--sp-8); }
        .page-header h1 {
            font-size: var(--fs-3xl); font-weight: var(--fw-extrabold); margin-bottom: var(--sp-2);
        }
        .page-header p { color: var(--c-text-subtle); font-size: var(--fs-base); }

        /* Form Card (top gradient bar) */
        .form-card { position: relative; overflow: hidden; }
        .form-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--c-primary), var(--c-secondary), var(--c-cyan));
        }
        .form-card h2 { display: flex; align-items: center; gap: var(--sp-2); }
        .form-card h2 svg { width: 20px; height: 20px; color: var(--c-primary-light); }

        .form-select, .form-textarea {
            width: 100%; padding: var(--sp-3) var(--sp-4); border-radius: var(--r-md);
            border: 1px solid var(--c-border); background: rgba(15,23,42,0.6);
            color: var(--c-text); font-size: var(--fs-base); font-family: var(--font-family);
            outline: none; transition: all var(--dur-normal) var(--ease-out);
        }
        .form-select:focus, .form-textarea:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .form-select option { background: var(--c-bg-elevated); color: var(--c-text); }

        .submit-btn {
            padding: var(--sp-3) var(--sp-6); border: none; border-radius: var(--r-md);
            background: linear-gradient(135deg, var(--c-primary), #7c3aed); color: white;
            font-size: var(--fs-base); font-weight: var(--fw-semibold); font-family: var(--font-family);
            cursor: pointer; transition: all var(--dur-normal) var(--ease-out);
            display: inline-flex; align-items: center; gap: var(--sp-2);
        }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-glow); }
        .submit-btn svg { width: 16px; height: 16px; }

        /* Flash messages */
        .msg { padding: var(--sp-3) var(--sp-4); border-radius: var(--r-md); font-size: var(--fs-sm); margin-bottom: var(--sp-4); display: flex; align-items: center; gap: var(--sp-2); }
        .msg svg { width: 18px; height: 18px; flex-shrink: 0; }
        .msg-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .msg-success svg { color: var(--c-success); }
        .msg-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .msg-error svg { color: var(--c-error); }

        /* Duyuru Cards */
        .duyuru-list { display: flex; flex-direction: column; gap: var(--sp-4); }
        .duyuru-card {
            background: rgba(30,41,59,0.5); border: 1px solid var(--c-border);
            border-radius: var(--r-lg); padding: var(--sp-5) var(--sp-6);
            transition: transform var(--dur-normal) var(--ease-out), border-color var(--dur-normal), box-shadow var(--dur-normal);
        }
        .duyuru-card:hover { border-color: rgba(99,102,241,0.2); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .duyuru-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-3); flex-wrap: wrap; gap: var(--sp-2); }
        .duyuru-badge { padding: 0.25rem 0.75rem; border-radius: var(--r-pill); font-size: 0.7rem; font-weight: var(--fw-semibold); display: inline-flex; align-items: center; gap: 0.35rem; }
        .duyuru-badge svg { width: 12px; height: 12px; }
        .badge-site { background: rgba(99,102,241,0.15); color: var(--c-primary-light); }
        .badge-kulup { background: rgba(16,185,129,0.15); color: #34d399; }
        .duyuru-tarih { font-size: var(--fs-xs); color: var(--c-text-subtle); display: flex; align-items: center; gap: 0.35rem; }
        .duyuru-tarih svg { width: 14px; height: 14px; }
        .duyuru-icerik { font-size: var(--fs-base); color: #cbd5e1; line-height: var(--lh-relaxed); }

        /* Empty state (page-specific) */
        .empty-state { background: rgba(30,41,59,0.3); border: 1px dashed var(--c-border); border-radius: var(--r-lg); padding: var(--sp-16) var(--sp-8); }

        /* Pagination (page-info text) */
        .pagination { gap: var(--sp-6); margin-top: var(--sp-12); }
        .page-info { font-size: var(--fs-sm); color: var(--c-text-subtle); font-weight: var(--fw-medium); }

        @media (max-width: 768px) {
            .container { padding: calc(var(--nav-height) + var(--sp-8)) var(--sp-4) var(--sp-8); }
            .pagination { flex-direction: column; gap: var(--sp-4); }
        }
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
            <a href="duyurular.php" class="active">Duyurular</a>
            <a href="oneri_sikayet.php">Öneri/Şikayet</a>
        </div>
        <div class="nav-auth">
            <?php include 'includes/theme_toggle.php'; ?>
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
        </div>
    </nav>
    <div class="drawer-overlay" aria-hidden="true"></div>
    <aside class="drawer" role="dialog" aria-label="Menü">
        <div class="drawer-header"><span style="font-weight:700;font-size:1.1rem;">Menü</span><button class="drawer-close" aria-label="Kapat"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
        <div class="drawer-links"><a href="index.php">Ana Sayfa</a><a href="kulupler.php">Kulüpler</a><a href="etkinlikler.php">Etkinlikler</a><a href="toplantilar.php">Toplantılar</a><a href="duyurular.php" class="active">Duyurular</a><a href="oneri_sikayet.php">Öneri/Şikayet</a></div>
        <div class="drawer-auth"><a href="profil.php" class="btn-ghost">Profil</a><a href="cikis.php" class="btn-ghost">Çıkış</a></div>
    </aside>

    <div class="container" id="main">
        <div class="page-header">
            <h1>📢 Duyurular</h1>
            <p>Site ve kulüp duyurularını görüntüleyin</p>
        </div>

        <?php if ($mesaj): ?>
            <div class="msg msg-<?php echo $mesaj_tur; ?>">
                <?php if ($mesaj_tur === 'success'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <?php if ($duyuru_formu_goster): ?>
        <div class="form-card">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Yeni Duyuru Oluştur
            </h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="duyuru_turu">Duyuru Türü</label>
                    <select name="duyuru_turu" id="duyuru_turu" class="form-select" required>
                        <option value="">Seçiniz...</option>
                        <?php if ($site_duyuru_yetkisi): ?>
                            <option value="site">Site Duyurusu</option>
                        <?php endif; ?>
                        <?php foreach ($yonetim_kulupler as $yk): ?>
                            <option value="kulup_<?php echo $yk['kulup_id']; ?>"><?php echo htmlspecialchars($yk['kulup_adi']); ?> Kulübü Duyurusu</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="duyuru">Duyuru İçeriği</label>
                    <textarea name="duyuru" id="duyuru" class="form-textarea" placeholder="Duyuru metnini yazın..." required></textarea>
                </div>
                <button type="submit" name="duyuru_olustur" class="submit-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                    Duyuru Yayınla
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!empty($duyurular)): ?>
        <div class="duyuru-list">
            <?php foreach ($duyurular as $d): ?>
            <div class="duyuru-card" data-animate>
                <div class="duyuru-card-header">
                    <span class="duyuru-badge <?php echo $d['kulup_id'] ? 'badge-kulup' : 'badge-site'; ?>">
                        <?php if ($d['kulup_id']): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($d['tur']); ?>
                    </span>
                    <span class="duyuru-tarih">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <?php echo date('d.m.Y H:i', strtotime($d['tarih'])); ?>
                    </span>
                </div>
                <div class="duyuru-icerik"><?php echo nl2br(htmlspecialchars($d['duyuru'])); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($toplam_sayfa > 1): ?>
        <div class="pagination">
            <a href="?sayfa=<?php echo $sayfa - 1; ?>" class="page-btn <?php echo $sayfa <= 1 ? 'disabled' : ''; ?>">← Geri</a>
            <div class="page-info"><?php echo $sayfa; ?> / <?php echo $toplam_sayfa; ?></div>
            <a href="?sayfa=<?php echo $sayfa + 1; ?>" class="page-btn <?php echo $sayfa >= $toplam_sayfa ? 'disabled' : ''; ?>">İleri →</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <p>Henüz görüntüleyebileceğiniz duyuru bulunmuyor.</p>
        </div>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <div class="footer-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <a href="toplantilar.php">Toplantılar</a>
            <a href="duyurular.php">Duyurular</a>
            <a href="profil.php">Profil</a>
            <a href="oneri_sikayet.php">Öneri/Şikayet</a>
        </div>
        <p>&copy; 2026 KulüpNet. Tüm hakları saklıdır.</p>
    </footer>
<script src="content/js/nav.js"></script>
<script src="content/js/animations.js"></script>
<script src="content/js/toast.js"></script>
<script src="content/js/theme.js"></script>
</body>
</html>
