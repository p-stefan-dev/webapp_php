<?php
// home.php
session_start();

// 1. VERIFICARE SECURITATE (Paznicul)
// Daca utilizatorul nu e logat, il trimitem la pagina de login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Sau login_dev.php
    exit;
}

require_once 'config/db.php';

// 2. PRELUARE DATE STATISTICE
try {
    // Tabelul 'users' nu mai exista.
    // Vom afisa: Total Personal vs Personal Activ
    
    // Numar total angajati
    $totalPersonal = $pdo->query("SELECT count(*) FROM personal")->fetchColumn();
    
    // Numar angajati ACTIVI (unde activ = 1)
    $totalActivi = $pdo->query("SELECT count(*) FROM personal WHERE activ = 1")->fetchColumn();

} catch (PDOException $e) {
    // În caz de eroare, afișam 'N/A'
    $totalActivi = $totalPersonal = 'N/A';
}

// 3. DETERMINARE NUME AFISAT
// Asigura compatibilitatea intre register.php si login_dev.php
$numeAfisat = $_SESSION['user_full_name'] ?? $_SESSION['nume'] ?? 'Utilizator';

$pageTitle = 'Panou Principal';
require_once 'includes/dashboard_header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h1 class="h2">Bun venit, <?php echo htmlspecialchars($numeAfisat); ?>!</h1>
        <p class="text-muted">Acesta este panoul principal al aplicației.</p>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="stat-title">Personal Activ</h6>
                    <span class="stat-value"><?php echo htmlspecialchars($totalActivi); ?></span>
                </div>
                <div class="col-auto">
                    <i class="fas fa-user-check stat-icon"></i> 
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="stat-title">Total Personal</h6>
                    <span class="stat-value"><?php echo htmlspecialchars($totalPersonal); ?></span>
                </div>
                <div class="col-auto">
                    <i class="fas fa-id-card-alt stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    </div>

<?php
require_once 'includes/dashboard_footer.php';
?>