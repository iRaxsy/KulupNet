<?php
session_start();
require_once 'config/veritabani.php';
require_once 'kullanici_auth.php';

$giris_yapildi = kullanici_giris_kontrol();

// Profil sahibini belirle
$profil_id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['kullanici_id'] ?? 0);

if (!$profil_id) {
    header("Location: giris.php");
    exit;
}

$kendi_profili = $giris_yapildi && $profil_id == $_SESSION['kullanici_id'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$stmt->execute([$profil_id]);
$kullanici = $stmt->fetch();

if (!$kullanici) {
    header("Location: index.php");
    exit;
}

// Ban / Unban İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kullanici_islem']) && !$kendi_profili) {
    if (isset($_SESSION['kullanici_rol']) && in_array($_SESSION['kullanici_rol'], ['Admin', 'Yetkili'])) {
        $yeni_durum = $_POST['kullanici_islem'] === 'yasakla' ? 'Yasakli' : 'Aktif';
        $update_stmt = $db->prepare("UPDATE kullanicilar SET durum = ? WHERE id = ?");
        $update_stmt->execute([$yeni_durum, $profil_id]);
        header("Location: profil.php?id=" . $profil_id . "&mesaj=guncellendi");
        exit;
    }
}

// Sayfalama Ayarları
$sayfa_kayit_sayisi = 5;

// Kulüp Üyelikleri Sayfalama
$k_sayfa = isset($_GET['k_sayfa']) ? max(1, (int)$_GET['k_sayfa']) : 1;
$k_offset = ($k_sayfa - 1) * $sayfa_kayit_sayisi;

$toplam_k_stmt = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = ?");
$toplam_k_stmt->execute([$profil_id]);
$toplam_k = $toplam_k_stmt->fetchColumn();
$toplam_k_sayfa = ceil($toplam_k / $sayfa_kayit_sayisi);

$stmt = $db->prepare("SELECT ku.*, k.ad as kulup_adi FROM kulup_uyelikleri ku JOIN kulupler k ON ku.kulup_id = k.id WHERE ku.kullanici_id = ? ORDER BY ku.katilma_tarihi DESC LIMIT $sayfa_kayit_sayisi OFFSET $k_offset");
$stmt->execute([$profil_id]);
$kulupler = $stmt->fetchAll();

// Etkinlik Katılımları Sayfalama
$e_sayfa = isset($_GET['e_sayfa']) ? max(1, (int)$_GET['e_sayfa']) : 1;
$e_offset = ($e_sayfa - 1) * $sayfa_kayit_sayisi;

$toplam_e_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlik_katilimlari WHERE kullanici_id = ?");
$toplam_e_stmt->execute([$profil_id]);
$toplam_e = $toplam_e_stmt->fetchColumn();
$toplam_e_sayfa = ceil($toplam_e / $sayfa_kayit_sayisi);

$stmt = $db->prepare("SELECT ek.*, e.baslik, e.tarih, e.konum, k.ad as kulup_adi FROM etkinlik_katilimlari ek JOIN etkinlikler e ON ek.etkinlik_id = e.id LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE ek.kullanici_id = ? ORDER BY e.tarih DESC LIMIT $sayfa_kayit_sayisi OFFSET $e_offset");
$stmt->execute([$profil_id]);
$etkinlikler = $stmt->fetchAll();

// Gizlilik kontrolleri (başkasının profilinde)
$eposta_goster = $kendi_profili || !$kullanici['gizli_eposta'];
$kulupler_goster = $kendi_profili || !$kullanici['gizli_kulupler'];
$etkinlikler_goster = $kendi_profili || !$kullanici['gizli_etkinlikler'];
$cinsiyet_goster = $kendi_profili || !$kullanici['gizli_cinsiyet'];

