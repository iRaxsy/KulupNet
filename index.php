<?php
session_start();
require_once 'config/veritabani.php';

$giris_yapildi = isset($_SESSION['kullanici_id']);

// İstatistikler
$kulup_sayisi = $db->query("SELECT COUNT(*) FROM kulupler")->fetchColumn();
$etkinlik_sayisi = $db->query("SELECT COUNT(*) FROM etkinlikler")->fetchColumn();
$aktif_etkinlik = $db->query("SELECT COUNT(*) FROM etkinlikler WHERE tarih >= NOW()")->fetchColumn();

// Üye sayısı
$uye_sayisi = 0;
try { $uye_sayisi = $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(); } catch(Exception $e) {}

// Son kulüpler
$son_kulupler = $db->query("SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = k.id) as uye_sayisi FROM kulupler k ORDER BY k.id DESC LIMIT 6")->fetchAll();

// Yaklaşan etkinlikler
$yaklasen = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.tarih >= NOW() ORDER BY e.tarih ASC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kulüp & Etkinlik Platformu</title>
    <meta name="description" content="Kulüp ve etkinlik yönetim platformu. Kulüplere katılın, etkinliklere katılın, topluluğunuzu büyütün.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #f1f5f9; min-height: 100vh; }

        /* Navbar */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(10, 14, 26, 0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 0 2rem; height: 70px; display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { font-size: 1.25rem; font-weight: 800; color: #f1f5f9; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand .brand-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .nav-links { display: flex; align-items: center; gap: 0.25rem; }
        .nav-links a {
            color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem;
            font-size: 0.875rem; font-weight: 500; transition: all 0.2s;
        }
        .nav-links a:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
        .nav-links a.active { color: #818cf8; background: rgba(99,102,241,0.1); }
        .nav-auth { display: flex; align-items: center; gap: 0.75rem; }
        .btn-ghost {
            color: #94a3b8; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem;
            font-size: 0.875rem; font-weight: 500; transition: all 0.2s; border: none; background: none; cursor: pointer;
        }
        .btn-ghost:hover { color: #f1f5f9; }
        .btn-primary-nav {
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; border: none;
            padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;
            text-decoration: none; cursor: pointer; transition: all 0.3s;
        }
        .btn-primary-nav:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(99,102,241,0.4); }
        .user-menu { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 0.5rem;
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem; color: #818cf8;
        }
        .user-menu-name { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }

        /* Hero */
        .hero {
            padding: 10rem 2rem 5rem; text-align: center; position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(99,102,241,0.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 20% 60%, rgba(139,92,246,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 80% 60%, rgba(6,182,212,0.06) 0%, transparent 60%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 700px; margin: 0 auto; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
            padding: 0.4rem 1rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 600;
            color: #818cf8; margin-bottom: 1.5rem;
        }
        .hero-badge .pulse-dot {
            width: 6px; height: 6px; border-radius: 50%; background: #818cf8;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.5); } }
        .hero h1 { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.25rem; letter-spacing: -0.03em; }
        .hero h1 .gradient-text {
            background: linear-gradient(135deg, #818cf8, #6366f1, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero p { font-size: 1.1rem; color: #94a3b8; line-height: 1.7; max-width: 560px; margin: 0 auto 2rem; }
        .hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-hero {
            padding: 0.85rem 2rem; border-radius: 0.75rem; font-size: 0.95rem; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s; cursor: pointer; border: none; font-family: 'Inter', sans-serif;
        }
        .btn-hero.primary {
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
        }
        .btn-hero.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99,102,241,0.4); }
        .btn-hero.secondary {
            background: rgba(255,255,255,0.05); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-hero.secondary:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); }
        .btn-hero svg { width: 18px; height: 18px; }

        /* Stats */
        .stats-section { padding: 0 2rem 4rem; }
        .stats-grid {
            max-width: 900px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;
        }
        .stat-item {
            text-align: center; padding: 1.5rem;
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; transition: transform 0.3s;
        }
        .stat-item:hover { transform: translateY(-4px); }
        .stat-value { font-size: 2.25rem; font-weight: 800; color: #818cf8; line-height: 1; }
        .stat-label { font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; font-weight: 500; }

        /* Section */
        .section { padding: 4rem 2rem; }
        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1100px; margin: 0 auto 2rem;
        }
        .section-header h2 { font-size: 1.75rem; font-weight: 700; }
        .section-header a {
            color: #818cf8; text-decoration: none; font-size: 0.875rem; font-weight: 600;
            display: flex; align-items: center; gap: 0.25rem; transition: color 0.2s;
        }
        .section-header a:hover { color: #a5b4fc; }

        /* Cards Grid */
        .cards-grid {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;
        }
        .card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem; padding: 1.5rem; transition: all 0.3s; position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,0.2); box-shadow: 0 12px 40px rgba(0,0,0,0.2); }
        .card-top { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .card-avatar {
            width: 48px; height: 48px; border-radius: 0.75rem;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; flex-shrink: 0;
        }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #e2e8f0; margin-bottom: 0.25rem; }
        .card-subtitle { font-size: 0.8rem; color: #64748b; }
        .card-desc { font-size: 0.85rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1rem;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-footer { display: flex; align-items: center; justify-content: space-between; }
        .card-meta { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #64748b; }
        .card-meta svg { width: 14px; height: 14px; }
        .card-btn {
            padding: 0.4rem 1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s; color: #818cf8;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
        }
        .card-btn:hover { background: rgba(99,102,241,0.2); }

        /* Event Card */
        .event-card .card-date {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 48px; height: 48px; border-radius: 0.75rem;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
        }
        .event-card .card-date .day { font-size: 1.1rem; font-weight: 700; color: #818cf8; line-height: 1; }
        .event-card .card-date .month { font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .event-card .event-location { display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #64748b; }
        .event-card .event-location svg { width: 12px; height: 12px; }

        /* Footer */
        .site-footer {
            border-top: 1px solid rgba(255,255,255,0.06); padding: 3rem 2rem;
            text-align: center; color: #475569; font-size: 0.8rem;
        }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; }
        .footer-links a { color: #64748b; text-decoration: none; transition: color 0.2s; }
        .footer-links a:hover { color: #818cf8; }

        /* Empty state */
        .empty-state { text-align: center; padding: 3rem; color: #475569; grid-column: 1 / -1; }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 1rem; color: #334155; }
        .empty-state p { font-size: 0.9rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.25rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .cards-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .navbar { padding: 0 1rem; }
        }

        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .hero-content { animation: fadeInUp 0.8s ease forwards; }
        .stat-item { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        .stat-item:nth-child(1) { animation-delay: 0.1s; }
        .stat-item:nth-child(2) { animation-delay: 0.2s; }
        .stat-item:nth-child(3) { animation-delay: 0.3s; }
        .stat-item:nth-child(4) { animation-delay: 0.4s; }
        .card { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.35s; }
        .card:nth-child(5) { animation-delay: 0.4s; }
        .card:nth-child(6) { animation-delay: 0.45s; }

        /* Mobile menu */
        .mobile-menu-btn { display: none; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.5rem; }
        .mobile-menu-btn svg { width: 24px; height: 24px; }
        @media (max-width: 768px) {
            .mobile-menu-btn { display: block; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <span class="brand-dot"></span>
            KulüpNet
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?>
                <a href="duyurular.php">Duyurular</a>
                <a href="oneri_sikayet.php">Öneri/Şikayet</a>
            <?php endif; ?>
        </div>
        <div class="nav-auth">
            <?php if ($giris_yapildi): ?>
                <div class="user-menu">
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
            <?php else: ?>
                <a href="giris.php" class="btn-ghost">Giriş Yap</a>
                <a href="kayit.php" class="btn-primary-nav">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="pulse-dot"></span>
                Platformumuza Hoş Geldiniz
            </div>
            <h1>Kulüplere Katıl,<br><span class="gradient-text">Etkinlikleri Keşfet</span></h1>
            <p>Kulüplere üye olun, etkinliklere katılın ve topluluğunuzla birlikte büyüyün. Tüm kulüp ve etkinlik bilgilerine tek bir yerden ulaşın.</p>
            <div class="hero-actions">
                <?php if ($giris_yapildi): ?>
                    <a href="kulupler.php" class="btn-hero primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                        Kulüpleri Keşfet
                    </a>
                    <a href="etkinlikler.php" class="btn-hero secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Etkinlikleri Gör
                    </a>
                <?php else: ?>
                    <a href="kayit.php" class="btn-hero primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                        Hemen Kayıt Ol
                    </a>
                    <a href="giris.php" class="btn-hero secondary">
                        Giriş Yap →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $kulup_sayisi; ?></div>
                <div class="stat-label">Aktif Kulüp</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $etkinlik_sayisi; ?></div>
                <div class="stat-label">Toplam Etkinlik</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $aktif_etkinlik; ?></div>
                <div class="stat-label">Yaklaşan Etkinlik</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $uye_sayisi; ?></div>
                <div class="stat-label">Kayıtlı Üye</div>
            </div>
        </div>
    </section>

    <!-- Kulüpler -->
    <section class="section">
        <div class="section-header">
            <h2>🎯 Kulüpler</h2>
            <a href="kulupler.php">Tümünü Gör →</a>
        </div>
        <div class="cards-grid">
            <?php
            $renkler = [
                ['bg' => 'rgba(99,102,241,0.15)', 'color' => '#818cf8'],
                ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
                ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
                ['bg' => 'rgba(236,72,153,0.15)', 'color' => '#f472b6'],
                ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#60a5fa'],
                ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
            ];
            if (!empty($son_kulupler)):
                foreach ($son_kulupler as $i => $k):
                    $renk = $renkler[$i % count($renkler)];
                    $harf = mb_strtoupper(mb_substr($k['ad'], 0, 1));
            ?>
            <div class="card">
                <div class="card-top">
                    <div class="card-avatar" style="background: <?php echo $renk['bg']; ?>; color: <?php echo $renk['color']; ?>;"><?php echo $harf; ?></div>
                    <div>
                        <div class="card-title"><?php echo htmlspecialchars($k['ad']); ?></div>
                        <div class="card-subtitle"><?php echo $k['uye_sayisi']; ?> üye</div>
                    </div>
                </div>
                <div class="card-desc"><?php echo htmlspecialchars($k['aciklama'] ?? 'Açıklama bulunmuyor.'); ?></div>
                <div class="card-footer">
                    <div class="card-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                        <?php echo $k['uye_sayisi']; ?> üye
                    </div>
                    <a href="kulup_detay.php?id=<?php echo $k['id']; ?>" class="card-btn">Detay →</a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                <p>Henüz kulüp eklenmemiş.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Etkinlikler -->
    <section class="section" style="padding-top: 0;">
        <div class="section-header">
            <h2>📅 Yaklaşan Etkinlikler</h2>
            <a href="etkinlikler.php">Tümünü Gör →</a>
        </div>
        <div class="cards-grid">
            <?php
            $aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
            if (!empty($yaklasen)):
                foreach ($yaklasen as $i => $e):
                    $ts = strtotime($e['tarih']);
                    $gun = date('d', $ts);
                    $ay = $aylar[(int)date('n', $ts) - 1];
                    $saat = date('H:i', $ts);
            ?>
            <div class="card event-card">
                <div class="card-top">
                    <div class="card-date">
                        <span class="day"><?php echo $gun; ?></span>
                        <span class="month"><?php echo $ay; ?></span>
                    </div>
                    <div>
                        <div class="card-title"><?php echo htmlspecialchars($e['baslik']); ?></div>
                        <div class="card-subtitle"><?php echo htmlspecialchars($e['kulup_adi'] ?? 'Genel'); ?> · <?php echo $saat; ?></div>
                    </div>
                </div>
                <div class="card-desc"><?php echo htmlspecialchars($e['aciklama'] ?? 'Açıklama bulunmuyor.'); ?></div>
                <div class="card-footer">
                    <div class="event-location">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        <?php echo htmlspecialchars($e['konum'] ?? 'Belirtilmedi'); ?>
                    </div>
                    <a href="etkinlikler.php" class="card-btn">Detay →</a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                <p>Yaklaşan etkinlik bulunmuyor.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-links">
            <a href="index.php">Ana Sayfa</a>
            <a href="kulupler.php">Kulüpler</a>
            <a href="etkinlikler.php">Etkinlikler</a>
            <?php if ($giris_yapildi): ?>
                <a href="profil.php">Profil</a>
                <a href="duyurular.php">Duyurular</a>
                <a href="oneri_sikayet.php">Öneri/Şikayet</a>
            <?php endif; ?>
        </div>
        <p>&copy; 2026 KulüpNet. Tüm hakları saklıdır.</p>
    </footer>
</body>
</html>
