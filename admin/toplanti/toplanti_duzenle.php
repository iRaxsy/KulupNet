<?php
$page_title = 'Toplantı Düzenle - Admin Paneli';
$header_title = 'Toplantı Düzenle';
$active_page = 'toplantilar';
require_once '../header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: toplantilar.php"); exit; }

$stmt = $db->prepare("SELECT * FROM toplantilar WHERE id = ?");
$stmt->execute([$id]);
$toplanti = $stmt->fetch();

if (!$toplanti) { header("Location: toplantilar.php"); exit; }

// Kulüpleri çek
$kulupler = $db->query("SELECT id, ad FROM kulupler ORDER BY ad ASC")->fetchAll();

// Form gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kulup_id = (int)$_POST['kulup_id'];
    $konu = trim($_POST['konu'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = $_POST['tarih'] ?? '';
    $konum = trim($_POST['konum'] ?? '');
    
    // Hedef rol - Checkbox'lardan gelen dizi
    $secilen_roller = $_POST['hedef_roller'] ?? [];
    if (empty($secilen_roller)) {
        // Hiçbir şey seçilmediyse veya 'Herkes' seçildiyse:
        $hedef_rol = 'Herkes';
    } else {
        $hedef_rol = implode(',', $secilen_roller);
    }

    if ($kulup_id && $konu && $tarih && $konum) {
        $stmt = $db->prepare("UPDATE toplantilar SET kulup_id=?, konu=?, aciklama=?, tarih=?, konum=?, hedef_rol=? WHERE id=?");
        $stmt->execute([$kulup_id, $konu, $aciklama, $tarih, $konum, $hedef_rol, $id]);
        header("Location: toplantilar.php?mesaj=guncellendi");
        exit;
    } else {
        $hata = "Lütfen tüm zorunlu alanları doldurun.";
    }
}

$secili_roller = explode(',', $toplanti['hedef_rol']);
$secili_roller = array_map('trim', $secili_roller);
$toplanti_tarihi = date('Y-m-d\TH:i', strtotime($toplanti['tarih']));
?>

<style>
    /* Form Card */
    .modern-form-card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(30, 41, 59, 0.4));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: 0.1s;
        opacity: 0;
    }

    .form-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 1.75rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .form-header .form-icon {
        width: 40px;
        height: 40px;
        border-radius: 0.625rem;
        background: rgba(16, 185, 129, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .form-header .form-icon svg {
        width: 20px;
        height: 20px;
        color: #10b981;
    }

    .form-header .form-title {
        font-size: 1rem;
        font-weight: 600;
        color: #e2e8f0;
    }

    .form-header .form-subtitle {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.1rem;
    }

    .form-body {
        padding: 1.75rem;
    }

    /* Form Group */
    .fg {
        margin-bottom: 1.5rem;
    }

    .fg label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 0.5rem;
        letter-spacing: 0.02em;
    }

    /* Input with Icon Styles */
    .fg .input-wrapper {
        position: relative;
    }

    .fg .input-icon {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: #475569;
        pointer-events: none;
        transition: color 0.3s;
    }

    .fg input[type="text"],
    .fg input[type="datetime-local"],
    .fg select,
    .fg textarea {
        width: 100%; box-sizing: border-box;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(15, 23, 42, 0.6);
        color: #f1f5f9;
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all 0.3s ease;
    }

    .fg input[type="text"],
    .fg input[type="datetime-local"],
    .fg select {
        padding: 0.75rem 0.85rem 0.75rem 2.75rem;
    }

    .fg textarea {
        padding: 1rem 1.75rem;
        min-height: 120px;
        resize: vertical;
    }

    .fg input:focus,
    .fg select:focus,
    .fg textarea:focus {
        border-color: rgba(16, 185, 129, 0.5);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .fg input:focus ~ .input-icon,
    .fg select:focus ~ .input-icon {
        color: #34d399;
    }
    
    .fg select option { 
        background: #1e293b; color: #f1f5f9; padding: 0.5rem; 
    }

    /* Modern Checkbox Style */
    .checkbox-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;
    }
    .check-option {
        position: relative;
    }
    .check-option input[type="checkbox"] {
        position: absolute; opacity: 0; width: 0; height: 0;
    }
    .check-option label {
        display: flex; align-items: center; justify-content: flex-start; gap: 0.75rem;
        padding: 0.75rem 1rem; border-radius: 0.625rem; cursor: pointer;
        border: 1px solid rgba(255,255,255,0.08); background: rgba(15, 23, 42, 0.6);
        color: #cbd5e1; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;
    }
    .check-option label::before {
        content: ''; width: 18px; height: 18px; border-radius: 4px;
        border: 2px solid rgba(255,255,255,0.2); transition: all 0.2s; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .check-option input[type="checkbox"]:checked + label {
        border-color: rgba(16,185,129,0.5); background: rgba(16, 185, 129, 0.15); color: #a7f3d0;
    }
    .check-option input[type="checkbox"]:checked + label::before {
        background-color: #10b981; border-color: #10b981;
        content: '✓'; color: white; font-size: 12px; font-weight: bold;
    }
    .check-option label:hover { border-color: rgba(255,255,255,0.2); }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding-top: 0.5rem;
        margin-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        padding-top: 1.5rem;
    }

    .btn-submit {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.625rem;
        font-size: 0.85rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-submit svg {
        width: 16px;
        height: 16px;
    }

    .btn-cancel {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.625rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        color: #94a3b8;
        background: rgba(51, 65, 85, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: rgba(51, 65, 85, 0.8);
        color: #e2e8f0;
        transform: translateY(-1px);
    }

    .btn-cancel svg {
        width: 16px;
        height: 16px;
    }

    /* Error Alert */
    .alert-error {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        margin-bottom: 1.25rem;
        font-size: 0.85rem;
        font-weight: 500;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        animation: shakeError 0.5s ease;
    }

    .alert-error svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        15% { transform: translateX(-6px); }
        30% { transform: translateX(5px); }
        45% { transform: translateX(-4px); }
        60% { transform: translateX(3px); }
        75% { transform: translateX(-2px); }
    }

    /* Breadcrumb */
    .breadcrumb-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        animation: fadeInUp 0.4s ease forwards;
    }

    .breadcrumb-bar a {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb-bar a:hover {
        color: #10b981;
    }

    .breadcrumb-bar a svg {
        width: 14px;
        height: 14px;
    }

    .breadcrumb-bar .sep {
        color: #334155;
        font-size: 0.75rem;
    }

    .breadcrumb-bar .current {
        font-size: 0.8rem;
        font-weight: 600;
        color: #94a3b8;
    }

    .help-text { font-size: 0.75rem; color: #64748b; margin-top: 0.75rem; display: block; }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <a href="toplantilar.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        Toplantılar
    </a>
    <span class="sep">/</span>
    <span class="current">Toplantı Düzenle</span>
</div>

<!-- Error -->
<?php if (isset($hata)): ?>
    <div class="alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <?php echo $hata; ?>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="modern-form-card">
    <div class="form-header">
        <div class="form-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
        </div>
        <div>
            <div class="form-title">Toplantıyı Düzenle</div>
            <div class="form-subtitle">Toplantının detaylarını güncelleyin</div>
        </div>
    </div>
    <div class="form-body">
        <form method="POST">
            
            <div class="fg">
                <label for="kulup_id">Kulüp *</label>
                <div class="input-wrapper">
                    <select id="kulup_id" name="kulup_id" required>
                        <option value="">-- Kulüp Seçin --</option>
                        <?php foreach ($kulupler as $k): ?>
                            <option value="<?php echo $k['id']; ?>" <?php echo $k['id'] == $toplanti['kulup_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($k['ad']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="konu">Toplantı Konusu *</label>
                <div class="input-wrapper">
                    <input type="text" id="konu" name="konu" placeholder="Toplantının konusu..." value="<?php echo htmlspecialchars($toplanti['konu']); ?>" required maxlength="200">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="aciklama">Açıklama</label>
                <textarea id="aciklama" name="aciklama" placeholder="Kısa bir açıklama..." rows="3"><?php echo htmlspecialchars($toplanti['aciklama']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="fg">
                    <label for="tarih">Tarih ve Saat *</label>
                    <div class="input-wrapper">
                        <input type="datetime-local" id="tarih" name="tarih" value="<?php echo $toplanti_tarihi; ?>" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                        </svg>
                    </div>
                </div>
                <div class="fg">
                    <label for="konum">Konum *</label>
                    <div class="input-wrapper">
                        <input type="text" id="konum" name="konum" placeholder="Ör: Toplantı Salonu A" value="<?php echo htmlspecialchars($toplanti['konum']); ?>" required maxlength="200">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="fg">
                <label>Katılım Hedefleri (Roller)</label>
                <div class="checkbox-grid">
                    <div class="check-option">
                        <input type="checkbox" name="hedef_roller[]" value="Yönetici" id="rol_yonetici" class="rol-cb" <?php echo in_array('Yönetici', $secili_roller) ? 'checked' : ''; ?>>
                        <label for="rol_yonetici">Yönetici</label>
                    </div>
                    <div class="check-option">
                        <input type="checkbox" name="hedef_roller[]" value="Başkan" id="rol_baskan" class="rol-cb" <?php echo in_array('Başkan', $secili_roller) ? 'checked' : ''; ?>>
                        <label for="rol_baskan">Başkan</label>
                    </div>
                    <div class="check-option">
                        <input type="checkbox" name="hedef_roller[]" value="Başkan Yardımcısı" id="rol_baskan_yrd" class="rol-cb" <?php echo in_array('Başkan Yardımcısı', $secili_roller) ? 'checked' : ''; ?>>
                        <label for="rol_baskan_yrd">Başkan Yrd.</label>
                    </div>
                    <div class="check-option">
                        <input type="checkbox" name="hedef_roller[]" value="Üye" id="rol_uye" class="rol-cb" <?php echo in_array('Üye', $secili_roller) ? 'checked' : ''; ?>>
                        <label for="rol_uye">Üye</label>
                    </div>
                    <div class="check-option">
                        <input type="checkbox" name="hedef_roller[]" value="Herkes" id="rol_herkes" class="rol-cb" <?php echo in_array('Herkes', $secili_roller) ? 'checked' : ''; ?>>
                        <label for="rol_herkes">Herkes</label>
                    </div>
                </div>
                <span class="help-text">Birden fazla rol seçebilirsiniz. "Herkes" seçili iken tüm üyelere duyuru gider. Başka roller seçildiğinde "Herkes" iptal olur.</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                    </svg>
                    Değişiklikleri Kaydet
                </button>
                <a href="toplantilar.php" class="btn-cancel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    İptal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roller = document.querySelectorAll('.rol-cb:not(#rol_herkes)');
    const herkes = document.getElementById('rol_herkes');

    // Herkes tıklanırsa diğerlerini temizle
    herkes.addEventListener('change', function() {
        if (this.checked) {
            roller.forEach(cb => cb.checked = false);
        } else {
            // Hiçbiri seçili değilse Herkes kapatılamaz
            let anyChecked = Array.from(roller).some(cb => cb.checked);
            if (!anyChecked) {
                this.checked = true;
            }
        }
    });

    // Diğer rollerden biri tıklanırsa Herkes'i yönet
    roller.forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                herkes.checked = false;
            } else {
                // Eğer tüm diğer rollerin işareti kalktıysa "Herkes"i otomatik seç
                let anyChecked = Array.from(roller).some(c => c.checked);
                if (!anyChecked) {
                    herkes.checked = true;
                }
            }
        });
    });
});
</script>

<?php require_once '../footer.php'; ?>
