<?php
$page_title = 'Üyeler - Admin Paneli';
$header_title = 'Üye Yönetimi';
$active_page = 'uyeler';
require_once '../header.php';

$kulup_id = (int)($_GET['kulup_id'] ?? 0);
$kulup_adi = '';

if ($kulup_id > 0) {
    $stmt = $db->prepare("SELECT ad FROM kulupler WHERE id = ?");
    $stmt->execute([$kulup_id]);
    $kulup_adi = $stmt->fetchColumn();
    if ($kulup_adi) {
        $header_title = $kulup_adi . ' Üye Listesi';
    }
}

// Rol güncelle
if (isset($_POST['rol_guncelle'])) {
    $uye_id = (int)$_POST['uye_id'];
    $yeni_rol = $_POST['yeni_rol'];
    $gecerli_roller = ['Admin', 'Yetkili', 'Organizatör', 'Üye'];
    if (in_array($yeni_rol, $gecerli_roller)) {
        $stmt = $db->prepare("UPDATE kullanicilar SET rol = ? WHERE id = ?");
        $stmt->execute([$yeni_rol, $uye_id]);
        header("Location: uyeler.php?mesaj=guncellendi");
        exit;
    }
}

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;

if ($kulup_id > 0) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar k JOIN kulup_uyelikleri ku ON k.id = ku.kullanici_id WHERE ku.kulup_id = ?");
    $count_stmt->execute([$kulup_id]);
} else {
    $count_stmt = $db->query("SELECT COUNT(*) FROM kullanicilar");
}
$kayit_sayisi = $count_stmt->fetchColumn();
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Üyeleri listele
if ($kulup_id > 0) {
    $stmt = $db->prepare("
        SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi 
        FROM kullanicilar k 
        JOIN kulup_uyelikleri ku ON k.id = ku.kullanici_id 
        WHERE ku.kulup_id = ? 
        ORDER BY k.id ASC 
        LIMIT $sayfa_kayit_sayisi OFFSET $offset
    ");
    $stmt->execute([$kulup_id]);
    $uyeler = $stmt->fetchAll();
} else {
    $uyeler = $db->query("SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi FROM kullanicilar k ORDER BY k.id ASC LIMIT $sayfa_kayit_sayisi OFFSET $offset")->fetchAll();
}

$rol_renkleri = [
    'Admin' => '#f87171',
    'Yetkili' => '#fbbf24',
    'Organizatör' => '#22d3ee',
    'Üye' => '#818cf8',
];
?>

<style>
    .role-select {
        padding: 0.35rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: #0f172a;
        color: #f1f5f9;
        font-size: 0.8rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        cursor: pointer;
    }
    .role-select:focus { border-color: #6366f1; }
    .role-badge {
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .btn-save {
        padding: 0.35rem 0.75rem;
        border-radius: 0.375rem;
        border: none;
        background: #6366f1;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .btn-save:hover { background: #4f46e5; }
    .detail-link {
        color: #818cf8;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        transition: color 0.2s;
    }
    .detail-link:hover { color: #a5b4fc; }
    .filter-info { margin-bottom: 1rem; color: #94a3b8; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
    .btn-clear-filter { color: #f87171; text-decoration: none; font-weight: 600; background: rgba(239, 68, 68, 0.1); padding: 0.25rem 0.6rem; border-radius: 0.5rem; font-size: 0.75rem; border: 1px solid rgba(239, 68, 68, 0.2); transition: all 0.2s; }
    .btn-clear-filter:hover { background: rgba(239, 68, 68, 0.2); }
</style>

<?php if ($kulup_id > 0 && $kulup_adi): ?>
<div class="filter-info">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;color:#818cf8;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
    </svg>
    <strong><?php echo htmlspecialchars($kulup_adi); ?></strong> kulübünün üyeleri gösteriliyor.
    <a href="uyeler.php" class="btn-clear-filter">Filtreyi Temizle</a>
</div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Kulüp</th>
                <th>Cinsiyet</th>
                <th>Rol</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($uyeler as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['ad'] . ' ' . $u['soyad']); ?></td>
                <td><?php echo htmlspecialchars($u['kullanici_adi']); ?></td>
                <td><?php echo htmlspecialchars($u['eposta']); ?></td>
                <td><?php echo $u['kulup_sayisi']; ?> kulüp</td>
                <td><?php echo ($u['cinsiyet'] === 'Kadin') ? 'Kadın' : htmlspecialchars($u['cinsiyet']); ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:0.5rem;align-items:center;">
                        <input type="hidden" name="uye_id" value="<?php echo $u['id']; ?>">
                        <select name="yeni_rol" class="role-select">
                            <?php foreach (['Admin','Yetkili','Organizatör','Üye'] as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo $u['rol'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="rol_guncelle" value="1" class="btn-save">Kaydet</button>
                    </form>
                </td>
                <td>
                    <a href="uye_detay.php?id=<?php echo $u['id']; ?>" class="detail-link">Detay →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($uyeler)): ?>
    <div style="text-align:center;padding:2rem;color:#64748b;">Henüz kayıtlı üye bulunmuyor.</div>
<?php endif; ?>

<?php
$sayfa_url = 'uyeler.php';
$ekstra_params = $kulup_id > 0 ? 'kulup_id=' . $kulup_id : '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
