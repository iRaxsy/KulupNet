<?php
$page_title = 'Üye Detay - Admin Paneli';
$header_title = 'Üye Detay';
$active_page = 'uyeler';
require_once '../header.php';

$uye_id = (int)($_GET['id'] ?? 0);
if (!$uye_id) { header("Location: uyeler.php"); exit; }

// Üye bilgisi
$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$stmt->execute([$uye_id]);
$uye = $stmt->fetch();
if (!$uye) { header("Location: uyeler.php"); exit; }

// Kulüp rol güncelle
if (isset($_POST['kulup_rol_guncelle'])) {
    $uyelik_id = (int)$_POST['uyelik_id'];
    $yeni_rol = $_POST['kulup_rol'];
    $gecerli = ['Yönetici', 'Başkan', 'Başkan Yardımcısı', 'Üye'];
    if (in_array($yeni_rol, $gecerli)) {
        $db->prepare("UPDATE kulup_uyelikleri SET kulup_rol = ? WHERE id = ? AND kullanici_id = ?")
           ->execute([$yeni_rol, $uyelik_id, $uye_id]);
        header("Location: uye_detay.php?id=" . $uye_id . "&mesaj=guncellendi");
        exit;
    }
}

// Kulüp üyelikleri
$stmt = $db->prepare("SELECT ku.*, k.ad as kulup_adi FROM kulup_uyelikleri ku JOIN kulupler k ON ku.kulup_id = k.id WHERE ku.kullanici_id = ? ORDER BY ku.katilma_tarihi DESC");
$stmt->execute([$uye_id]);
$kulupler = $stmt->fetchAll();

// Etkinlik katılımları
$stmt = $db->prepare("SELECT ek.*, e.baslik, e.tarih, e.konum FROM etkinlik_katilimlari ek JOIN etkinlikler e ON ek.etkinlik_id = e.id WHERE ek.kullanici_id = ? ORDER BY e.tarih DESC");
$stmt->execute([$uye_id]);
$etkinlikler = $stmt->fetchAll();

$rol_renkleri = [
    'Admin' => '#f87171', 'Yetkili' => '#fbbf24', 'Organizatör' => '#22d3ee', 'Üye' => '#818cf8',
];
?>

<style>
    .back-link { color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 1.5rem; }
    .back-link:hover { color: #a5b4fc; }
    .info-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;
    }
    .info-card {
        background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.75rem; padding: 1.25rem;
    }
    .info-card .label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
    .info-card .value { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; }
    .sub-title { font-size: 1rem; font-weight: 600; color: #e2e8f0; margin: 1.5rem 0 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .sub-title svg { width: 18px; height: 18px; color: #818cf8; }
    .role-select {
        padding: 0.35rem 0.5rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1);
        background: #0f172a; color: #f1f5f9; font-size: 0.8rem; font-family: 'Inter', sans-serif; outline: none; cursor: pointer;
    }
    .role-select:focus { border-color: #6366f1; }
    .btn-save {
        padding: 0.35rem 0.75rem; border-radius: 0.375rem; border: none; background: #6366f1; color: white;
        font-size: 0.75rem; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
    }
    .btn-save:hover { background: #4f46e5; }
    .role-badge {
        display: inline-block; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600;
    }
</style>

<a href="uyeler.php" class="back-link">← Üyelere Dön</a>

<div class="info-grid">
    <div class="info-card">
        <div class="label">Ad Soyad</div>
        <div class="value"><?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?></div>
    </div>
    <div class="info-card">
        <div class="label">Kullanıcı Adı</div>
        <div class="value">@<?php echo htmlspecialchars($uye['kullanici_adi']); ?></div>
    </div>
    <div class="info-card">
        <div class="label">E-posta</div>
        <div class="value"><?php echo htmlspecialchars($uye['eposta']); ?></div>
    </div>
    <div class="info-card">
        <div class="label">Rol</div>
        <div class="value"><span class="role-badge" style="background:rgba(99,102,241,0.15);color:<?php echo $rol_renkleri[$uye['rol']] ?? '#818cf8'; ?>;"><?php echo htmlspecialchars($uye['rol']); ?></span></div>
    </div>
    <div class="info-card">
        <div class="label">Kayıt Tarihi</div>
        <div class="value"><?php echo date('d.m.Y H:i', strtotime($uye['kayit_tarihi'])); ?></div>
    </div>
    <div class="info-card">
        <div class="label">Kulüp Sayısı</div>
        <div class="value"><?php echo count($kulupler); ?> kulüp</div>
    </div>
</div>

<!-- Kulüp Üyelikleri & Rol Düzenleme -->
<h3 class="sub-title">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
    Kulüp Üyelikleri & Kulüp Rolleri
</h3>

<?php if (!empty($kulupler)): ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Kulüp</th>
                <th>Katılma Tarihi</th>
                <th>Kulüp Rolü</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kulupler as $k): ?>
            <tr>
                <td><?php echo htmlspecialchars($k['kulup_adi']); ?></td>
                <td><?php echo date('d.m.Y', strtotime($k['katilma_tarihi'])); ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:0.5rem;align-items:center;">
                        <input type="hidden" name="uyelik_id" value="<?php echo $k['id']; ?>">
                        <select name="kulup_rol" class="role-select">
                            <?php foreach (['Yönetici','Başkan','Başkan Yardımcısı','Üye'] as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo $k['kulup_rol'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="kulup_rol_guncelle" value="1" class="btn-save">Kaydet</button>
                    </form>
                </td>
                <td>—</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div style="text-align:center;padding:1.5rem;color:#64748b;font-size:0.85rem;">Bu üye henüz bir kulübe katılmamış.</div>
<?php endif; ?>

<!-- Etkinlik Katılımları -->
<h3 class="sub-title">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
    Etkinlik Katılımları
</h3>

<?php if (!empty($etkinlikler)): ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Etkinlik</th>
                <th>Tarih</th>
                <th>Konum</th>
                <th>Katılma Tarihi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etkinlikler as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e['baslik']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($e['tarih'])); ?></td>
                <td><?php echo htmlspecialchars($e['konum'] ?? 'Belirtilmedi'); ?></td>
                <td><?php echo date('d.m.Y', strtotime($e['katilma_tarihi'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div style="text-align:center;padding:1.5rem;color:#64748b;font-size:0.85rem;">Bu üye henüz bir etkinliğe katılmamış.</div>
<?php endif; ?>

<?php require_once '../footer.php'; ?>
