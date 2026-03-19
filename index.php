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
$yaklasen = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.tarih >= NOW() ORDER BY e.tarih ASC LIMIT 5")->fetchAll();

// Takvim Verileri (Mevcut Ay)
$ay_baslap = date('Y-m-01');
$ay_bitis = date('Y-m-t');
$takvim_etkinlik_sorgu = $db->prepare("SELECT id, baslik, tarih, konum FROM etkinlikler WHERE tarih BETWEEN ? AND ? ORDER BY tarih ASC");
$takvim_etkinlik_sorgu->execute([$ay_baslap . ' 00:00:00', $ay_bitis . ' 23:59:59']);
$takvim_etkinlikler = $takvim_etkinlik_sorgu->fetchAll();

$etkinlik_gunleri = [];
foreach($takvim_etkinlikler as $et) {
    $gun = (int)date('d', strtotime($et['tarih']));
    $etkinlik_gunleri[$gun][] = $et;
}

$bugun = (int)date('d');
$bu_ay = (int)date('m');
$bu_yil = (int)date('Y');
$toplam_gun = (int)date('t');
$aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
$ilk_gun_hafta = (int)date('w', strtotime($ay_baslap)); // 0 (Paz) - 6 (Cmt)
if($ilk_gun_hafta == 0) $ilk_gun_hafta = 7; // Pazartesi = 1 yapmak için

