<?php
$page_title = 'Duyuru Detayı - Admin Paneli';
$header_title = 'Duyuru Detayı';
$active_page = 'duyurular';
require_once '../header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: duyurular.php");
    exit;
}

// Kaydı getir
$stmt = $db->prepare("SELECT d.*, k.ad as kulup_adi 
                      FROM duyurular d 
                      LEFT JOIN kulupler k ON d.kulup_id = k.id 
                      WHERE d.id = ?");
$stmt->execute([$id]);
$kayit = $stmt->fetch();

if (!$kayit) {
    header("Location: duyurular.php");
    exit;
}
?>

<style>
    .back-link { color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; transition: color 0.2s; }
    .back-link:hover { color: #a5b4fc; }
    .back-link svg { width: 16px; height: 16px; }
    
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .info-card { background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; padding: 1.25rem; }
    .info-card .label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
    .info-card .value { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; line-height: 1.5; }
    
    .type-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700; }
    .type-site { background: rgba(99,102,241,0.15); color: #818cf8; }
    .type-kulup { background: rgba(16,185,129,0.15); color: #34d399; }
</style>

<a href="duyurular.php" class="back-link">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
    Duyurulara Dön
</a>

<div class="info-grid">
    <div class="info-card" style="grid-column: 1 / -1;">
        <div class="label">Kayıt Detayı</div>
        <div class="value" style="font-size: 1.25rem; color: #fff;">#<?php echo $kayit['id']; ?> Nolu Duyuru</div>
    </div>
    
    <div class="info-card">
        <div class="label">Duyuru Türü</div>
        <div class="value">
            <span class="type-badge <?php echo $kayit['kulup_id'] ? 'type-kulup' : 'type-site'; ?>">
                <?php echo $kayit['kulup_id'] ? 'Kulüp Duyurusu' : 'Site Geneli'; ?>
            </span>
        </div>
    </div>
    
    <div class="info-card">
        <div class="label">İlgili Kulüp</div>
        <div class="value" style="color: #818cf8;"><?php echo $kayit['kulup_adi'] ? htmlspecialchars($kayit['kulup_adi']) : '<em style="color:#64748b;">Belirtilmedi</em>'; ?></div>
    </div>
    
    <div class="info-card">
        <div class="label">Yayınlanma Tarihi</div>
        <div class="value"><?php echo date('d.m.Y H:i:s', strtotime($kayit['tarih'])); ?></div>
    </div>

    <div class="info-card" style="grid-column: 1 / -1;">
        <div class="label">Duyuru İçeriği</div>
        <div class="value" style="font-weight: 400; color: #cbd5e1; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($kayit['duyuru'])); ?></div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
