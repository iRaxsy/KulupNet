<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/veritabani.php';

// Base URL calculation (rough but functional for this structure)
$is_subdir = (strpos($_SERVER['SCRIPT_NAME'], '/kulup/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/etkinlik/') !== false);
$base_prefix = $is_subdir ? '../' : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Paneli'; ?></title>
    <link rel="stylesheet" href="<?php echo $base_prefix; ?>admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { display: block; background: #0f172a; min-height: 100vh; margin: 0; }
        .sidebar { width: 250px; background: #1e293b; height: 100vh; position: fixed; border-right: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: 2rem; }
        .nav-link { display: block; padding: 1rem; color: #94a3b8; text-decoration: none; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: #334155; color: white; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 0.875rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .stat-card { background: #1e293b; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); }
        .stat-card h3 { margin: 0; color: #94a3b8; font-size: 0.875rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin-top: 0.5rem; }
        .table-container { background: #1e293b; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #334155; text-align: left; padding: 1rem; color: #94a3b8; font-weight: 600; font-size: 0.875rem; }
        td { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-sm { padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.8125rem; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn-danger { background: #ef4444; color: white; border: none; }
        .btn-primary { background: #6366f1; color: white; border: none; }
        .action-btns { display: flex; gap: 0.5rem; }
        .form-card { background: #1e293b; padding: 2rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); max-width: 600px; }
        textarea { width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); background-color: var(--bg-dark); color: white; outline: none; transition: border-color 0.2s; min-height: 100px; font-family: inherit; }
        textarea:focus { border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div style="padding: 2rem; font-size: 1.25rem; font-weight: 700;">Admin Panel</div>
        <a href="<?php echo $base_prefix; ?>index.php" class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
        <a href="<?php echo $base_prefix; ?>kulup/kulupler.php" class="nav-link <?php echo ($active_page == 'kulupler') ? 'active' : ''; ?>">Kulüpler</a>
        <a href="<?php echo $base_prefix; ?>etkinlik/etkinlikler.php" class="nav-link <?php echo ($active_page == 'etkinlikler') ? 'active' : ''; ?>">Etkinlikler</a>
    </div>
    
    <div class="main-content">
        <header>
            <h1><?php echo $header_title ?? 'Dashboard'; ?></h1>
            <a href="?logout=1" class="logout-btn" onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?')">Çıkış Yap</a>
        </header>
