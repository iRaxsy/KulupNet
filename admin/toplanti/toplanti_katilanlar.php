<?php
$page_title = 'Toplantı Katılımcıları - Admin Paneli';
$header_title = 'Toplantı Katılımcıları';
$active_page = 'toplantilar';
require_once '../header.php';

if (!isset($_GET['id'])) {
    header("Location: toplantilar.php");
    exit;
}

$id = (int)$_GET['id'];

// Toplantı detaylarını çek
$stmt = $db->prepare("
    SELECT t.*, k.ad as kulup_adi 
    FROM toplantilar t 
    JOIN kulupler k ON t.kulup_id = k.id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$toplanti = $stmt->fetch();

if (!$toplanti) {
    header("Location: toplantilar.php");
    exit;
}

// Toplam katılımcı sayısını çek
$total_stmt = $db->prepare("SELECT COUNT(*) FROM toplanti_katilimlari WHERE toplanti_id = ?");
$total_stmt->execute([$id]);
$kayit_sayisi = $total_stmt->fetchColumn();

// Sayfalama ayarları
$sayfa_kayit_sayisi = 5;
$sayfa = isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1;
$offset = ($sayfa - 1) * $sayfa_kayit_sayisi;

// Katılımcıları çek (Sayfalı)
$stmt_kat = $db->prepare("
    SELECT tk.*, u.id as u_id, CONCAT(u.ad, ' ', u.soyad) as ad_soyad, u.kullanici_adi as kullanici_ad, u.eposta
    FROM toplanti_katilimlari tk
    JOIN kullanicilar u ON tk.kullanici_id = u.id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.katilim_tarihi DESC
    LIMIT $sayfa_kayit_sayisi OFFSET $offset
");
$stmt_kat->execute([$id]);
$katilimcilar = $stmt_kat->fetchAll();
?>

<style>
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .btn-back {
        display: inline-flex; align-items: center; gap: 0.5rem;
        color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 600;
        transition: all 0.2s;
    }
    .btn-back:hover { color: #f1f5f9; transform: translateX(-4px); }

    /* Match uyeler.php action-btn styles */
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .action-btn svg { width: 14px; height: 14px; }
    .btn-view {
        background: rgba(99,102,241,0.1);
        color: #818cf8;
        border-color: rgba(99,102,241,0.2);
    }
    .btn-view:hover {
        background: rgba(99,102,241,0.2);
        transform: translateY(-1px);
    }
</style>

<div class="top-bar">
    <a href="toplantilar.php" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Geri Dön
    </a>
</div>


<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Kullanıcı ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Katılım Tarihi</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($katilimcilar as $k): ?>
            <tr>
                <td><?php echo $k['u_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($k['ad_soyad']); ?></strong></td>
                <td>@<?php echo htmlspecialchars($k['kullanici_ad']); ?></td>
                <td><?php echo htmlspecialchars($k['eposta']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($k['katilim_tarihi'])); ?></td>
                <td>
                    <a href="../uye/uye_detay.php?id=<?php echo $k['u_id']; ?>" class="action-btn btn-view">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Profil
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($katilimcilar)): ?>
    <div style="text-align: center; padding: 4rem; background: rgba(30,41,59,0.3); border-radius: 1rem; margin-top: 1rem; border: 1px dashed rgba(255,255,255,0.05);">
        <p style="color:#64748b; font-size:0.9rem;">Henüz katılan kimse bulunmuyor.</p>
    </div>
<?php endif; ?>

<?php
$sayfa_url = 'toplanti_katilanlar.php';
$ekstra_params = 'id=' . $id;
require_once '../sayfalama.php';
?>

<?php require_once '../footer.php'; ?>
