<?php
// register.php - Pagina de Configurare Profil (Design V2 - Volt Style)

session_start();
require_once 'config/db.php';

// ==================================================================
// 1. LOGICĂ PHP (LDAP, LISTE, SAVE)
// ==================================================================

$errorMessage = '';
$successMessage = '';

// A. Preluare liste (Grade și Structuri)
try {
    $grade_list  = $pdo->query("SELECT id_grad, nume_grad FROM grade ORDER BY nume_grad ASC")->fetchAll();
    $struct_list = $pdo->query("SELECT id_struct, Structura FROM structuri ORDER BY Structura ASC")->fetchAll();
    
    // Preluare imagine fundal (pentru consistență cu index.php)
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'login_background_image'");
    $stmt->execute();
    $loginBgImage = $stmt->fetchColumn();

} catch (PDOException $e) {
    $errorMessage = "Eroare sistem: " . $e->getMessage();
}

// B. Simulare Date LDAP (Mapare)
$ldapMap = ['nume' => 'sn', 'prenume' => 'givenname', 'email' => 'mail', 'username' => 'samaccountname'];

// Date simulate (Aici ar veni datele reale din AD)
$ldapRawData = [
    'sn'             => ['Popescu'],
    'givenname'      => ['Mihai'],
    'mail'           => ['mihai.popescu@exemplu.ro'],
    'samaccountname' => ['mpopescu']
];

$f_nume     = $ldapRawData[$ldapMap['nume']][0]     ?? '';
$f_prenume  = $ldapRawData[$ldapMap['prenume']][0]  ?? '';
$f_email    = $ldapRawData[$ldapMap['email']][0]    ?? '';
$f_username = $ldapRawData[$ldapMap['username']][0] ?? '';

// C. Procesare Formular
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $post_nume     = trim($_POST['nume']);
    $post_prenume  = trim($_POST['prenume']);
    $post_email    = trim($_POST['email']);
    $post_username = trim($_POST['username']);
    $post_id_grad   = $_POST['id_grad'] ?? null;
    $post_id_struct = $_POST['id_struct'] ?? null;
    
    if (empty($post_id_grad) || empty($post_id_struct)) {
        $errorMessage = 'Vă rugăm să selectați Gradul și Structura!';
    } else {
        try {
            // Inserare
            $sql = "INSERT INTO personal (nume, prenume, email, username, id_grad, id_struct, clasa, activ, rol) 
                    VALUES (:nume, :prenume, :email, :username, :id_grad, :id_struct, '1', 1, 4)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nume' => $post_nume, ':prenume' => $post_prenume, ':email' => $post_email,
                ':username' => $post_username, ':id_grad' => $post_id_grad, ':id_struct' => $post_id_struct
            ]);

            // Auto-Login & Redirect
            $new_user_id = $pdo->lastInsertId();
            $_SESSION['user_id']        = $new_user_id;
            $_SESSION['user_full_name'] = $post_nume . ' ' . $post_prenume;
            $_SESSION['nume']           = $post_nume . ' ' . $post_prenume;
            $_SESSION['username']       = $post_username;
            $_SESSION['rol']            = 4; 

            header("Location: home.php");
            exit();

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errorMessage = 'Acest utilizator este deja înregistrat.';
            } else {
                $errorMessage = 'Eroare SQL: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Finalizare Profil';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/login.css"> <style>
        /* Stiluri specifice doar pentru register ca să arate bine input-urile readonly pe fundal dark */
        .form-control:read-only {
            background-color: #2d3748; /* Un gri închis */
            color: #a0aec0; /* Text gri deschis */
            border-color: #4a5568;
            cursor: not-allowed;
        }
        /* Ajustare lățime container form pentru a acomoda mai multe câmpuri */
        .login-form-container .w-100 { max-width: 500px !important; }
    </style>
</head>
<body class="bg-dark text-white">

<div class="container-fluid login-container">
    <div class="row vh-100">
        
        <div class="col-lg-7 d-none d-lg-block login-bg" 
             style="<?php 
                 if (!empty($loginBgImage) && file_exists(__DIR__ . '/' . $loginBgImage)) {
                     echo 'background-image: url(\'' . htmlspecialchars($loginBgImage) . '\');';
                 } else {
                     echo 'background-color: #374151;'; 
                 }
             ?>">
        </div>
        
        <div class="col-lg-5 col-md-12 login-form-container overflow-auto"> <div class="w-100 p-4" style="max-width: 500px;"> <div class="text-center mb-4">
                    <h1 class="h3">Configurare Profil</h1>
                    <p class="text-muted">Date preluate din Active Directory</p>
                </div>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($f_username); ?>" readonly placeholder="User">
                                <label for="username" class="text-dark">Username</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($f_email); ?>" readonly placeholder="Email">
                                <label for="email" class="text-dark">Email</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($f_nume); ?>" readonly placeholder="Nume">
                                <label for="nume" class="text-dark">Nume</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo htmlspecialchars($f_prenume); ?>" readonly placeholder="Prenume">
                                <label for="prenume" class="text-dark">Prenume</label>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-4">
                    
                    <p class="text-white-50 mb-3 small text-uppercase fw-bold">Selectați Încadrarea:</p>

                    <div class="form-floating mb-3">
                        <select class="form-select text-dark" id="id_grad" name="id_grad" required>
                            <option value="" selected disabled>Selectați Gradul...</option>
                            <?php foreach ($grade_list as $grad): ?>
                                <option value="<?php echo $grad['id_grad']; ?>">
                                    <?php echo htmlspecialchars($grad['nume_grad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="id_grad" class="text-dark">Grad Profesional</label>
                    </div>

                    <div class="form-floating mb-4">
                        <select class="form-select text-dark" id="id_struct" name="id_struct" required>
                            <option value="" selected disabled>Selectați Structura...</option>
                            <?php foreach ($struct_list as $struct): ?>
                                <option value="<?php echo $struct['id_struct']; ?>">
                                    <?php echo htmlspecialchars($struct['Structura']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="id_struct" class="text-dark">Structura</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg btn-block">Salvează și Intră</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <span class="text-white-50 small">Ai deja cont configurat?</span>
                    <a href="index.php" class="text-white small ms-2 fw-bold">Autentifică-te aici</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>