$rol_renkleri = [
    'Admin' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Yetkili' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Organizatör' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(99,102,241,0.15)', 'color' => '#818cf8'],
];
$kulup_rol_renkleri = [
    'Yönetici' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Başkan' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Başkan Yardımcısı' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
];
$renk = $rol_renkleri[$kullanici['rol']] ?? $rol_renkleri['Üye'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?> - Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="content/css/theme.css">
    <script>if(localStorage.getItem('kulupnet-theme')==='light')document.documentElement.style.background='#f0f2f5';</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #f1f5f9; min-height: 100vh; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: rgba(10,14,26,0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.06); padding: 0 2rem; height: 70px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-size: 1.25rem; font-weight: 800; color: #f1f5f9; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand .brand-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .nav-links { display: flex; align-items: center; gap: 0.25rem; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .nav-links a:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
        .nav-links a.active { color: #818cf8; background: rgba(99,102,241,0.1); }
        .nav-auth { display: flex; align-items: center; gap: 0.75rem; }
        .btn-ghost { color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; }
        .btn-ghost:hover { color: #f1f5f9; }
        .user-avatar-nav { width: 36px; height: 36px; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #818cf8; text-decoration: none; }

        .container { max-width: 900px; margin: 0 auto; padding: 6rem 2rem 3rem; }

        /* Profile Header */
        .profile-header {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1.25rem; padding: 2.5rem; margin-bottom: 1.5rem; position: relative; overflow: hidden;
        }
        .profile-header::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 80px;
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1));
        }
        .profile-top { display: flex; align-items: flex-end; gap: 1.5rem; position: relative; z-index: 1; margin-bottom: 1.5rem; }
        .profile-avatar {
            width: 80px; height: 80px; border-radius: 1rem;
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3));
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 2rem; color: #818cf8;
            border: 3px solid #0a0e1a; flex-shrink: 0;
        }
        .profile-info h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .profile-info .username { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; }
        .profile-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .badge {
            padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 0.25rem;
        }
        .profile-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; position: relative; z-index: 1; }
        .detail-item { display: flex; align-items: center; gap: 0.75rem; }
        .detail-item .detail-icon {
            width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;
            background: rgba(99,102,241,0.1); flex-shrink: 0;
        }
        .detail-item .detail-icon svg { width: 16px; height: 16px; color: #818cf8; }
        .detail-item .detail-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .detail-item .detail-value { font-size: 0.85rem; color: #e2e8f0; font-weight: 500; }

        /* Sections */
        .section-card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; margin-bottom: 1.5rem; overflow: hidden;
        }
        .section-card-header {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-card-header h2 { font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; color: #e2e8f0; }
        .section-card-header h2 svg { width: 18px; height: 18px; color: #818cf8; }
        .section-card-header .count-badge { background: rgba(99,102,241,0.15); color: #818cf8; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 1rem; font-weight: 600; }
        .list-item {
            display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;
        }
        .list-item:hover { background: rgba(255,255,255,0.02); }
        .list-item:last-child { border-bottom: none; }
        .list-item-avatar {
            width: 40px; height: 40px; border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }
        .list-item-info { flex: 1; min-width: 0; }
        .list-item-name { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }
        .list-item-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.1rem; }
        .empty-section { text-align: center; padding: 2rem; color: #475569; font-size: 0.85rem; }
        .empty-section svg { width: 40px; height: 40px; display: block; margin: 0 auto 0.5rem; color: #334155; }
        .hidden-section { text-align: center; padding: 2rem; color: #475569; font-size: 0.85rem; }
        .hidden-section svg { width: 32px; height: 32px; display: block; margin: 0 auto 0.5rem; color: #334155; }
        .edit-btn {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
            color: #818cf8; border-radius: 0.5rem; text-decoration: none; font-size: 0.8rem; font-weight: 600;
            transition: all 0.2s;
        }
        .edit-btn:hover { background: rgba(99,102,241,0.2); }
        .edit-btn svg { width: 14px; height: 14px; }
        .ban-btn {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem;
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);
            color: #f87171; border-radius: 0.5rem; text-decoration: none; font-size: 0.8rem; font-weight: 600;
            transition: all 0.2s; border: none; cursor: pointer; font-family: 'Inter', sans-serif;
        }
        .ban-btn:hover { background: rgba(239,68,68,0.2); }
        .unban-btn {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem;
            background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);
            color: #34d399; border-radius: 0.5rem; text-decoration: none; font-size: 0.8rem; font-weight: 600;
            transition: all 0.2s; border: none; cursor: pointer; font-family: 'Inter', sans-serif;
        }
        .unban-btn:hover { background: rgba(16,185,129,0.2); }
        
        .status-badge { padding: 0.15rem 0.5rem; border-radius: 1rem; font-size: 0.65rem; font-weight: 600; margin-left: auto; flex-shrink: 0; }
        .status-active { background: rgba(16,185,129,0.15); color: #34d399; }
        .status-ended { background: rgba(100,116,139,0.15); color: #94a3b8; }
        
        /* Simple Pagination UI */
        .simple-pagination { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.04); background: rgba(15,23,42,0.2); }
        .page-btn { padding: 0.5rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.05); color: #94a3b8; text-decoration: none; font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.08); transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; }
        .page-btn:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; border-color: rgba(255,255,255,0.15); }
        .page-btn.disabled { opacity: 0.3; pointer-events: none; cursor: not-allowed; }

        /* SweetAlert2 Dark Theme Enhancements */
        .swal2-popup {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 1.25rem !important;
            color: #f1f5f9 !important;
        }
        .swal2-title, .swal2-html-container { color: #f1f5f9 !important; }
        .swal2-confirm { border-radius: 0.625rem !important; font-weight: 600 !important; }
        .swal2-cancel {
            background: rgba(255,255,255,0.05) !important;
            color: #94a3b8 !important;
            border-radius: 0.625rem !important;
        }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .profile-header, .section-card { animation: fadeInUp 0.6s ease forwards; }
        @media (max-width: 768px) {
            .profile-top { flex-direction: column; align-items: center; text-align: center; }
            .profile-details { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><span class="brand-dot"></span> KulüpNet</a>
        <div class="nav-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
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
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-avatar"><?php echo mb_strtoupper(mb_substr($kullanici['ad'], 0, 1)); ?></div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($kullanici['ad'] . ' ' . $kullanici['soyad']); ?></h1>
                    <div class="username">@<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></div>
                    <div class="profile-badges">
                        <span class="badge" style="background: <?php echo $renk['bg']; ?>; color: <?php echo $renk['color']; ?>;"><?php echo htmlspecialchars($kullanici['rol']); ?></span>
                        <?php if (isset($kullanici['durum']) && $kullanici['durum'] === 'Yasakli'): ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                Yasaklı
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($kendi_profili): ?>
                            <a href="ayarlar.php" class="edit-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                Düzenle
                            </a>
                        <?php elseif (isset($_SESSION['kullanici_rol']) && in_array($_SESSION['kullanici_rol'], ['Admin', 'Yetkili'])): ?>
                            <form id="banForm" method="POST" style="margin: 0;">
                                <input type="hidden" name="kullanici_islem" id="banActionInput" value="">
                                <?php if (isset($kullanici['durum']) && $kullanici['durum'] === 'Yasakli'): ?>
                                    <button type="button" onclick="confirmBanAction('kaldir', 'Yasağı Kaldır', 'Bu kullanıcının yasağını kaldırmak istediğinize emin misiniz?', '#10b981')" class="unban-btn">Yasağı Kaldır</button>
                                <?php else: ?>
                                    <button type="button" onclick="confirmBanAction('yasakla', 'Kullanıcıyı Yasakla', 'Bu kullanıcıyı yasaklamak istediğinize emin misiniz? Yasaklı kullanıcılar etkinliklere katılamaz.', '#ef4444')" class="ban-btn">Kullanıcıyı Yasakla</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="profile-details">
                <?php if ($eposta_goster): ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </div>
                    <div><div class="detail-label">E-posta</div><div class="detail-value"><?php echo htmlspecialchars($kullanici['eposta']); ?></div></div>
                </div>
                <?php endif; ?>
                <?php if ($cinsiyet_goster): ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </div>
                    <div><div class="detail-label">Cinsiyet</div><div class="detail-value"><?php echo ($kullanici['cinsiyet'] === 'Kadin') ? 'Kadın' : htmlspecialchars($kullanici['cinsiyet']); ?></div></div>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </div>
                    <div><div class="detail-label">Kayıt Tarihi</div><div class="detail-value"><?php echo date('d.m.Y', strtotime($kullanici['kayit_tarihi'])); ?></div></div>
                </div>
            </div>
        </div>

        <!-- Kulüpler -->
        <div class="section-card">
            <div class="section-card-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                    Katıldığı Kulüpler
                </h2>
                <?php if ($kulupler_goster): ?><span class="count-badge"><?php echo $toplam_k; ?></span><?php endif; ?>
            </div>
            <?php if (!$kulupler_goster): ?>
                <div class="hidden-section">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    <p>Bu bilgi gizlenmiş.</p>
                </div>
            <?php elseif (empty($kulupler)): ?>
                <div class="empty-section">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                    <p>Henüz bir kulübe katılmamış.</p>
                </div>
            <?php else: ?>
                <?php foreach ($kulupler as $k):
                    $kr = $kulup_rol_renkleri[$k['kulup_rol']] ?? $kulup_rol_renkleri['Üye'];
                ?>
                <div class="list-item">
                    <div class="list-item-avatar" style="background: rgba(99,102,241,0.15); color: #818cf8;"><?php echo mb_strtoupper(mb_substr($k['kulup_adi'], 0, 1)); ?></div>
                    <div class="list-item-info">
                        <div class="list-item-name"><a href="kulup_detay.php?id=<?php echo $k['kulup_id']; ?>" style="color:#e2e8f0;text-decoration:none;"><?php echo htmlspecialchars($k['kulup_adi']); ?></a></div>
                        <div class="list-item-meta">Katılma: <?php echo date('d.m.Y', strtotime($k['katilma_tarihi'])); ?></div>
                    </div>
                    <span class="badge" style="background: <?php echo $kr['bg']; ?>; color: <?php echo $kr['color']; ?>;"><?php echo htmlspecialchars($k['kulup_rol']); ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="simple-pagination">
                    <a href="?id=<?php echo $profil_id; ?>&k_sayfa=<?php echo ($k_sayfa - 1); ?>&e_sayfa=<?php echo $e_sayfa; ?>" class="page-btn <?php echo ($k_sayfa <= 1) ? 'disabled' : ''; ?>">← Geri</a>
                    <span style="font-size:0.75rem;color:#475569;font-weight:500;">Kulüpler (<?php echo $k_sayfa; ?>/<?php echo max(1, $toplam_k_sayfa); ?>)</span>
                    <a href="?id=<?php echo $profil_id; ?>&k_sayfa=<?php echo ($k_sayfa + 1); ?>&e_sayfa=<?php echo $e_sayfa; ?>" class="page-btn <?php echo ($k_sayfa >= $toplam_k_sayfa) ? 'disabled' : ''; ?>">İleri →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Etkinlikler -->
        <div class="section-card">
            <div class="section-card-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    Katıldığı Etkinlikler
                </h2>
                <?php if ($etkinlikler_goster): ?><span class="count-badge"><?php echo $toplam_e; ?></span><?php endif; ?>
            </div>
            <?php if (!$etkinlikler_goster): ?>
                <div class="hidden-section">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    <p>Bu bilgi gizlenmiş.</p>
                </div>
            <?php elseif (empty($etkinlikler)): ?>
                <div class="empty-section">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    <p>Henüz bir etkinliğe katılmamış.</p>
                </div>
            <?php else: ?>
                <?php foreach ($etkinlikler as $e): 
                    $bitmis = strtotime($e['tarih']) < time();
                ?>
                <div class="list-item">
                    <div class="list-item-avatar" style="background: rgba(16,185,129,0.15); color: #34d399;"><?php echo date('d', strtotime($e['tarih'])); ?></div>
                    <div class="list-item-info">
                        <div class="list-item-name"><?php echo htmlspecialchars($e['baslik']); ?></div>
                        <div class="list-item-meta"><?php echo htmlspecialchars($e['kulup_adi'] ?? 'Genel'); ?> · <?php echo date('d.m.Y H:i', strtotime($e['tarih'])); ?> · <?php echo htmlspecialchars($e['konum'] ?? ''); ?></div>
                    </div>
                    <?php if ($bitmis): ?>
                        <span class="status-badge status-ended">Bitti</span>
                    <?php else: ?>
                        <span class="status-badge status-active">Aktif</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="simple-pagination">
                    <a href="?id=<?php echo $profil_id; ?>&e_sayfa=<?php echo ($e_sayfa - 1); ?>&k_sayfa=<?php echo $k_sayfa; ?>" class="page-btn <?php echo ($e_sayfa <= 1) ? 'disabled' : ''; ?>">← Geri</a>
                    <span style="font-size:0.75rem;color:#475569;font-weight:500;">Etkinlikler (<?php echo $e_sayfa; ?>/<?php echo max(1, $toplam_e_sayfa); ?>)</span>
                    <a href="?id=<?php echo $profil_id; ?>&e_sayfa=<?php echo ($e_sayfa + 1); ?>&k_sayfa=<?php echo $k_sayfa; ?>" class="page-btn <?php echo ($e_sayfa >= $toplam_e_sayfa) ? 'disabled' : ''; ?>">İleri →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function confirmBanAction(actionValue, title, text, confirmColor) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: 'rgba(255,255,255,0.1)',
                confirmButtonText: 'Evet, Onaylıyorum',
                cancelButtonText: 'İptal',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#f1f5f9'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('banActionInput').value = actionValue;
                    document.getElementById('banForm').submit();
                }
            });
        }
        
        <?php if (isset($_GET['mesaj']) && $_GET['mesaj'] === 'guncellendi'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Başarılı!',
                text: 'Kullanıcının yasak durumu güncellendi.',
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#f1f5f9'
            });
        });
        <?php endif; ?>
    </script>
<script src="content/js/theme.js"></script>
</body>
</html>
