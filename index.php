<?php
// index.php -- Pagina de Autentificare (Design V2 - Volt)

require_once 'config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$errorMessage = '';
$successMessage = '';
$warningMessage = ''; // Variabila noua pentru atentionari

if (isset($_SESSION['activation_success'])) {
    $successMessage = $_SESSION['activation_success'];
    unset($_SESSION['activation_success']);
}
if (isset($_SESSION['registration_pending'])) {
    $successMessage = $_SESSION['registration_pending'];
    unset($_SESSION['registration_pending']);
}
// Verificam si mesajul de atentionare
if (isset($_SESSION['registration_success_no_mail'])) {
    $warningMessage = $_SESSION['registration_success_no_mail'];
    unset($_SESSION['registration_success_no_mail']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $errorMessage = 'Adresa de email și parola sunt obligatorii.';
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];
        try {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.id_personal, u.email, u.password, p.activ, p.nume, p.prenume 
                 FROM users u JOIN personal p ON u.id_personal = p.id WHERE u.email = ?"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verificam daca utilizatorul exista si daca parola este corecta folosind password_verify()
            if ($user && password_verify($password, $user['password'])) {
                // Verificam daca contul este activ
                if ($user['activ'] == 1) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_personal_id'] = $user['id_personal'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_full_name'] = $user['nume'] . ' ' . $user['prenume'];
                    header('Location: home.php');
                    exit();
                } else {
                    $errorMessage = 'Contul dumneavoastră este dezactivat. Vă rugăm să contactați un administrator.';
                }
            } else {
                $errorMessage = 'Email sau parolă incorectă.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'A apărut o eroare tehnică.';
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'login_background_image'");
    $stmt->execute();
    $loginBgImage = $stmt->fetchColumn();
} catch (PDOException $e) {
    $loginBgImage = ''; // Fallback in caz de eroare
}

$pageTitle = 'Bun Venit - Autentificare';
// NU includem header-ul standard, deoarece aceasta pagina are un layout unic
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="bg-dark text-white">

<div class="container-fluid login-container">
    <div class="row vh-100">
        <!-- Partea Stanga (Imagine) -->
        <div class="col-lg-7 d-none d-lg-block login-bg" 
             style="<?php 
                        if (!empty($loginBgImage) && file_exists(__DIR__ . '/' . $loginBgImage)) {
                            echo 'background-image: url(\'' . htmlspecialchars($loginBgImage) . '\');';
                        } else {
                            echo 'background-color: #374151;'; // Culoare de fallback
                        }
                    ?>">
            <h1 class="display-4"></h1>
            <p class="lead"></p>
        </div>
        
        <!-- Partea Dreapta (Formular) -->
        <div class="col-lg-5 col-md-12 login-form-container">
            <div class="w-100 p-4" style="max-width: 400px;">
                <div class="text-center mb-4">
                    <h1 class="h3">WebApp Intranet</h1>
                    <p class="text-muted">O soluție integrată pentru management intern.</p>
                </div>
                <h2 class="h4 mb-4 text-center">Autentificare</h2>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>
                <?php if (!empty($warningMessage)): ?>
                    <div class="alert alert-warning"><?php echo htmlspecialchars($warningMessage); ?></div>
                <?php endif; ?>

                <form action="index.php" method="post">
                    <div class="form-floating mb-3">
                        <input class="form-control" id="email" name="email" type="email" placeholder="adresa@email.com" required />
                        <label for="email">Adresă de email</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input class="form-control" id="password" name="password" type="password" placeholder="Parolă" required />
                        <label for="password">Parolă</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" id="showPassword" type="checkbox" />
                        <label class="form-check-label" for="showPassword">Afișează parola</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-block">Intră în cont</button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <a href="login_dev.php" class="text-white-50 small">Ați uitat parola?</a>
                    <span class="text-white-50 mx-2">|</span>
                    <a href="register.php" class="text-white-50 small">Creează un cont</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// Scriptul pentru afisarea parolei ramane valabil
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('showPassword');
    if (passwordInput && showPasswordCheckbox) {
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    }
});
</script>
</body>
</html>