// JavaScript için etkinlik verilerini JSON formatına çevir
$takvim_json = json_encode($etkinlik_gunleri);
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
            background: linear-gradient(135deg, #06b6d4, #0ea5e9); color: white; border: none;
        }
        .btn-hero.secondary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(6, 182, 212, 0.4); background: linear-gradient(135deg, #22d3ee, #38bdf8); }
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

        /* Activities Hub Grid */
        .activities-container {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 380px; gap: 2rem;
        }

        /* Calendar Styles */
        .calendar-card {
            background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1.25rem; padding: 2rem; backdrop-filter: blur(10px);
        }
        .calendar-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        }
        .calendar-header h3 { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; }
        .calendar-grid {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.75rem;
        }
        .calendar-day-label {
            text-align: center; font-size: 0.7rem; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.1em; padding-bottom: 0.5rem;
        }
        .calendar-day {
            aspect-ratio: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-radius: 0.75rem; font-size: 0.9rem; font-weight: 500; color: #94a3b8;
            border: 1px solid rgba(255,255,255,0.03); background: rgba(0,0,0,0.1);
            position: relative; transition: all 0.2s; cursor: default;
        }
        .calendar-day.empty { background: transparent; border: none; }
        .calendar-day.has-event {
            background: rgba(99,102,241,0.1); border-color: rgba(99,102,241,0.3);
            color: #818cf8; font-weight: 700; cursor: pointer;
        }
        .calendar-day.today {
            background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
            box-shadow: 0 0 20px rgba(99,102,241,0.3); border: none;
        }
        .calendar-day.has-event:hover {
            transform: scale(1.1); background: rgba(99,102,241,0.2); z-index: 2;
        }
        .event-dot-group {
            position: absolute; bottom: 6px; display: flex; gap: 2px;
        }
        .event-dot { width: 4px; height: 4px; border-radius: 50%; background: #818cf8; }
        .calendar-day.today .event-dot { background: white; }

        /* Compact Upcoming Events List */
        .upcoming-panel {
            display: flex; flex-direction: column; gap: 1rem;
        }
        .compact-event-card {
            background: rgba(30,41,59,0.4); border: 1px solid rgba(255,255,255,0.04);
            border-radius: 1rem; padding: 1rem; display: flex; gap: 1rem;
            transition: all 0.2s; text-decoration: none; align-items: center;
        }
        .compact-event-card:hover { border-color: rgba(99,102,241,0.3); background: rgba(30,41,59,0.7); }
        .mini-date {
            width: 48px; height: 48px; border-radius: 0.75rem; flex-shrink: 0;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .mini-date .d { font-size: 1.1rem; font-weight: 700; color: #818cf8; line-height: 1; }
        .mini-date .m { font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; }
        .event-main-info { flex: 1; overflow: hidden; }
        .event-main-info h4 { font-size: 0.9rem; font-weight: 600; color: #f1f5f9; margin-bottom: 0.2rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-main-meta { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #64748b; }
        .event-main-meta span { display: flex; align-items: center; gap: 0.25rem; }

        @media (max-width: 992px) {
            .activities-container { grid-template-columns: 1fr; }
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
        /* Event Detail Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
            z-index: 1000; display: none; align-items: center; justify-content: center; padding: 2rem;
            animation: fadeIn 0.3s ease;
        }
        .modal-overlay.active { display: flex; }
        .event-modal {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 1.5rem;
            width: 100%; max-width: 450px; overflow: hidden; transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.active .event-modal { transform: scale(1); }
        .modal-header {
            padding: 1.5rem; background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h3 { font-size: 1.1rem; color: #818cf8; }
        .close-modal { 
            background: none; border: none; color: #64748b; cursor: pointer; padding: 0.5rem;
            transition: color 0.2s;
        }
        .close-modal:hover { color: #f1f5f9; }
        .modal-body { padding: 1.5rem; max-height: 400px; overflow-y: auto; }
        .modal-event-item {
            padding: 1rem; border-radius: 1rem; background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1rem;
        }
        .modal-event-item:last-child { margin-bottom: 0; }
        .modal-event-meta { font-size: 0.8rem; color: #94a3b8; display: flex; flex-direction: column; gap: 0.3rem; }
        
        /* Modal Pagination */
        .modal-footer-pagination {
            padding: 1rem 1.5rem; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .pagination-btn {
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
            color: #818cf8; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.75rem;
            font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.3rem;
        }
        .pagination-btn:hover:not(:disabled) { background: rgba(99,102,241,0.2); transform: translateY(-1px); }
        .pagination-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .page-info { font-size: 0.75rem; color: #64748b; font-weight: 600; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
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
                <a href="toplantilar.php">Toplantılar</a>
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

    <!-- Etkinlik Merkezi -->
    <section class="section">
        <div class="section-header">
            <h2>📅 Etkinlik Merkezi</h2>
            <p style="color: #64748b; font-size: 0.875rem;">Aylık program ve yaklaşan duyurular</p>
        </div>
        
        <div class="activities-container">
            <!-- Takvim Paneli -->
            <div class="calendar-card">
                <div class="calendar-header">
                    <h3><?php echo $aylar[$bu_ay-1] . ' ' . $bu_yil; ?></h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <span style="display: flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; color: #64748b;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #6366f1;"></span> Bugün
                        </span>
                        <span style="display: flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; color: #64748b;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(99,102,241,0.3); border: 1px solid rgba(99,102,241,0.5);"></span> Etkinlik
                        </span>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <?php 
                    $gun_isimleri = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                    foreach($gun_isimleri as $gi) echo "<div class='calendar-day-label'>$gi</div>";
                    
                    // İlk gün boşlukları
                    for($i = 1; $i < $ilk_gun_hafta; $i++) echo "<div class='calendar-day empty'></div>";
                    
                    // Günler
                    for($d = 1; $d <= $toplam_gun; $d++):
                        $is_today = ($d == $bugun);
                        $has_events = isset($etkinlik_gunleri[$d]);
                        $class = "calendar-day";
                        if($is_today) $class .= " today";
                        if($has_events) $class .= " has-event";
                        
                        $tooltip = "";
                        if($has_events) {
                            $titles = array_map(function($e) { return htmlspecialchars($e['baslik']); }, $etkinlik_gunleri[$d]);
                            $tooltip = 'title="' . implode(' | ', $titles) . '"';
                        }
                    ?>
                        <div class="<?php echo $class; ?>" <?php echo $tooltip; ?>>
                            <?php echo $d; ?>
                            <?php if($has_events): ?>
                                <div class="event-dot-group">
                                    <?php foreach($etkinlik_gunleri[$d] as $index => $et) if($index < 3) echo '<span class="event-dot"></span>'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Yaklaşan Etkinlikler Listesi -->
            <div class="upcoming-panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">Yaklaşanlar</h3>
                    <a href="etkinlikler.php" style="font-size: 0.75rem; color: #818cf8; text-decoration: none; font-weight: 600;">Tümü →</a>
                </div>
                
                <?php if (!empty($yaklasen)): foreach ($yaklasen as $e): 
                    $ts = strtotime($e['tarih']);
                    $gun = date('d', $ts);
                    $ay = $aylar[(int)date('n', $ts) - 1];
                ?>
                <a href="etkinlikler.php" class="compact-event-card">
                    <div class="mini-date">
                        <span class="d"><?php echo $gun; ?></span>
                        <span class="m"><?php echo $ay; ?></span>
                    </div>
                    <div class="event-main-info">
                        <h4><?php echo htmlspecialchars($e['baslik']); ?></h4>
                        <div class="event-main-meta">
                            <span>
                                <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <?php echo date('H:i', $ts); ?>
                            </span>
                            <span>
                                <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <?php echo htmlspecialchars($e['konum'] ?? 'Kayıtlı Değil'); ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; else: ?>
                    <div style="padding: 2rem; text-align: center; color: #475569; background: rgba(0,0,0,0.15); border-radius: 1rem; border: 1px dashed rgba(255,255,255,0.05);">
                        <p style="font-size: 0.85rem;">Yakın zamanda etkinlik bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
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
                <a href="toplantilar.php">Toplantılar</a>
                <a href="duyurular.php">Duyurular</a>
                <a href="oneri_sikayet.php">Öneri/Şikayet</a>
            <?php endif; ?>
        </div>
        <p>&copy; 2026 KulüpNet. Tüm hakları saklıdır.</p>
    </footer>
    <!-- Event Detail Modal -->
    <div class="modal-overlay" id="eventModalOverlay">
        <div class="event-modal">
            <div class="modal-header">
                <h3 id="modalDateTitle">Etkinlikler</h3>
                <button class="close-modal" id="closeModal">
                    <svg style="width:24px; height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dinamik içerik -->
            </div>
            <div class="modal-footer-pagination" id="modalPagination" style="display: none;">
                <button class="pagination-btn" id="prevPage">
                    <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Geri
                </button>
                <div class="page-info" id="pageInfo">1 / 1</div>
                <button class="pagination-btn" id="nextPage">
                    İleri
                    <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const eventData = <?php echo $takvim_json; ?>;
        const modalOverlay = document.getElementById('eventModalOverlay');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalDateTitle');
        const modalPagination = document.getElementById('modalPagination');
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const closeBtn = document.getElementById('closeModal');
        const aylar = <?php echo json_encode($aylar); ?>;

        let currentDayEvents = [];
        let currentPage = 1;
        const eventsPerPage = 3;

        const renderEvents = () => {
            const start = (currentPage - 1) * eventsPerPage;
            const end = start + eventsPerPage;
            const paginatedEvents = currentDayEvents.slice(start, end);
            const totalPages = Math.ceil(currentDayEvents.length / eventsPerPage);

            modalBody.innerHTML = paginatedEvents.map(e => `
                <div class="modal-event-item">
                    <div class="modal-event-title">${e.baslik}</div>
                    <div class="modal-event-meta">
                        <span>🕒 Saat: ${e.tarih.split(' ')[1].substring(0,5)}</span>
                        <span>📍 Konum: ${e.konum || 'Belirtilmedi'}</span>
                    </div>
                </div>
            `).join('');

            if (currentDayEvents.length > eventsPerPage) {
                modalPagination.style.display = 'flex';
                pageInfo.innerText = `${currentPage} / ${totalPages}`;
                prevBtn.disabled = (currentPage === 1);
                nextBtn.disabled = (currentPage === totalPages);
            } else {
                modalPagination.style.display = 'none';
            }
        };

        document.querySelectorAll('.calendar-day.has-event').forEach(day => {
            day.addEventListener('click', () => {
                const gun = parseInt(day.innerText);
                currentDayEvents = eventData[gun] || [];
                
                if (currentDayEvents.length === 0) return;

                currentPage = 1;
                modalTitle.innerText = `${gun} ${aylar[<?php echo $bu_ay-1; ?>]} ${<?php echo $bu_yil; ?>} Etkinlikleri`;
                renderEvents();
                modalOverlay.classList.add('active');
            });
        });

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
            }
        });

        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(currentDayEvents.length / eventsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderEvents();
            }
        });

        const closeMenu = () => modalOverlay.classList.remove('active');
        closeBtn.addEventListener('click', closeMenu);
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeMenu();
        });
    </script>
</body>
</html>
