<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

$kulup_id = (int)($_GET['id'] ?? 0);
if (!$kulup_id) { header("Location: kulupler.php"); exit; }

// Kulüp bilgisi
$stmt = $db->prepare("SELECT * FROM kulupler WHERE id = ?");
$stmt->execute([$kulup_id]);
$kulup = $stmt->fetch();
if (!$kulup) { header("Location: kulupler.php"); exit; }

$page_title = htmlspecialchars($kulup['ad']) . ' - İstatistikler';
$header_title = 'Kulüp İstatistikleri';
$active_page = 'kulupler';
require_once '../header.php';

// 1. Temel İstatistikler
$toplam_uye = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = ?");
$toplam_uye->execute([$kulup_id]);
$toplam_uye = $toplam_uye->fetchColumn();

$toplam_etkinlik = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ?");
$toplam_etkinlik->execute([$kulup_id]);
$toplam_etkinlik = $toplam_etkinlik->fetchColumn();

$ortalama_katilim = $db->prepare("
    SELECT AVG(katilim_sayisi) 
    FROM (
        SELECT COUNT(ek.id) as katilim_sayisi 
        FROM etkinlikler e 
        LEFT JOIN etkinlik_katilimlari ek ON e.id = ek.etkinlik_id 
        WHERE e.kulup_id = ? 
        GROUP BY e.id
    ) as subquery
");
$ortalama_katilim->execute([$kulup_id]);
$ortalama_katilim = round($ortalama_katilim->fetchColumn() ?: 0, 1);

// 2. Aylık Etkinlik Sayıları (Son 12 Ay)
$aylik_etkinlikler = $db->prepare("
    SELECT DATE_FORMAT(tarih, '%Y-%m') as ay, COUNT(*) as sayi 
    FROM etkinlikler 
    WHERE kulup_id = ? AND tarih >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY ay 
    ORDER BY ay ASC
");
$aylik_etkinlikler->execute([$kulup_id]);
$aylik_data = $aylik_etkinlikler->fetchAll();

// 3. Son 1 Ay Toplam Büyüme (Yeni İsteğe Göre)
$son_bir_ay_sorgu = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = ? AND katilma_tarihi >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$son_bir_ay_sorgu->execute([$kulup_id]);
$son_1_ay_sayisi = $son_bir_ay_sorgu->fetchColumn();

// 4. Cinsiyet Dağılımı
$cinsiyet_dagilimi = $db->prepare("
    SELECT k.cinsiyet, COUNT(*) as sayi 
    FROM kulup_uyelikleri ku 
    JOIN kullanicilar k ON ku.kullanici_id = k.id 
    WHERE ku.kulup_id = ? 
    GROUP BY k.cinsiyet
");
$cinsiyet_dagilimi->execute([$kulup_id]);
$cinsiyet_data = $cinsiyet_dagilimi->fetchAll();

// 4.1. Rol Dağılımı (Premium İçin)
$rol_dagilimi = $db->prepare("
    SELECT kulup_rol, COUNT(*) as sayi 
    FROM kulup_uyelikleri 
    WHERE kulup_id = ? 
    GROUP BY kulup_rol
");
$rol_dagilimi->execute([$kulup_id]);
$rol_data = $rol_dagilimi->fetchAll();

// 4.2. Yönetici Sayısı
$yonetici_sayisi = 0;
foreach($rol_data as $r) {
    if(in_array($r['kulup_rol'], ['Başkan', 'Yönetici', 'Admin'])) $yonetici_sayisi += $r['sayi'];
}

// 5. Yıllık Etkinlik Sayıları (Sayfalamalı)
$y_per_page = 5;
$y_sayfa = isset($_GET['y_sayfa']) ? max(1, (int)$_GET['y_sayfa']) : 1;
$y_offset = ($y_sayfa - 1) * $y_per_page;

$y_count_stmt = $db->prepare("SELECT COUNT(DISTINCT YEAR(tarih)) FROM etkinlikler WHERE kulup_id = ?");
$y_count_stmt->execute([$kulup_id]);
$y_total = $y_count_stmt->fetchColumn();
$y_total_pages = ceil($y_total / $y_per_page);

$yillik_etkinlikler = $db->prepare("
    SELECT YEAR(tarih) as yil, COUNT(*) as sayi 
    FROM etkinlikler 
    WHERE kulup_id = ? 
    GROUP BY yil 
    ORDER BY yil DESC 
    LIMIT $y_per_page OFFSET $y_offset
");
$yillik_etkinlikler->execute([$kulup_id]);
$yillik_data = $yillik_etkinlikler->fetchAll();

// 6. Yaklaşan Etkinlikler (Sayfalamalı)
$e_per_page = 5;
$e_sayfa = isset($_GET['e_sayfa']) ? max(1, (int)$_GET['e_sayfa']) : 1;
$e_offset = ($e_sayfa - 1) * $e_per_page;

$e_count_stmt = $db->prepare("SELECT COUNT(*) FROM etkinlikler WHERE kulup_id = ? AND tarih >= NOW()");
$e_count_stmt->execute([$kulup_id]);
$e_total = $e_count_stmt->fetchColumn();
$e_total_pages = ceil($e_total / $e_per_page);

$yaklasan_etkinlikler = $db->prepare("
    SELECT e.*, 
    (SELECT COUNT(*) FROM etkinlik_katilimlari WHERE etkinlik_id = e.id) as katilimci_sayisi 
    FROM etkinlikler e 
    WHERE e.kulup_id = ? AND e.tarih >= NOW() 
    ORDER BY e.tarih ASC 
    LIMIT $e_per_page OFFSET $e_offset
");
$yaklasan_etkinlikler->execute([$kulup_id]);
$yaklasanlar = $yaklasan_etkinlikler->fetchAll();

// 7. Son Katılan Üyeler (Sayfalamalı)
$u_per_page = 5;
$u_sayfa = isset($_GET['u_sayfa']) ? max(1, (int)$_GET['u_sayfa']) : 1;
$u_offset = ($u_sayfa - 1) * $u_per_page;

$u_count_stmt = $db->prepare("SELECT COUNT(*) FROM kulup_uyelikleri WHERE kulup_id = ?");
$u_count_stmt->execute([$kulup_id]);
$u_total = $u_count_stmt->fetchColumn();
$u_total_pages = ceil($u_total / $u_per_page);

$son_uyeler = $db->prepare("
    SELECT k.ad, k.soyad, k.kullanici_adi, ku.katilma_tarihi, ku.kulup_rol 
    FROM kulup_uyelikleri ku 
    JOIN kullanicilar k ON ku.kullanici_id = k.id 
    WHERE ku.kulup_id = ? 
    ORDER BY ku.katilma_tarihi DESC 
    LIMIT $u_per_page OFFSET $u_offset
");
$son_uyeler->execute([$kulup_id]);
$son_uyeler = $son_uyeler->fetchAll();

// 8. En Çok Katılınan 3 Etkinlik
$en_cok_katilim = $db->prepare("
    SELECT e.id, e.baslik, e.tarih, e.kisi_siniri,
    (SELECT COUNT(*) FROM etkinlik_katilimlari WHERE etkinlik_id = e.id) as katilimci_sayisi
    FROM etkinlikler e
    WHERE e.kulup_id = ?
    ORDER BY katilimci_sayisi DESC, e.tarih DESC
    LIMIT 3
");
$en_cok_katilim->execute([$kulup_id]);
$en_cok_uclu = $en_cok_katilim->fetchAll();

// Helper function to build pagination URL
function build_url($base, $params, $new_key, $new_val) {
    $params[$new_key] = $new_val;
    return $base . '?' . http_build_query($params);
}
$current_params = $_GET;
$base_url = 'kulup_istatistik.php';

// Chart.js için data hazırlama
$labels_aylik = []; $values_aylik = [];
foreach($aylik_data as $row) { $labels_aylik[] = date('M Y', strtotime($row['ay'] . '-01')); $values_aylik[] = $row['sayi']; }

$labels_cinsiyet = []; $values_cinsiyet = [];
foreach($cinsiyet_data as $row) { $labels_cinsiyet[] = $row['cinsiyet']; $values_cinsiyet[] = $row['sayi']; }

$labels_rol = []; $values_rol = [];
foreach($rol_data as $row) { $labels_rol[] = $row['kulup_rol']; $values_rol[] = $row['sayi']; }

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .stats-container { animation: fadeInUp 0.5s ease forwards; }
    .header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
    .btn-back-modern { 
        display: inline-flex; align-items: center; gap: 0.5rem; 
        color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 600; 
        transition: color 0.2s; 
    }
    .btn-back-modern:hover { color: #f1f5f9; }
    
    .stats-grid-admin { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
    .stat-card-admin { 
        background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.06); 
        padding: 1.5rem; border-radius: 1rem; position: relative; overflow: hidden; 
    }
    .stat-card-admin::before { content: ''; position: absolute; top:0; left:0; width:4px; height:100%; background: #6366f1; }
    .stat-label-admin { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
    .stat-value-admin { font-size: 1.5rem; font-weight: 800; color: #f8fafc; }

    .charts-row-admin { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    .chart-card-admin { background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.06); padding: 1.5rem; border-radius: 1rem; }
    .chart-title-admin { font-size: 0.9rem; font-weight: 700; margin-bottom: 1.5rem; color: #e2e8f0; display: flex; align-items: center; gap: 0.5rem; }
    
    .tables-grid-admin { display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 1.5rem; }
    .card-panel-admin { background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 1rem; overflow: hidden; }
    .card-panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06); font-size: 0.85rem; font-weight: 700; color: #f1f5f9; }
    
    .table-admin { width: 100%; border-collapse: collapse; }
    .table-admin th { text-align: left; padding: 1rem 1.5rem; font-size: 0.7rem; color: #64748b; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .table-admin td { padding: 0.85rem 1.5rem; font-size: 0.8rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.04); }
    
    .member-item-admin { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .member-avatar-admin { width: 30px; height: 30px; border-radius: 0.5rem; background: rgba(99,102,241,0.1); color: #818cf8; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
    .member-name-admin { font-weight: 600; color: #f1f5f9; font-size: 0.8rem; }
    .member-meta-admin { font-size: 0.65rem; color: #64748b; }

    @media (max-width: 1200px) { .tables-grid-admin { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 1024px) { .charts-row-admin { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .tables-grid-admin { grid-template-columns: 1fr; } }

    .card-pagination { 
        display: flex; justify-content: space-between; align-items: center; 
        padding: 0.75rem 1.25rem; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.04);
    }
    .card-page-btn {
        color: #94a3b8; text-decoration: none; font-size: 0.7rem; font-weight: 700;
        padding: 0.25rem 0.5rem; border-radius: 0.4rem; background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06); transition: all 0.2s;
    }
    .card-page-btn:hover:not(.disabled) { background: rgba(99,102,241,0.1); color: #818cf8; border-color: rgba(99,102,241,0.2); }
    .card-page-btn.disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }
    /* Premium Profile Styles */
    .profile-premium-stack { display: flex; flex-direction: column; align-items: center; }
    .badge-grid-premium { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; width: 100%; margin-top: 1.5rem; }
    .badge-stat { 
        display: flex; align-items: center; gap: 0.75rem; 
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
        padding: 0.85rem; border-radius: 0.75rem; transition: all 0.3s;
    }
    .badge-stat:hover { background: rgba(255,255,255,0.05); border-color: rgba(99,102,241,0.3); transform: translateY(-3px); }
    .badge-icon { 
        width: 32px; height: 32px; border-radius: 0.5rem; 
        display: flex; align-items: center; justify-content: center;
        background: rgba(99, 102, 241, 0.1); color: #818cf8;
    }
    .card-page-info { font-size: 0.65rem; color: #475569; font-weight: 600; }

    /* Fresh Profile Redesign Styles */
    .profile-fresh-container { display: flex; gap: 2rem; align-items: center; padding: 0.5rem 0; }
    .profile-chart-side { flex: 0 0 160px; position: relative; }
    .profile-info-side { flex: 1; display: flex; flex-direction: column; gap: 1rem; }
    
    .insight-row { display: flex; flex-direction: column; gap: 0.4rem; }
    .insight-label-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; }
    .insight-label { color: #94a3b8; font-weight: 600; }
    .insight-value { color: #f1f5f9; font-weight: 700; }
    
    .progress-track { width: 100%; height: 6px; background: rgba(255,255,255,0.03); border-radius: 10px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
    
    .fresh-footer-stats { 
        display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; 
        margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,0.06); 
    }
    .footer-mini-card { 
        padding: 0.75rem; background: rgba(0,0,0,0.15); border-radius: 0.75rem; 
        display: flex; align-items: center; gap: 0.75rem; border: 1px solid rgba(255,255,255,0.03);
    }
    .footer-mini-icon { width: 32px; height: 32px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
    .footer-mini-label { font-size: 0.6rem; color: #64748b; font-weight: 700; text-transform: uppercase; }
    .footer-mini-val { font-size: 0.85rem; color: #f1f5f9; font-weight: 800; display: block; }

    @media (max-width: 500px) { .profile-fresh-container { flex-direction: column; } .fresh-footer-stats { grid-template-columns: 1fr; } }
</style>

<div class="stats-container">
    <div class="header-row">
        <div>
            <a href="kulupler.php" class="btn-back-modern">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Listeye Dön
            </a>
            <h2 style="margin-top:0.5rem; font-weight:800;"><?php echo htmlspecialchars($kulup['ad']); ?></h2>
        </div>
        <button onclick="indirIstatistikPDF()" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; border-radius: 0.625rem; font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); outline: none; transition: all 0.3s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            İstatistikleri İndir (PDF)
        </button>
    </div>

    <div class="stats-grid-admin">
        <div class="stat-card-admin">
            <div class="stat-label-admin">Üye Sayısı</div>
            <div class="stat-value-admin"><?php echo $toplam_uye; ?></div>
        </div>
        <div class="stat-card-admin" style="border-left-color: #10b981;">
            <div class="stat-label-admin">Etkinlik Sayısı</div>
            <div class="stat-value-admin"><?php echo $toplam_etkinlik; ?></div>
        </div>
        <div class="stat-card-admin" style="border-left-color: #f59e0b;">
            <div class="stat-label-admin">Ort. Katılım</div>
            <div class="stat-value-admin"><?php echo $ortalama_katilim; ?></div>
        </div>
        <div class="stat-card-admin" style="border-left-color: #06b6d4;">
            <div class="stat-label-admin">Yaklaşan</div>
            <div class="stat-value-admin"><?php echo count($yaklasanlar); ?></div>
        </div>
    </div>

    <div class="charts-row-admin">
        <div class="chart-card-admin">
            <div class="chart-title-admin">Aylık Etkinlik Performansı</div>
            <canvas id="etkinlikChartAdmin" height="200"></canvas>
        </div>
        <div class="chart-card-admin">
            <div class="chart-title-admin" style="justify-content: space-between;">
                <span>Üye Profil Analizi</span>
                <span style="font-size: 0.65rem; padding: 0.2rem 0.6rem; background: rgba(99,102,241,0.1); color: #818cf8; border-radius: 4px; border: 1px solid rgba(99,102,241,0.2);">Canlı Veri</span>
            </div>
            
            <div class="profile-fresh-container">
                <div class="profile-chart-side">
                    <canvas id="cinsiyetChartAdmin"></canvas>
                </div>
                
                <div class="profile-info-side">
                    <?php 
                    $colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#06b6d4'];
                    foreach($cinsiyet_data as $index => $row): 
                        $yuzde = $toplam_uye > 0 ? round(($row['sayi'] / $toplam_uye) * 100, 1) : 0;
                        $color = $colors[$index % count($colors)];
                    ?>
                    <div class="insight-row">
                        <div class="insight-label-row">
                            <span class="insight-label"><?php echo htmlspecialchars($row['cinsiyet']); ?> Üyeler</span>
                            <span class="insight-value"><?php echo $yuzde; ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo $yuzde; ?>%; background: <?php echo $color; ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="font-size: 0.65rem; color: #475569; font-weight: 600; line-height: 1.4; margin-top: 5px;">
                        Demografik verilere göre kulübün <strong>%<?php echo $toplam_uye > 0 ? round(($yonetici_sayisi / $toplam_uye) * 100, 1) : 0; ?></strong> oranı yönetici statüsündedir.
                    </div>
                </div>
            </div>

            <!-- Top 3 Etkinlik (Kompakt Liste) -->
            <div style="margin-top: 1.5rem; padding: 0 0.5rem; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1.25rem;">
                <div style="font-size: 0.7rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 12px; height: 2px; background: #6366f1;"></span> En Başarılı 3 Etkinlik
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php if(empty($en_cok_uclu)): ?>
                        <div style="font-size: 0.65rem; color: #475569;">Veri bulunmuyor.</div>
                    <?php else: foreach($en_cok_uclu as $top_e): 
                        $doluluk = $top_e['kisi_siniri'] > 0 ? min(100, round(($top_e['katilimci_sayisi'] / $top_e['kisi_siniri']) * 100)) : 100;
                    ?>
                    <div style="background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.02); padding: 0.5rem 0.75rem; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                            <span style="font-size: 0.7rem; font-weight: 600; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%;"><?php echo htmlspecialchars($top_e['baslik']); ?></span>
                            <span style="font-size: 0.65rem; font-weight: 700; color: #818cf8;"><?php echo $top_e['katilimci_sayisi']; ?> <span style="font-size: 0.55rem; opacity: 0.6;">Kişi</span></span>
                        </div>
                        <div class="progress-track" style="height: 3px;">
                            <div class="progress-fill" style="width: <?php echo $doluluk; ?>%; background: #6366f1;"></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="fresh-footer-stats">
                <div class="footer-mini-card">
                    <div class="footer-mini-icon" style="background: rgba(9, 181, 212, 0.1); color: #22d3ee;">👥</div>
                    <div>
                        <span class="footer-mini-label">Toplam Üye</span>
                        <span class="footer-mini-val"><?php echo $toplam_uye; ?></span>
                    </div>
                </div>
                <div class="footer-mini-card">
                    <div class="footer-mini-icon" style="background: rgba(16,185,129,0.1); color: #34d399;">⚡</div>
                    <div>
                        <span class="footer-mini-label">Son 1 Ay</span>
                        <span class="footer-mini-val">+<?php echo $son_1_ay_sayisi; ?> Üye</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                <?php foreach($rol_data as $r): ?>
                    <span style="font-size: 0.6rem; font-weight: 700; color: #64748b; background: rgba(0,0,0,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.03);">
                        <?php echo $r['kulup_rol']; ?> (<?php echo $r['sayi']; ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="tables-grid-admin">
        <div class="card-panel-admin" style="display:flex; flex-direction:column;">
            <div class="card-panel-header">Yıllık Özet</div>
            <div style="flex:1;">
                <table class="table-admin">
                    <thead><tr><th>Yıl</th><th>Etkinlik</th></tr></thead>
                    <tbody>
                        <?php if(empty($yillik_data)): ?>
                            <tr><td colspan="2" style="text-align:center;">Veri yok</td></tr>
                        <?php else: foreach($yillik_data as $y): ?>
                        <tr>
                            <td style="font-weight:700; color:#818cf8;"><?php echo $y['yil']; ?></td>
                            <td><?php echo $y['sayi']; ?> Kayıt</td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-pagination">
                <a href="<?php echo build_url($base_url, $current_params, 'y_sayfa', $y_sayfa - 1); ?>" class="card-page-btn <?php echo $y_sayfa <= 1 ? 'disabled' : ''; ?>">←</a>
                <span class="card-page-info"><?php echo $y_sayfa; ?> / <?php echo max(1, $y_total_pages); ?></span>
                <a href="<?php echo build_url($base_url, $current_params, 'y_sayfa', $y_sayfa + 1); ?>" class="card-page-btn <?php echo $y_sayfa >= $y_total_pages ? 'disabled' : ''; ?>">→</a>
            </div>
        </div>

        <div class="card-panel-admin" style="display:flex; flex-direction:column;">
            <div class="card-panel-header">Yaklaşan Etkinlikler</div>
            <div style="flex:1;">
                <table class="table-admin">
                    <thead><tr><th>Başlık</th><th>Katılım</th></tr></thead>
                    <tbody>
                        <?php if(empty($yaklasanlar)): ?>
                            <tr><td colspan="2" style="text-align:center;">Plan yok</td></tr>
                        <?php else: foreach($yaklasanlar as $e): ?>
                            <tr>
                                <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($e['baslik']); ?></td>
                                <td><span style="color:#818cf8; font-weight:700;"><?php echo $e['katilimci_sayisi']; ?></span><?php if($e['kisi_siniri']) echo "/".$e['kisi_siniri']; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-pagination">
                <a href="<?php echo build_url($base_url, $current_params, 'e_sayfa', $e_sayfa - 1); ?>" class="card-page-btn <?php echo $e_sayfa <= 1 ? 'disabled' : ''; ?>">←</a>
                <span class="card-page-info"><?php echo $e_sayfa; ?> / <?php echo max(1, $e_total_pages); ?></span>
                <a href="<?php echo build_url($base_url, $current_params, 'e_sayfa', $e_sayfa + 1); ?>" class="card-page-btn <?php echo $e_sayfa >= $e_total_pages ? 'disabled' : ''; ?>">→</a>
            </div>
        </div>

        <div class="card-panel-admin" style="display:flex; flex-direction:column;">
            <div class="card-panel-header">Son Katılanlar</div>
            <div style="flex:1;">
                <?php if(empty($son_uyeler)): ?>
                    <div style="padding:1.5rem; text-align:center; font-size:0.75rem; color:#475569;">Üye yok</div>
                <?php else: foreach($son_uyeler as $s): ?>
                    <div class="member-item-admin">
                        <div class="member-avatar-admin"><?php echo mb_strtoupper(mb_substr($s['ad'], 0, 1)); ?></div>
                        <div>
                            <div class="member-name-admin"><?php echo htmlspecialchars($s['ad'].' '.$s['soyad']); ?></div>
                            <div class="member-meta-admin"><?php echo date('d.m.Y', strtotime($s['katilma_tarihi'])); ?> · <?php echo $s['kulup_rol']; ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="card-pagination">
                <a href="<?php echo build_url($base_url, $current_params, 'u_sayfa', $u_sayfa - 1); ?>" class="card-page-btn <?php echo $u_sayfa <= 1 ? 'disabled' : ''; ?>">←</a>
                <span class="card-page-info"><?php echo $u_sayfa; ?> / <?php echo max(1, $u_total_pages); ?></span>
                <a href="<?php echo build_url($base_url, $current_params, 'u_sayfa', $u_sayfa + 1); ?>" class="card-page-btn <?php echo $u_sayfa >= $u_total_pages ? 'disabled' : ''; ?>">→</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Etkinlik Chart
    const ctxEtkinlik = document.getElementById('etkinlikChartAdmin').getContext('2d');
    new Chart(ctxEtkinlik, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_aylik); ?>,
            datasets: [{
                label: 'Etkinlik',
                data: <?php echo json_encode($values_aylik); ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.4)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Cinsiyet Chart
    const ctxCinsiyet = document.getElementById('cinsiyetChartAdmin').getContext('2d');
    new Chart(ctxCinsiyet, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels_cinsiyet); ?>,
            datasets: [{
                data: <?php echo json_encode($values_cinsiyet); ?>,
                backgroundColor: ['#6366f1', '#ec4899', '#10b981'],
                borderWidth: 0,
                hoverOffset: 12
            }]
        },
        options: {
            responsive: true,
            cutout: '82%',
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<!-- Gizli PDF İçeriği -->
<div id="pdfContainer" style="position: absolute; left: -9999px; top: 0; width: 800px; background: white; z-index: -1;">
    <div id="pdfIstatistik" style="padding: 40px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333;">
        <div style="text-align: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 25px;">
            <h1 style="color: #111827; margin-bottom: 5px; font-size: 24px; font-weight: bold;"><?php echo htmlspecialchars($kulup['ad']); ?></h1>
            <h2 style="color: #4b5563; font-size: 16px; font-weight: normal; margin: 0;">İstatistik Raporu</h2>
        </div>

        <!-- Temel İstatistikler -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Üye Sayısı</th>
                    <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Etkinlik Sayısı</th>
                    <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Ort. Katılım</th>
                    <th style="padding: 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; color: #374151;">Son 1 Ay Yeni Üye</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db; font-weight: 700; font-size: 18px;"><?php echo $toplam_uye; ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db; font-weight: 700; font-size: 18px;"><?php echo $toplam_etkinlik; ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db; font-weight: 700; font-size: 18px;"><?php echo $ortalama_katilim; ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db; font-weight: 700; font-size: 18px;">+<?php echo $son_1_ay_sayisi; ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Cinsiyet Dağılımı -->
        <h3 style="font-size: 16px; color: #374151; margin-bottom: 10px;">Cinsiyet Dağılımı</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Cinsiyet</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Sayı</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Yüzde</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cinsiyet_data as $row): 
                    $yuzde = $toplam_uye > 0 ? round(($row['sayi'] / $toplam_uye) * 100, 1) : 0;
                ?>
                <tr>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo htmlspecialchars($row['cinsiyet']); ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo $row['sayi']; ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;">%<?php echo $yuzde; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Rol Dağılımı -->
        <h3 style="font-size: 16px; color: #374151; margin-bottom: 10px;">Rol Dağılımı</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Rol</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Sayı</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rol_data as $r): ?>
                <tr>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo htmlspecialchars($r['kulup_rol']); ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo $r['sayi']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- En Başarılı 3 Etkinlik -->
        <?php if(!empty($en_cok_uclu)): ?>
        <h3 style="font-size: 16px; color: #374151; margin-bottom: 10px;">En Başarılı 3 Etkinlik</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Etkinlik</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Tarih</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Katılım</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($en_cok_uclu as $top_e): ?>
                <tr>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo htmlspecialchars($top_e['baslik']); ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo date('d.m.Y', strtotime($top_e['tarih'])); ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo $top_e['katilimci_sayisi']; ?><?php if($top_e['kisi_siniri']) echo '/'.$top_e['kisi_siniri']; ?> kişi</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Yıllık Özet -->
        <?php 
        $yillik_tum = $db->prepare("SELECT YEAR(tarih) as yil, COUNT(*) as sayi FROM etkinlikler WHERE kulup_id = ? GROUP BY yil ORDER BY yil DESC");
        $yillik_tum->execute([$kulup_id]);
        $yillik_tum_data = $yillik_tum->fetchAll();
        if(!empty($yillik_tum_data)): ?>
        <h3 style="font-size: 16px; color: #374151; margin-bottom: 10px;">Yıllık Etkinlik Özeti</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Yıl</th>
                    <th style="padding: 10px 12px; border: 1px solid #d1d5db; text-align: left; font-weight: 600;">Etkinlik Sayısı</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($yillik_tum_data as $y): ?>
                <tr>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db; font-weight: 700;"><?php echo $y['yil']; ?></td>
                    <td style="padding: 10px 12px; border: 1px solid #d1d5db;"><?php echo $y['sayi']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: right; font-size: 12px; color: #6b7280;">
            Oluşturulma Tarihi: <?php echo date('d.m.Y H:i'); ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function indirIstatistikPDF() {
        const element = document.getElementById('pdfIstatistik');
        const opt = {
            margin:       [15, 15, 15, 15],
            filename:     '<?php echo preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($kulup['ad'])); ?>_istatistikler.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>

<?php require_once '../footer.php'; ?>
