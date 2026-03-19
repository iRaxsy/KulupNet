<?php
$page_title = 'Toplantı Detayı - Admin Paneli';
$header_title = 'Toplantı Detayı';
$active_page = 'toplantilar';
require_once '../header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: toplantilar.php"); exit; }

$stmt = $db->prepare("
    SELECT t.*, k.ad as kulup_adi 
    FROM toplantilar t 
    JOIN kulupler k ON t.kulup_id = k.id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$toplanti = $stmt->fetch();

if (!$toplanti) { header("Location: toplantilar.php"); exit; }

$gecmis_mi = strtotime($toplanti['tarih']) < time();
$roller = explode(',', $toplanti['hedef_rol']);

$rol_renkleri = [
    'Herkes' => ['bg' => 'rgba(99,102,241,0.15)', 'color' => '#818cf8'],
    'Yönetici' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#f87171'],
    'Başkan' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fbbf24'],
    'Başkan Yardımcısı' => ['bg' => 'rgba(6,182,212,0.15)', 'color' => '#22d3ee'],
    'Üye' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#34d399'],
];
?>

<style>
    .back-link { color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; transition: color 0.2s; }
    .back-link:hover { color: #a5b4fc; }
    .back-link svg { width: 16px; height: 16px; }
    
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .info-card { background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.25rem; }
    .info-card .label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
    .info-card .value { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; line-height: 1.5; }
    
    .role-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; margin: 0.1rem; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .status-yaklasan { background: rgba(16,185,129,0.15); color: #34d399; }
    .status-gecmis { background: rgba(148,163,184,0.15); color: #94a3b8; }
</style>

<a href="toplantilar.php" class="back-link">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
    Toplantılara Dön
</a>

<div class="info-grid">
    <div class="info-card" style="grid-column: 1 / -1;">
        <div class="label">Toplantı Konusu</div>
        <div class="value" style="font-size: 1.25rem; color: #fff;"><?php echo htmlspecialchars($toplanti['konu']); ?></div>
    </div>
    
    <div class="info-card">
        <div class="label">İlgili Kulüp</div>
        <div class="value" style="color: #818cf8;"><?php echo htmlspecialchars($toplanti['kulup_adi']); ?></div>
    </div>
    
    <div class="info-card">
        <div class="label">Tarih ve Saat</div>
        <div class="value"><?php echo date('d.m.Y H:i', strtotime($toplanti['tarih'])); ?></div>
    </div>
    
    <div class="info-card">
        <div class="label">Konum</div>
        <div class="value"><?php echo htmlspecialchars($toplanti['konum']); ?></div>
    </div>

    <div class="info-card">
        <div class="label">Durum</div>
        <div class="value">
            <span class="status-badge <?php echo $gecmis_mi ? 'status-gecmis' : 'status-yaklasan'; ?>">
                <?php echo $gecmis_mi ? 'Geçmiş (Bitti)' : 'Aktif'; ?>
            </span>
        </div>
    </div>
    
    <div class="info-card" style="grid-column: 1 / -1;">
        <div class="label">Açıklama</div>
        <div class="value" style="font-weight: 400; color: #cbd5e1; white-space: pre-wrap;"><?php echo !empty($toplanti['aciklama']) ? nl2br(htmlspecialchars($toplanti['aciklama'])) : '<em style="color:#64748b;">Açıklama belirtilmemiş.</em>'; ?></div>
    </div>

    <div class="info-card" style="grid-column: 1 / -1;">
        <div class="label">Katılım Hedefleri (Roller)</div>
        <div class="value">
            <?php foreach($roller as $r): 
                $r = trim($r);
                $rc = $rol_renkleri[$r] ?? $rol_renkleri['Herkes'];
            ?>
                <span class="role-badge" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>;"><?php echo htmlspecialchars($r); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="info-card">
        <div class="label">Oluşturulma Tarihi</div>
        <div class="value" style="font-size: 0.85rem; color: #94a3b8;"><?php echo date('d.m.Y H:i:s', strtotime($toplanti['olusturma_tarihi'])); ?></div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
