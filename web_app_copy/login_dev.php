<?php
// login_dev.php
session_start();
require_once 'config/db.php';

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);

    if (empty($username)) {
        $msg = '<div class="alert alert-danger">Introdu un username!</div>';
    } else {
        // Verificăm userul în DB
        $stmt = $pdo->prepare("SELECT id, nume, prenume, rol, email FROM personal WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Setare sesiune
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_full_name'] = $user['nume'] . ' ' . $user['prenume'];
            $_SESSION['nume']           = $user['nume'] . ' ' . $user['prenume'];
            $_SESSION['username']       = $username;
            $_SESSION['rol']            = $user['rol'];
            
            header("Location: home.php");
            exit;
        } else {
            $msg = '<div class="alert alert-warning">Username inexistent! <a href="register.php">Înregistrează-te</a></div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Dev Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center vh-100">
    <div class="card" style="width: 350px;">
        <div class="card-header bg-danger text-white text-center fw-bold">DEV LOGIN (NO PASS)</div>
        <div class="card-body">
            <?php if (!empty($msg)) echo $msg; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Username (ex: mpopescu)</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <button type="submit" class="btn btn-danger w-100">Intră</button>
            </form>
            <div class="text-center mt-2">
                <a href="register.php" class="small">Nu ai cont?</a>
            </div>
        </div>
    </div>
</body>
</html>