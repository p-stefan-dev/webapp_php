<?php
// includes/dashboard_header.php - "Prestige" Design

if (session_status() == PHP_SESSION_NONE) session_start();

// CĂI ABSOLUTE PENTRU PHP (folosind dirname(__DIR__))
// dirname(__DIR__) = folderul 'htdocs'. Adăugăm /includes/auth_check.php
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
// Dacă nu ai definit BASE_URL în config, o definim aici temporar (nerecomandat, mai bine în config)
if (!defined('BASE_URL')) define('BASE_URL', '/nume_proiect/'); 

// Initializare variabile default
$appName = 'WebApp Intranet';
$userRole = 0;
$userFullName = 'Utilizator';

try {
    // 1. Preluam Titlul Aplicatiei
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'dashboard_title'");
    $stmt->execute();
    $dbAppName = $stmt->fetchColumn();
    if ($dbAppName !== false) {
        $appName = $dbAppName;
    }

    // 2. Preluam Datele Utilizatorului Curent
    $currentUserId = $_SESSION['user_id'] ?? 0;

    if ($currentUserId > 0) {
        $stmt = $pdo->prepare("SELECT rol, nume, prenume FROM personal WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $user = $stmt->fetch();

        if ($user) {
            $userRole = $user['rol'];
            $userFullName = $user['nume'] . ' ' . $user['prenume'];
        }
    }

} catch (PDOException $e) {
    error_log("Eroare Header: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($appName) : 'WebApp Intranet'; ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
</head>
<body>

<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <a href="<?php echo BASE_URL; ?>home.php" class="text-white text-decoration-none">
                <i class="fas fa-bolt"></i>
                <span class="ms-2"><?php echo htmlspecialchars($appName); ?></span>
            </a>
        </div>
        <ul class="list-group list-group-flush sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>home.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i> Panou Principal
                </a>
            </li>
            
            <li class="nav-item">
                 <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#managementSubmenu" role="button" aria-expanded="false" aria-controls="managementSubmenu">
                    <span><i class="fas fa-users-cog fa-fw"></i> Management</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="managementSubmenu">
                    <a href="<?php echo BASE_URL; ?>personal/personal.php" class="list-group-item list-group-item-action ps-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'personal.php') ? 'active' : ''; ?>">Personal</a>
                    <a href="<?php echo BASE_URL; ?>tehnica/tehnica.php" class="list-group-item list-group-item-action ps-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'tehnica.php') ? 'active' : ''; ?>">Tehnica</a>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>cru/adauga_cru.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'adauga_cru.php') ? 'active' : ''; ?>">
                    <i class="fas fa-file-medical-alt fa-fw"></i> Rapoarte C.R.U.
                </a>
            </li>

            <?php if (isset($userRole) && $userRole == 1): // Afisam doar pentru admini ?>
            <li class="nav-item">
                 <a href="<?php echo BASE_URL; ?>settings.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog fa-fw"></i> Setări
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark top-navbar">
            <div class="container-fluid">
                <button class="btn btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1 class="h4 mb-0 ms-3 text-white"><?php echo htmlspecialchars($pageTitle ?? ''); ?></h1>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle fa-fw fs-5"></i>
                            <span class="ms-2 d-none d-lg-inline"><?php echo htmlspecialchars($userFullName); ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>my_profile.php">Profil</a>
                            
                            <?php if (isset($userRole) && $userRole == 1): ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>settings.php">Setări Aplicație</a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Deconectare</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="content-area">