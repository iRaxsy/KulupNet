<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

$error = '';
$kulupler = $db->query("SELECT id, ad FROM kulupler ORDER BY ad ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik'] ?? '');
    $kulup_id = $_POST['kulup_id'] ?: null;
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = $_POST['tarih'] ?? '';
    $konum = trim($_POST['konum'] ?? '');
    $kisi_siniri = !empty($_POST['kisi_siniri']) ? (int)$_POST['kisi_siniri'] : null;

    if (empty($baslik) || empty($tarih) || empty($kulup_id)) {
        $error = "Lütfen başlık, kulüp ve tarih alanlarını doldurunuz.";
    }
    else {
        $stmt = $db->prepare("INSERT INTO etkinlikler (kulup_id, baslik, aciklama, tarih, konum, kisi_siniri) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kulup_id, $baslik, $aciklama, $tarih, $konum, $kisi_siniri]);
        header("Location: etkinlikler.php?mesaj=eklendi");
        exit;
    }
}

$page_title = 'Yeni Etkinlik Ekle - Admin Paneli';
$header_title = 'Yeni Etkinlik Ekle';
$active_page = 'etkinlikler';
require_once '../header.php';
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
    .fg input[type="number"],
    .fg select {
        width: 100%; box-sizing: border-box;
        padding: 0.75rem 0.85rem 0.75rem 2.75rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(15, 23, 42, 0.6);
        color: #f1f5f9;
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all 0.3s ease;
    }

    .fg input:focus,
    .fg select:focus,
    .fg textarea:focus {
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .fg input:focus ~ .input-icon,
    .fg select:focus ~ .input-icon {
        color: #818cf8;
    }

    .fg input[type="datetime-local"],
    .fg input[type="number"] {
        padding-right: 1rem;
    }

    .fg select {
        appearance: none;
        cursor: pointer;
    }

    .fg .select-wrapper::after {
        content: '';
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 10px;
        height: 6px;
        background: #64748b;
        clip-path: polygon(100% 0%, 0 0%, 50% 100%);
        pointer-events: none;
    }

    .fg textarea {
        width: 100%; box-sizing: border-box;
        padding: 1rem 1.75rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(15, 23, 42, 0.6);
        color: #f1f5f9;
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all 0.3s ease;
        min-height: 120px;
        resize: vertical;
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding-top: 0.5rem;
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

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <a href="etkinlikler.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
        Etkinlikler
    </a>
    <span class="sep">/</span>
    <span class="current">Yeni Etkinlik Ekle</span>
</div>

<!-- Error -->
<?php if ($error): ?>
    <div class="alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <?php echo $error; ?>
    </div>
<?php
endif; ?>

<!-- Form Card -->
<div class="modern-form-card">
    <div class="form-header">
        <div class="form-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
        <div>
            <div class="form-title">Yeni Etkinlik Bilgileri</div>
            <div class="form-subtitle">Yeni bir etkinlik oluşturun ve kulüp atayın</div>
        </div>
    </div>
    <div class="form-body">
        <form action="" method="POST" onsubmit="return confirmForm(this, 'Etkinlik Ekle', 'Yeni etkinliği kaydetmek istiyor musunuz?', 'question', 'Evet, Ekle', 'İptal')">
            <div class="fg">
                <label for="baslik">Etkinlik Başlığı</label>
                <div class="input-wrapper">
                    <input type="text" id="baslik" name="baslik" placeholder="Etkinlik başlığı girin" required>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="kulup_id">Kulüp</label>
                <div class="input-wrapper select-wrapper">
                    <select id="kulup_id" name="kulup_id" required>
                        <option value="" disabled selected>Kulüp seçiniz...</option>
                        <?php foreach ($kulupler as $kulup): ?>
                            <option value="<?php echo $kulup['id']; ?>"><?php echo htmlspecialchars($kulup['ad']); ?></option>
                        <?php
endforeach; ?>
                    </select>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="tarih">Etkinlik Tarihi</label>
                <div class="input-wrapper">
                    <input type="datetime-local" id="tarih" name="tarih" required>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="konum">Konum</label>
                <div class="input-wrapper">
                    <input type="text" id="konum" name="konum" placeholder="Etkinlik konumu (örn: Konferans Salonu)">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="kisi_siniri">Kişi Sınırı (Kontenjan)</label>
                <div class="input-wrapper">
                    <input type="number" id="kisi_siniri" name="kisi_siniri" placeholder="Örn: 50 (Sınırsız için boş bırakın)" min="1">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="aciklama">Açıklama</label>
                <textarea id="aciklama" name="aciklama" placeholder="Etkinlik detaylarını buraya yazın..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Kaydet
                </button>
                <a href="etkinlikler.php" class="btn-cancel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    İptal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
