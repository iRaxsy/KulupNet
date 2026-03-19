<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');

    if (!empty($ad)) {
        $stmt = $db->prepare("INSERT INTO kulupler (ad, aciklama) VALUES (?, ?)");
        $stmt->execute([$ad, $aciklama]);
        header("Location: kulupler.php?mesaj=eklendi");
        exit;
    }
    else {
        $error = "Lütfen kulüp adını giriniz.";
    }
}

$page_title = 'Yeni Kulüp Ekle - Admin Paneli';
$header_title = 'Yeni Kulüp Ekle';
$active_page = 'kulupler';
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

    .fg input[type="text"] {
        padding: 0.75rem 0.85rem 0.75rem 2.75rem;
    }

    .fg textarea {
        padding: 1rem 1.75rem;
        min-height: 120px;
        resize: vertical;
    }

    .fg input:focus,
    .fg textarea:focus {
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .fg input:focus ~ .input-icon {
        color: #818cf8;
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
    <a href="kulupler.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
        Kulüpler
    </a>
    <span class="sep">/</span>
    <span class="current">Yeni Kulüp Ekle</span>
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
            <div class="form-title">Yeni Kulüp Bilgileri</div>
            <div class="form-subtitle">Sisteme yeni bir öğrenci kulübü ekleyin</div>
        </div>
    </div>
    <div class="form-body">
        <form action="" method="POST" onsubmit="return confirmForm(this, 'Kulüp Ekle', 'Yeni kulübü kaydetmek istiyor musunuz?', 'question', 'Evet, Ekle', 'İptal')">
            <div class="fg">
                <label for="ad">Kulüp Adı</label>
                <div class="input-wrapper">
                    <input type="text" id="ad" name="ad" placeholder="Kulüp adını girin" required>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="aciklama">Açıklama</label>
                <textarea id="aciklama" name="aciklama" placeholder="Kulüp hakkında kısa bilgi yazın..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Kaydet
                </button>
                <a href="kulupler.php" class="btn-cancel">
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
