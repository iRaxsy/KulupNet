<?php
$page_title = 'Üyeler - Admin Paneli';
$header_title = 'Üye Yönetimi';
$active_page = 'uyeler';
require_once '../header.php';

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
$kayit_sayisi = $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Üyeleri listele
$uyeler = $db->query("SELECT k.*, (SELECT COUNT(*) FROM kulup_uyelikleri WHERE kullanici_id = k.id) as kulup_sayisi FROM kullanicilar k ORDER BY k.id ASC LIMIT $sayfa_kayit_sayisi OFFSET $offset")->fetchAll();

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
</style>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Kulüp</th>
                <th>Kayıt Tarihi</th>
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
                <td><?php echo date('d.m.Y', strtotime($u['kayit_tarihi'])); ?></td>
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
$ekstra_params = '';
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
