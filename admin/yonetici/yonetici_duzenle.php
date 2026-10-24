<?php
require_once '../auth.php';
require_once '../../config/veritabani.php';

$error = '';
if (!isset($_GET['id'])) {
    header("Location: yoneticiler.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT * FROM adminler WHERE id = ?");
$stmt->execute([$id]);
$yonetici = $stmt->fetch();

if (!$yonetici) {
    header("Location: yoneticiler.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');

    if (!empty($kullanici_adi) && !preg_match('/^[a-zA-Z0-9_\-\x{00C7}\x{011E}\x{0130}\x{0131}\x{00D6}\x{00DC}\x{00E7}\x{011F}\x{00F6}\x{00FC} ]{3,30}$/u', $kullanici_adi)) {
        $error = "Kullanıcı adı 3-30 karakter olmalı ve yalnızca harf, rakam, boşluk, alt çizgi veya tire içerebilir.";
    } elseif (!empty($kullanici_adi)) {
        // Aynı kullanıcı adına sahip başka bir kayıt var mı kontrol et
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM adminler WHERE kullanici_adi = ? AND id != ?");
        $check_stmt->execute([$kullanici_adi, $id]);
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Bu kullanıcı adı başka bir yönetici tarafından kullanılıyor.";
        } else {
            if (!empty($sifre)) {
                $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE adminler SET kullanici_adi = ?, sifre = ? WHERE id = ?");
                $upd->execute([$kullanici_adi, $hashed_sifre, $id]);
            } else {
                $upd = $db->prepare("UPDATE adminler SET kullanici_adi = ? WHERE id = ?");
                $upd->execute([$kullanici_adi, $id]);
            }

            // Kendi kullanıcı adı güncellendiyse session'ı da güncelle
            if ($id == $_SESSION['admin_id']) {
                $_SESSION['admin_adi'] = $kullanici_adi;
            }

            header("Location: yoneticiler.php?mesaj=guncellendi");
            exit;
        }
    } else {
        $error = "Kullanıcı adı boş bırakılamaz.";
    }
}

$page_title = 'Yönetici Düzenle - Admin Paneli';
$header_title = 'Yönetici Düzenle';
$active_page = 'yoneticiler';
require_once '../header.php';
?>

<style>
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
        background: rgba(59, 130, 246, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .form-header .form-icon svg { width: 20px; height: 20px; color: #60a5fa; }
    .form-header .form-title { font-size: 1rem; font-weight: 600; color: #e2e8f0; }
    .form-header .form-subtitle { font-size: 0.75rem; color: #64748b; margin-top: 0.1rem; }
    .form-body { padding: 1.75rem; }

    .fg { margin-bottom: 1.5rem; }
    .fg label {
        display: block; font-size: 0.8rem; font-weight: 600;
        color: #94a3b8; margin-bottom: 0.5rem; letter-spacing: 0.02em;
    }
    
    .fg .input-wrapper { position: relative; }
    .fg .input-icon {
        position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
        width: 18px; height: 18px; color: #475569; pointer-events: none; transition: color 0.3s;
    }

    .fg input[type="text"], .fg input[type="password"] {
        width: 100%; box-sizing: border-box; border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.08); background: rgba(15, 23, 42, 0.6);
        color: #f1f5f9; font-size: 0.875rem; font-family: 'Inter', sans-serif;
        outline: none; transition: all 0.3s ease; padding: 0.75rem 0.85rem 0.75rem 2.75rem;
    }

    .fg input:focus {
        border-color: rgba(99, 102, 241, 0.5); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .fg input:focus ~ .input-icon { color: #818cf8; }

    .form-actions { display: flex; gap: 0.75rem; align-items: center; padding-top: 0.5rem; }
    .btn-submit {
        display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.75rem 1.5rem;
        border-radius: 0.625rem; font-size: 0.85rem; font-weight: 600;
        font-family: 'Inter', sans-serif; border: none; cursor: pointer;
        transition: all 0.3s ease; background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(59, 130, 246, 0.4); }
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

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        15% { transform: translateX(-6px); }
        30% { transform: translateX(5px); }
        45% { transform: translateX(-4px); }
        60% { transform: translateX(3px); }
        75% { transform: translateX(-2px); }
    }

    .breadcrumb-bar {
        display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;
        animation: fadeInUp 0.4s ease forwards;
    }
    .breadcrumb-bar a {
        display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem;
        font-weight: 500; color: #64748b; text-decoration: none; transition: color 0.2s;
    }
    .breadcrumb-bar a:hover { color: #60a5fa; }
    .breadcrumb-bar a svg { width: 14px; height: 14px; }
    .breadcrumb-bar .sep { color: #334155; font-size: 0.75rem; }
    .breadcrumb-bar .current { font-size: 0.8rem; font-weight: 600; color: #94a3b8; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <a href="yoneticiler.php">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        Yöneticiler
    </a>
    <span class="sep">/</span>
    <span class="current">Yönetici Düzenle</span>
</div>

<!-- Error -->
<?php if ($error): ?>
    <div class="alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <?php echo $error; ?>
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
            <div class="form-title">Yönetici Düzenle (ID: <?php echo $yonetici['id']; ?>)</div>
            <div class="form-subtitle">Sistem yöneticisinin bilgilerini güncelleyin</div>
        </div>
    </div>
    <div class="form-body">
        <form action="" method="POST" autocomplete="off" onsubmit="return confirmForm(this, 'Yöneticiyi Güncelle', 'Yönetici bilgilerini kaydetmek istiyor musunuz?', 'question', 'Evet, Güncelle', 'İptal')">
            <div class="fg">
                <label for="kullanici_adi">Kullanıcı Adı</label>
                <div class="input-wrapper">
                    <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?php echo htmlspecialchars($yonetici['kullanici_adi']); ?>" required autocomplete="new-password">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="sifre">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                <div class="input-wrapper">
                    <input type="password" id="sifre" name="sifre" placeholder="Şifreyi girin" autocomplete="new-password">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Güncelle
                </button>
                <a href="yoneticiler.php" class="btn-cancel">
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
