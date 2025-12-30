<?php
// register.php - Configurare Profil (Fără Localitate Domiciliu)

session_start();
require_once 'config/db.php';

$errorMessage = '';
$successMessage = '';

// A. Preluare liste inițiale (Grade și Structuri)
try {
    $grade_list  = $pdo->query("SELECT id_grad, nume_grad FROM grade ORDER BY nume_grad ASC")->fetchAll();
    $struct_list = $pdo->query("SELECT id_struct, Structura FROM structuri ORDER BY Structura ASC")->fetchAll();
    
    // Preluare imagine fundal
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'login_background_image'");
    $stmt->execute();
    $loginBgImage = $stmt->fetchColumn();

} catch (PDOException $e) {
    $errorMessage = "Eroare sistem: " . $e->getMessage();
}

// B. Simulare Date LDAP (Mapare)
$ldapMap = ['nume' => 'sn', 'prenume' => 'givenname', 'email' => 'mail', 'username' => 'samaccountname'];

// Date simulate
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
    
    // Date Text
    $post_nume     = trim($_POST['nume']);
    $post_prenume  = trim($_POST['prenume']);
    $post_email    = trim($_POST['email']);
    $post_username = trim($_POST['username']);
    $post_telefon  = trim($_POST['telefon']);

    // ID-uri din Select-uri
    $post_id_grad   = filter_input(INPUT_POST, 'id_grad', FILTER_VALIDATE_INT);
    $post_id_struct = filter_input(INPUT_POST, 'id_struct', FILTER_VALIDATE_INT);
    $post_id_substr = filter_input(INPUT_POST, 'id_substr', FILTER_VALIDATE_INT);
    
    // Validare
    if (empty($post_id_grad) || empty($post_id_struct)) {
        $errorMessage = 'Vă rugăm să selectați Gradul și Structura!';
    } else {
        try {
            // Inserare fără datele de localitate
            $sql = "INSERT INTO personal (
                        nume, prenume, email, username, telefon,
                        id_grad, id_struct, id_substr, 
                        clasa, activ, rol, data_add
                    ) VALUES (
                        :nume, :prenume, :email, :username, :telefon,
                        :id_grad, :id_struct, :id_substr,
                        '1', 1, 4, NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nume'      => $post_nume, 
                ':prenume'   => $post_prenume, 
                ':email'     => $post_email,
                ':username'  => $post_username, 
                ':telefon'   => $post_telefon,
                ':id_grad'   => $post_id_grad, 
                ':id_struct' => $post_id_struct,
                ':id_substr' => ($post_id_substr ? $post_id_substr : 0) // Dacă e null, punem 0
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
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .form-control:read-only {
            background-color: #2d3748;
            color: #a0aec0;
            border-color: #4a5568;
            cursor: not-allowed;
        }
        .login-form-container .w-100 { max-width: 600px !important; }
        .form-label-custom {
            color: #d1d5db;
            font-size: 0.85rem;
            margin-bottom: 0.2rem;
            margin-left: 0.2rem;
        }
    </style>
</head>
<body class="bg-dark text-white">

<div class="container-fluid login-container">
    <div class="row vh-100">
        
        <div class="col-lg-6 d-none d-lg-block login-bg" 
             style="<?php 
                 if (!empty($loginBgImage) && file_exists(__DIR__ . '/' . $loginBgImage)) {
                     echo 'background-image: url(\'' . htmlspecialchars($loginBgImage) . '\');';
                 } else {
                     echo 'background-color: #374151;'; 
                 }
             ?>">
        </div>
        
        <div class="col-lg-6 col-md-12 login-form-container overflow-auto">
            <div class="w-100 p-4" style="max-width: 600px;">
                <div class="text-center mb-4">
                    <h1 class="h3">Configurare Profil</h1>
                    <p class="text-muted">Completează detaliile pentru a finaliza înregistrarea.</p>
                </div>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    
                    <h6 class="text-uppercase text-white-50 border-bottom border-secondary pb-2 mb-3 small">1. Identitate (LDAP)</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($f_username); ?>" readonly>
                                <label for="username" class="text-dark">Username</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($f_email); ?>" readonly>
                                <label for="email" class="text-dark">Email</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($f_nume); ?>" readonly>
                                <label for="nume" class="text-dark">Nume</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo htmlspecialchars($f_prenume); ?>" readonly>
                                <label for="prenume" class="text-dark">Prenume</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-white-50 border-bottom border-secondary pb-2 mb-3 mt-4 small">2. Detalii Serviciu</h6>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">Grad Profesional</label>
                            <select class="form-select text-dark" id="id_grad" name="id_grad" required>
                                <option value="" selected disabled>-- Alege Grad --</option>
                                <?php foreach ($grade_list as $grad): ?>
                                    <option value="<?php echo $grad['id_grad']; ?>"><?php echo htmlspecialchars($grad['nume_grad']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Număr Telefon</label>
                            <input type="text" class="form-control text-dark bg-white" id="telefon" name="telefon" placeholder="07xx xxx xxx" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Structura</label>
                        <select class="form-select text-dark" id="id_struct" name="id_struct" required>
                            <option value="" selected disabled>-- Alege Structura --</option>
                            <?php foreach ($struct_list as $struct): ?>
                                <option value="<?php echo $struct['id_struct']; ?>"><?php echo htmlspecialchars($struct['Structura']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Substructura</label>
                        <select class="form-select text-dark" id="id_substr" name="id_substr" disabled>
                            <option value="">Selectați întâi structura...</option>
                        </select>
                        <div class="form-text text-white-50" id="substr_status"></div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg btn-block">Salvează Profilul</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <span class="text-white-50 small">Ai deja cont?</span>
                    <a href="index.php" class="text-white small ms-2 fw-bold">Autentificare</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // LOGICĂ STRUCTURĂ -> SUBSTRUCTURĂ
    const structSelect = document.getElementById('id_struct');
    const substrSelect = document.getElementById('id_substr');
    const substrStatus = document.getElementById('substr_status');

    structSelect.addEventListener('change', function() {
        const idStruct = this.value;
        
        // Resetare UI
        substrSelect.innerHTML = '<option value="">Se încarcă...</option>';
        substrSelect.disabled = true;
        substrStatus.textContent = "Se caută substructuri...";

        if (idStruct) {
            // Verifică calea! Dacă ești în root, calea este api/get_substructuri.php
            fetch('api/get_substructuri.php?id_struct=' + idStruct) 
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP Error " + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    substrSelect.innerHTML = '<option value="0">Fără substructură</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(item => {
                            // API-ul returnează 'id_substr' și 'denumire'
                            substrSelect.add(new Option(item.denumire, item.id_substr));
                        });
                        substrStatus.textContent = "";
                    } else {
                        substrStatus.textContent = "Această structură nu are substructuri.";
                    }
                    substrSelect.disabled = false;
                })
                .catch(err => {
                    console.error("Eroare JS:", err);
                    substrSelect.innerHTML = '<option value="">Eroare încărcare</option>';
                    substrStatus.textContent = "Eroare la conectarea cu serverul (verificați consola).";
                });
        } else {
            substrSelect.innerHTML = '<option value="">Selectați întâi structura...</option>';
            substrStatus.textContent = "";
        }
    });
});
</script>
</body>
</html>