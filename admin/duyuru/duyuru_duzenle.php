<?php
$page_title = 'Duyuru Düzenle - Admin Paneli';
$header_title = 'Duyuru Düzenleme';
$active_page = 'duyurular';
require_once '../header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hata = '';
$basari = '';

if ($id === 0) {
    header("Location: duyurular.php");
    exit;
}

// Mevcut kaydı getir
$stmt = $db->prepare("SELECT * FROM duyurular WHERE id = ?");
$stmt->execute([$id]);
$duyuru = $stmt->fetch();

if (!$duyuru) {
    header("Location: duyurular.php");
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $yeni_icerik = trim($_POST['duyuru'] ?? '');

    if (empty($yeni_icerik)) {
        $hata = "Duyuru içeriği boş bırakılamaz.";
    } else {
        $update_stmt = $db->prepare("UPDATE duyurular SET duyuru = ? WHERE id = ?");
        if ($update_stmt->execute([$yeni_icerik, $id])) {
            header("Location: duyurular.php?mesaj=guncellendi");
            exit;
        } else {
            $hata = "Duyuru güncellenirken bir hata oluştu.";
        }
    }
}
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

    .form-header .form-icon svg { width: 20px; height: 20px; color: #10b981; }
    .form-header .form-title { font-size: 1rem; font-weight: 600; color: #e2e8f0; }
    .form-header .form-subtitle { font-size: 0.75rem; color: #64748b; margin-top: 0.1rem; }
    .form-body { padding: 1.75rem; }

    .fg { margin-bottom: 1.5rem; }
    .fg label {
        display: block; font-size: 0.8rem; font-weight: 600;
        color: #94a3b8; margin-bottom: 0.5rem; letter-spacing: 0.02em;
    }
    
    .fg textarea {
        width: 100%; box-sizing: border-box; border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.08); background: rgba(15, 23, 42, 0.6);
        color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
        outline: none; transition: all 0.3s ease; padding: 1rem 1.25rem;
        min-height: 200px; resize: vertical; line-height: 1.6;
    }

    .fg textarea:focus {
        border-color: rgba(16, 185, 129, 0.5); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .form-actions { display: flex; gap: 0.75rem; align-items: center; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.06); }
    .btn-submit {
        display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.75rem 1.5rem;
        border-radius: 0.625rem; font-size: 0.85rem; font-weight: 600;
        font-family: 'Inter', sans-serif; border: none; cursor: pointer;
        transition: all 0.3s ease; background: linear-gradient(135deg, #10b981, #059669);
        color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4); }
    .btn-submit svg { width: 16px; height: 16px; }

    .btn-cancel {
        display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.75rem 1.5rem;
        border-radius: 0.625rem; font-size: 0.85rem; font-weight: 600; text-decoration: none;
        color: #94a3b8; background: rgba(51, 65, 85, 0.5); border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease;
    }
    .btn-cancel:hover { background: rgba(51, 65, 85, 0.8); color: #e2e8f0; transform: translateY(-1px); }
    .btn-cancel svg { width: 16px; height: 16px; }

    .alert-error {
        display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem;
        border-radius: 0.75rem; margin-bottom: 1.25rem; font-size: 0.85rem; font-weight: 500;
        background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171; animation: shakeError 0.5s ease;
    }
    .alert-error svg { width: 20px; height: 20px; flex-shrink: 0; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Breadcrumb */
    .breadcrumb-bar {
        display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;
        animation: fadeInUp 0.4s ease forwards;
    }
    .breadcrumb-bar a {
        display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem;
        font-weight: 500; color: #64748b; text-decoration: none; transition: color 0.2s;
    }
    .breadcrumb-bar a:hover { color: #10b981; }
    .breadcrumb-bar a svg { width: 14px; height: 14px; }
    .breadcrumb-bar .sep { color: #334155; font-size: 0.75rem; }
    .breadcrumb-bar .current { font-size: 0.8rem; font-weight: 600; color: #94a3b8; }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <a href="duyurular.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
        </svg>
        Duyurular
    </a>
    <span class="sep">/</span>
    <span class="current">Duyuru Düzenle</span>
</div>

<!-- Error -->
<?php if ($hata): ?>
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
            <div class="form-title">Duyuruyu Düzenle</div>
            <div class="form-subtitle">Duyuru içeriğini güncelleyin</div>
        </div>
    </div>
    <div class="form-body">
        <form method="POST">
            <div class="fg">
                <label for="duyuru">Duyuru İçeriği *</label>
                <textarea id="duyuru" name="duyuru" placeholder="Duyuru metni..." required><?php echo htmlspecialchars($duyuru['duyuru']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                    </svg>
                    Güncelle
                </button>
                <a href="duyurular.php" class="btn-cancel">
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
