<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/veritabani.php';
require_once __DIR__ . '/../kullanici_auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!kullanici_giris_kontrol()) {
    http_response_code(401);
    echo json_encode(['hata' => 'Giriş yapılmamış veya hesap askıya alınmış']);
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];
$aksiyon = $_GET['aksiyon'] ?? '';

// POST'la yapılan state-changing aksiyonlar CSRF token ister
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($aksiyon, ['oku', 'tumunu_oku'], true)) {
    if (!csrf_dogrula()) {
        http_response_code(400);
        echo json_encode(['hata' => 'Geçersiz CSRF token']);
        exit;
    }
}

// Kullanıcının kayıt tarihini al
$stmt = $db->prepare("SELECT kayit_tarihi FROM kullanicilar WHERE id = ?");
$stmt->execute([$kullanici_id]);
$kayit_tarihi = $stmt->fetchColumn();
if (!$kayit_tarihi) {
    echo json_encode(['hata' => 'Kullanıcı bulunamadı']);
    exit;
}

// Kullanıcının üye olduğu kulüpler
$stmt_kulup = $db->prepare("SELECT kulup_id FROM kulup_uyelikleri WHERE kullanici_id = ?");
$stmt_kulup->execute([$kullanici_id]);
$uye_kulup_ids = $stmt_kulup->fetchAll(PDO::FETCH_COLUMN);

