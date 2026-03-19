<?php
$page_title = 'Dashboard - Kulüp Yönetimi';
$header_title = 'Hoş geldin, ' . (isset($_SESSION['admin_adi']) ? $_SESSION['admin_adi'] : 'Admin');
$active_page = 'dashboard';
require_once 'header.php';

// İstatistikler
$kulup_sayisi = $db->query("SELECT COUNT(*) FROM kulupler")->fetchColumn();
$etkinlik_sayisi = $db->query("SELECT COUNT(*) FROM etkinlikler")->fetchColumn();
$aktif_etkinlik = $db->query("SELECT COUNT(*) FROM etkinlikler WHERE tarih >= NOW()")->fetchColumn();
$biten_etkinlik = $db->query("SELECT COUNT(*) FROM etkinlikler WHERE tarih < NOW()")->fetchColumn();

// Yaklaşan etkinlikler (en yakın 5)
$yaklasen = $db->query("SELECT e.*, k.ad as kulup_adi FROM etkinlikler e LEFT JOIN kulupler k ON e.kulup_id = k.id WHERE e.tarih >= NOW() ORDER BY e.tarih ASC LIMIT 5")->fetchAll();

// Son eklenen kulüpler (son 5)
$son_kulupler = $db->query("SELECT * FROM kulupler ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<style>
    /* Dashboard Grid */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .dash-card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .dash-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    }

    .dash-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 1rem 1rem 0 0;
    }

    .dash-card.purple::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
    .dash-card.blue::before { background: linear-gradient(90deg, #3b82f6, #06b6d4); }
    .dash-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .dash-card.orange::before { background: linear-gradient(90deg, #f59e0b, #ef4444); }

    .dash-card .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .dash-card.purple .card-icon { background: rgba(99, 102, 241, 0.15); }
    .dash-card.blue .card-icon { background: rgba(59, 130, 246, 0.15); }
    .dash-card.green .card-icon { background: rgba(16, 185, 129, 0.15); }
    .dash-card.orange .card-icon { background: rgba(245, 158, 11, 0.15); }

    .dash-card .card-icon svg {
        width: 24px;
        height: 24px;
    }

    .dash-card.purple .card-icon svg { color: #818cf8; }
    .dash-card.blue .card-icon svg { color: #60a5fa; }
    .dash-card.green .card-icon svg { color: #34d399; }
    .dash-card.orange .card-icon svg { color: #fbbf24; }

    .dash-card .card-label {
        font-size: 0.8rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .dash-card .card-value {
        font-size: 2rem;
        font-weight: 700;
        color: #f1f5f9;
        line-height: 1;
    }

    /* Content Grid */
    .dashboard-content {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 900px) {
        .dashboard-content {
            grid-template-columns: 1fr;
        }
    }

    .panel {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .panel-header h2 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        color: #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .panel-header .panel-badge {
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-weight: 600;
    }

    .panel-header a {
        font-size: 0.8rem;
        color: #818cf8;
        text-decoration: none;
        transition: color 0.2s;
    }

    .panel-header a:hover {
        color: #a5b4fc;
    }

    /* Event List */
    .event-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .event-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        transition: background 0.2s;
    }

    .event-item:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .event-item:last-child {
        border-bottom: none;
    }

    .event-date-box {
        min-width: 50px;
        text-align: center;
        background: rgba(99, 102, 241, 0.1);
        border-radius: 0.5rem;
        padding: 0.5rem 0.4rem;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .event-date-box .day {
        font-size: 1.2rem;
        font-weight: 700;
        color: #818cf8;
        line-height: 1;
    }

    .event-date-box .month {
        font-size: 0.65rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 2px;
    }

    .event-info {
        flex: 1;
        min-width: 0;
    }

    .event-info .event-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-info .event-meta {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.2rem;
        display: flex;
        gap: 0.75rem;
    }

    .event-info .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Club list */
    .club-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .club-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        transition: background 0.2s;
    }

    .club-item:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .club-item:last-child {
        border-bottom: none;
    }

    .club-avatar {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .club-item-info {
        flex: 1;
        min-width: 0;
    }

    .club-item-info .club-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .club-item-info .club-desc {
        font-size: 0.75rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .quick-action {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        text-decoration: none;
        color: #e2e8f0;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .quick-action:hover {
        background: rgba(99, 102, 241, 0.1);
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    .quick-action .qa-icon {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .quick-action .qa-icon svg {
        width: 18px;
        height: 18px;
    }

    .qa-purple { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
    .qa-green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .qa-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
    .qa-orange { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2rem 1.5rem;
        color: #64748b;
        font-size: 0.85rem;
    }

    .empty-state svg {
        width: 40px;
        height: 40px;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    /* Counter animation */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dash-card { animation: fadeInUp 0.5s ease forwards; }
    .dash-card:nth-child(1) { animation-delay: 0s; }
    .dash-card:nth-child(2) { animation-delay: 0.1s; }
    .dash-card:nth-child(3) { animation-delay: 0.2s; }
    .dash-card:nth-child(4) { animation-delay: 0.3s; }

    .panel { animation: fadeInUp 0.5s ease forwards; animation-delay: 0.35s; opacity: 0; }
    .quick-actions { animation: fadeInUp 0.5s ease forwards; animation-delay: 0.45s; opacity: 0; }
</style>

<!-- Stat Cards -->
<div class="dashboard-stats">
    <div class="dash-card purple">
        <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
        </div>
        <div class="card-label">Toplam Kulüp</div>
        <div class="card-value"><?php echo $kulup_sayisi; ?></div>
    </div>

    <div class="dash-card blue">
        <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>
        <div class="card-label">Toplam Etkinlik</div>
        <div class="card-value"><?php echo $etkinlik_sayisi; ?></div>
    </div>

    <div class="dash-card green">
        <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div class="card-label">Aktif Etkinlik</div>
        <div class="card-value"><?php echo $aktif_etkinlik; ?></div>
    </div>

    <div class="dash-card orange">
        <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div class="card-label">Biten Etkinlik</div>
        <div class="card-value"><?php echo $biten_etkinlik; ?></div>
    </div>
</div>

<!-- Content Grid -->
<div class="dashboard-content">
    <!-- Yaklaşan Etkinlikler -->
    <div class="panel">
        <div class="panel-header">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;color:#818cf8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Yaklaşan Etkinlikler
                <?php if ($aktif_etkinlik > 0): ?>
                    <span class="panel-badge"><?php echo $aktif_etkinlik; ?> aktif</span>
                <?php endif; ?>
            </h2>
            <a href="etkinlik/etkinlikler.php">Tümünü gör →</a>
        </div>
        <?php if (!empty($yaklasen)): ?>
            <ul class="event-list">
                <?php
                $aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
                foreach ($yaklasen as $e):
                    $ts = strtotime($e['tarih']);
                    $gun = date('d', $ts);
                    $ay = $aylar[(int)date('n', $ts) - 1];
                    $saat = date('H:i', $ts);
                ?>
                <li class="event-item">
                    <div class="event-date-box">
                        <div class="day"><?php echo $gun; ?></div>
                        <div class="month"><?php echo $ay; ?></div>
                    </div>
                    <div class="event-info">
                        <div class="event-title"><?php echo htmlspecialchars($e['baslik']); ?></div>
                        <div class="event-meta">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <?php echo $saat; ?>
                            </span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <?php echo htmlspecialchars($e['konum']); ?>
                            </span>
                            <?php if (!empty($e['kulup_adi'])): ?>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                <?php echo htmlspecialchars($e['kulup_adi']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <p>Yaklaşan etkinlik bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Son Kulüpler -->
    <div class="panel">
        <div class="panel-header">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;color:#34d399;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                Son Kulüpler
            </h2>
            <a href="kulup/kulupler.php">Tümünü gör →</a>
        </div>
        <?php if (!empty($son_kulupler)): ?>
            <ul class="club-list">
                <?php
                $renkler = [
                    ['bg' => 'rgba(99, 102, 241, 0.15)', 'color' => '#818cf8'],
                    ['bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#34d399'],
                    ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#fbbf24'],
                    ['bg' => 'rgba(236, 72, 153, 0.15)', 'color' => '#f472b6'],
                    ['bg' => 'rgba(59, 130, 246, 0.15)', 'color' => '#60a5fa'],
                ];
                foreach ($son_kulupler as $i => $k):
                    $renk = $renkler[$i % count($renkler)];
                    $harf = mb_strtoupper(mb_substr($k['ad'], 0, 1));
                ?>
                <li class="club-item">
                    <div class="club-avatar" style="background: <?php echo $renk['bg']; ?>; color: <?php echo $renk['color']; ?>;">
                        <?php echo $harf; ?>
                    </div>
                    <div class="club-item-info">
                        <div class="club-name"><?php echo htmlspecialchars($k['ad']); ?></div>
                        <div class="club-desc"><?php echo htmlspecialchars(mb_strimwidth($k['aciklama'], 0, 60, "...")); ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <p>Henüz kulüp eklenmemiş.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="kulup/kulup_ekle.php" class="quick-action">
        <div class="qa-icon qa-purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
        Yeni Kulüp Ekle
    </a>
    <a href="etkinlik/etkinlik_ekle.php" class="quick-action">
        <div class="qa-icon qa-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
        Yeni Etkinlik Ekle
    </a>
    <a href="kulup/kulupler.php" class="quick-action">
        <div class="qa-icon qa-blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
        </div>
        Kulüpleri Yönet
    </a>
    <a href="etkinlik/etkinlikler.php" class="quick-action">
        <div class="qa-icon qa-orange">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
        </div>
        Etkinlikleri Yönet
    </a>
</div>

<?php require_once 'footer.php'; ?>
