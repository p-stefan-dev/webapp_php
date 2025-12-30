<?php
// includes/dashboard_header.php - "Prestige" Design

if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Initializare variabile default pentru a preveni erori "Undefined variable"
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
    // FIX: Folosim $_SESSION['user_id'] direct, deoarece tabela users nu mai exista
    // ID-ul din sesiune este direct ID-ul din tabela personal.
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
    // In caz de eroare DB, ramanem cu valorile default, nu oprim executia cu die()
    error_log("Eroare Header: " + $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($appName) : 'WebApp Intranet'; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>

<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <a href="home.php" class="text-white text-decoration-none">
                <i class="fas fa-bolt"></i>
                <span class="ms-2"><?php echo htmlspecialchars($appName); ?></span>
            </a>
        </div>
        <ul class="list-group list-group-flush sidebar-nav">
            <li class="nav-item">
                <a href="home.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i> Panou Principal
                </a>
            </li>
            
            <li class="nav-item">
                 <a class="list-group-item d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#managementSubmenu" role="button" aria-expanded="false" aria-controls="managementSubmenu">
                    <span><i class="fas fa-users-cog fa-fw"></i> Management</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="managementSubmenu">
                    <a href="personal.php" class="list-group-item list-group-item-action ps-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'personal.php') ? 'active' : ''; ?>">Personal</a>
                    <a href="tehnica.php" class="list-group-item list-group-item-action ps-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'tehnica.php') ? 'active' : ''; ?>">Tehnica</a>
                </div>
            </li>

            <?php if (isset($userRole) && $userRole == 1): // Afisam doar pentru admini ?>
            <li class="nav-item">
                 <a href="settings.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
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
                            <a class="dropdown-item" href="my_profile.php">Profil</a>
                            
                            <?php if (isset($userRole) && $userRole == 1): ?>
                            <a class="dropdown-item" href="settings.php">Setări Aplicație</a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">Deconectare</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="content-area">