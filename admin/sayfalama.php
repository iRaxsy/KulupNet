<?php
$sayfa_sayisi = ceil($kayit_sayisi / $sayfa_kayit_sayisi);
$aralik = 2;
$aranan = $aranan ?? '';
// XSS kapatması: aranan ve ekstra URL/HTML attribute içine gömülmeden önce kaçışlanır.
$aranan_attr = htmlspecialchars(urlencode($aranan), ENT_QUOTES, 'UTF-8');
$ekstra_attr_raw = '';
if (isset($ekstra_params) && !empty($ekstra_params)) {
    $ekstra_attr_raw = '&' . $ekstra_params;
}
// $ekstra_params zaten sayfa tarafında urlencode edilmiş çiftlerden gelir (yon=ASC&... gibi);
// güvenlik için attribute bağlamında ayrıca htmlspecialchars uygularız.
$ekstra_attr = htmlspecialchars($ekstra_attr_raw, ENT_QUOTES, 'UTF-8');
$sayfa_url_attr = htmlspecialchars($sayfa_url, ENT_QUOTES, 'UTF-8');

echo "<div style='display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem;'>";

// Sayfalama linkleri
echo "<ul style='list-style: none; display: flex; gap: 0.25rem; padding: 0; margin: 0;'>";

if ($sayfa > 1) {
    $onceki_sayfa = $sayfa - 1;
    echo "<li><a href='{$sayfa_url_attr}?sayfa={$onceki_sayfa}&aranan={$aranan_attr}{$ekstra_attr}' style='display: inline-block; padding: 0.5rem 0.85rem; border-radius: 0.375rem; background: #334155; color: #94a3b8; text-decoration: none; font-size: 0.875rem;'>&laquo;</a></li>";
}

$baslangic_no = max(1, $sayfa - $aralik);
$bitis_no = min($sayfa_sayisi, $sayfa + $aralik);

for ($x = $baslangic_no; $x <= $bitis_no; $x++) {
    if ($x == $sayfa) {
        echo "<li><a href='javascript:void(0);' style='display: inline-block; padding: 0.5rem 0.85rem; border-radius: 0.375rem; background: #6366f1; color: white; text-decoration: none; font-size: 0.875rem; font-weight: 600;'>{$x}</a></li>";
    } else {
        echo "<li><a href='{$sayfa_url_attr}?sayfa={$x}&aranan={$aranan_attr}{$ekstra_attr}' style='display: inline-block; padding: 0.5rem 0.85rem; border-radius: 0.375rem; background: #334155; color: #94a3b8; text-decoration: none; font-size: 0.875rem;'>{$x}</a></li>";
    }
}

if ($sayfa < $sayfa_sayisi) {
    $sonraki_sayfa = $sayfa + 1;
    echo "<li><a href='{$sayfa_url_attr}?sayfa={$sonraki_sayfa}&aranan={$aranan_attr}{$ekstra_attr}' style='display: inline-block; padding: 0.5rem 0.85rem; border-radius: 0.375rem; background: #334155; color: #94a3b8; text-decoration: none; font-size: 0.875rem;'>&raquo;</a></li>";
}

echo "</ul>";

// Sayfa seçici
echo "<form action='" . htmlspecialchars($sayfa_url) . "' method='get' style='display: flex; gap: 0.5rem; align-items: center;'>";
echo "<select name='sayfa' style='padding: 0.5rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.1); background: #1e293b; color: white; outline: none; font-size: 0.875rem;'>";
for ($i = 1; $i <= $sayfa_sayisi; $i++) {
    $selected = ($i == $sayfa) ? "selected" : "";
    echo "<option value='{$i}' {$selected}>{$i}</option>";
}
echo "</select>";
echo "<input type='hidden' name='aranan' value='" . htmlspecialchars($aranan) . "'>";

// Ekstra parametreleri hidden input olarak ekle
if (isset($ekstra_params) && !empty($ekstra_params)) {
    parse_str($ekstra_params, $ep);
    foreach ($ep as $key => $value) {
        echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
    }
}

echo "<button type='submit' class='btn-sm btn-primary' style='padding: 0.5rem 1rem;'>Git</button>";
echo "</form>";

echo "</div>";
?>