if ($aksiyon === 'getir') {
    $bildirimler = [];
    $okunanlar_modu = isset($_GET['okunanlar']) && $_GET['okunanlar'] === '1';
    
    // Geçmiş için daha uzun süre (90 gün), aktif için 30 gün
    $gun_siniri = $okunanlar_modu ? '-90 days' : '-30 days';
    $tarih_siniri = date('Y-m-d H:i:s', strtotime($gun_siniri));
    
    // Okunmuşluk kontrolü: okunanlar modunda IN, normal modda NOT IN
    $okunma_kosulu = $okunanlar_modu ? "IN" : "NOT IN";

    // 1. Duyurular
    if (!empty($uye_kulup_ids)) {
        $placeholders = implode(',', array_fill(0, count($uye_kulup_ids), '?'));
        $params = array_merge([$kayit_tarihi, $tarih_siniri], $uye_kulup_ids, [$kayit_tarihi, $tarih_siniri, $kullanici_id]);
        $sql = "
            SELECT d.id, d.tur, d.duyuru as mesaj, d.tarih, d.kulup_id
            FROM duyurular d
            WHERE (
                (d.kulup_id IS NULL AND d.tarih >= ? AND d.tarih >= ?)
                OR 
                (d.kulup_id IN ($placeholders) AND d.tarih >= ? AND d.tarih >= ?)
            )
            AND d.id $okunma_kosulu (
                SELECT ilgili_id FROM bildirim_okumalari 
                WHERE kullanici_id = ? AND tur = 'duyuru'
            )
            ORDER BY d.tarih DESC
            LIMIT 20
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $db->prepare("
            SELECT d.id, d.tur, d.duyuru as mesaj, d.tarih, d.kulup_id
            FROM duyurular d
            WHERE d.kulup_id IS NULL AND d.tarih >= ? AND d.tarih >= ?
            AND d.id $okunma_kosulu (
                SELECT ilgili_id FROM bildirim_okumalari 
                WHERE kullanici_id = ? AND tur = 'duyuru'
            )
            ORDER BY d.tarih DESC
            LIMIT 20
        ");
        $stmt->execute([$kayit_tarihi, $tarih_siniri, $kullanici_id]);
    }
    $duyurular = $stmt->fetchAll();
    foreach ($duyurular as $d) {
        $bildirimler[] = [
            'id' => $d['id'],
            'tur' => 'duyuru',
            'baslik' => $d['tur'],
            'mesaj' => mb_substr($d['mesaj'], 0, 80) . (mb_strlen($d['mesaj']) > 80 ? '...' : ''),
            'tarih' => $d['tarih'],
            'link' => 'duyurular.php'
        ];
    }

    // 2. Toplantılar
    if (!empty($uye_kulup_ids)) {
        $placeholders = implode(',', array_fill(0, count($uye_kulup_ids), '?'));
        $params = array_merge($uye_kulup_ids, [$kayit_tarihi, $tarih_siniri, $kullanici_id]);
        $stmt = $db->prepare("
            SELECT t.id, t.konu, t.tarih, k.ad as kulup_adi
            FROM toplantilar t
            JOIN kulupler k ON t.kulup_id = k.id
            WHERE t.kulup_id IN ($placeholders) 
            AND t.olusturma_tarihi >= ? AND t.olusturma_tarihi >= ?
            AND t.id $okunma_kosulu (
                SELECT ilgili_id FROM bildirim_okumalari 
                WHERE kullanici_id = ? AND tur = 'toplanti'
            )
            ORDER BY t.olusturma_tarihi DESC
            LIMIT 20
        ");
        $stmt->execute($params);
        $toplantilar = $stmt->fetchAll();
        foreach ($toplantilar as $t) {
            $bildirimler[] = [
                'id' => $t['id'],
                'tur' => 'toplanti',
                'baslik' => 'Toplantı: ' . $t['kulup_adi'],
                'mesaj' => mb_substr($t['konu'], 0, 80) . (mb_strlen($t['konu']) > 80 ? '...' : ''),
                'tarih' => $t['tarih'],
                'link' => 'toplantilar.php'
            ];
        }
    }

    // 3. Etkinlikler
    if (!empty($uye_kulup_ids)) {
        $placeholders = implode(',', array_fill(0, count($uye_kulup_ids), '?'));
        $params = array_merge($uye_kulup_ids, [$tarih_siniri, $kullanici_id]);
        $stmt = $db->prepare("
            SELECT e.id, e.baslik, e.tarih, k.ad as kulup_adi
            FROM etkinlikler e
            JOIN kulupler k ON e.kulup_id = k.id
            WHERE e.kulup_id IN ($placeholders) 
            AND e.tarih >= ?
            AND e.id $okunma_kosulu (
                SELECT ilgili_id FROM bildirim_okumalari 
                WHERE kullanici_id = ? AND tur = 'etkinlik'
            )
            ORDER BY e.tarih ASC
            LIMIT 20
        ");
        $stmt->execute($params);
        $etkinlikler = $stmt->fetchAll();
        foreach ($etkinlikler as $e) {
            $bildirimler[] = [
                'id' => $e['id'],
                'tur' => 'etkinlik',
                'baslik' => 'Etkinlik: ' . $e['kulup_adi'],
                'mesaj' => mb_substr($e['baslik'], 0, 80) . (mb_strlen($e['baslik']) > 80 ? '...' : ''),
                'tarih' => $e['tarih'],
                'link' => 'etkinlikler.php'
            ];
        }
    }

    // Tarihe göre sırala
    usort($bildirimler, function($a, $b) {
        return strtotime($b['tarih']) - strtotime($a['tarih']);
    });

    $bildirimler = array_slice($bildirimler, 0, 15);

    echo json_encode([
        'bildirimler' => $bildirimler,
        'okunmamis_sayisi' => (!$okunanlar_modu ? count($bildirimler) : 0)
    ]);

} elseif ($aksiyon === 'oku') {
    $tur = $_POST['tur'] ?? '';
    $ilgili_id = (int)($_POST['ilgili_id'] ?? 0);

    if (!in_array($tur, ['duyuru', 'toplanti', 'etkinlik']) || $ilgili_id <= 0) {
        echo json_encode(['hata' => 'Geçersiz parametre']);
        exit;
    }

    $stmt = $db->prepare("INSERT IGNORE INTO bildirim_okumalari (kullanici_id, tur, ilgili_id) VALUES (?, ?, ?)");
    $stmt->execute([$kullanici_id, $tur, $ilgili_id]);

    echo json_encode(['basarili' => true]);

} elseif ($aksiyon === 'tumunu_oku') {
    $tur = $_POST['tur'] ?? '';
    $idler = json_decode($_POST['idler'] ?? '[]', true);

    if (!empty($idler) && is_array($idler)) {
        foreach ($idler as $item) {
            if (isset($item['tur'], $item['id']) && in_array($item['tur'], ['duyuru', 'toplanti', 'etkinlik'])) {
                $stmt = $db->prepare("INSERT IGNORE INTO bildirim_okumalari (kullanici_id, tur, ilgili_id) VALUES (?, ?, ?)");
                $stmt->execute([$kullanici_id, $item['tur'], (int)$item['id']]);
            }
        }
    }

    echo json_encode(['basarili' => true]);

} else {
    echo json_encode(['hata' => 'Geçersiz aksiyon']);
}
