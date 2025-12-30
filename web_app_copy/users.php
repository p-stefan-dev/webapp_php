<?php
// users.php

$pageTitle = 'Management Utilizatori';
require_once 'includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$successMessage = '';
$errorMessage = '';

// --- Logica de procesare a formularelor ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $personal_id = filter_input(INPUT_POST, 'personal_id', FILTER_VALIDATE_INT);

    try {
        // Actiunea de confirmare a contului
        if ($action === 'confirm_account' && $personal_id) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE personal SET rol = 4 WHERE id = ?");
            $stmt->execute([$personal_id]);
            $stmt = $pdo->prepare("UPDATE users SET activation_token = NULL WHERE id_personal = ?");
            $stmt->execute([$personal_id]);
            $pdo->commit();
            $successMessage = 'Contul a fost confirmat cu succes!';
        }

        // Actiunea de schimbare a rolului
        if ($action === 'change_role' && $personal_id) {
            $new_role = filter_input(INPUT_POST, 'new_role', FILTER_VALIDATE_INT);
            if (in_array($new_role, [1, 2, 3, 4, 5])) { // Validam ca rolul este unul permis
                $stmt = $pdo->prepare("UPDATE personal SET rol = ? WHERE id = ?");
                $stmt->execute([$new_role, $personal_id]);
                $successMessage = 'Rolul a fost actualizat cu succes!';
            } else {
                $errorMessage = 'Rol invalid selectat.';
            }
        }

        // Actiunea de retrimitere a email-ului de confirmare
        if ($action === 'resend_confirmation') {
            $email = $_POST['email'] ?? '';
            $token = $_POST['token'] ?? '';
            $prenume = $_POST['prenume'] ?? '';

            if (!empty($email) && !empty($token)) {
                $activationLink = "http://localhost/web_app/activate.php?token=" . $token;
                $subject = 'Activarea contului WebApp Intranet';
                $body = "<p>Bună ziua {$prenume},</p><p>Administratorul v-a retrimis email-ul de activare. Pentru a vă activa contul, vă rugăm să accesați link-ul de mai jos:</p><p><a href='{$activationLink}'>{$activationLink}</a></p>";
                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: <no-reply@intranet.local>\r\n";
                
                if (@mail($email, $subject, $body, $headers)) {
                    $successMessage = 'Email-ul de confirmare a fost retrimis cu succes către ' . htmlspecialchars($email);
                } else {
                    $errorMessage = 'Serverul nu a putut retrimite email-ul. Verificați configurarea XAMPP.';
                }
            } else {
                $errorMessage = 'Date invalide pentru retransmiterea email-ului.';
            }
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorMessage = 'A apărut o eroare la actualizarea datelor.';
    }
}


// --- Preluarea listei de utilizatori ---
$usersList = [];
try {
    $stmt = $pdo->query(
        "SELECT 
            u.id, 
            p.id AS personal_id, 
            p.email, 
            u.activation_token, 
            p.nume, 
            p.prenume, 
            p.rol, 
            g.nume_grad,
            struct.prescurt AS structura_prescurt,
            substruct.prescurt AS substructura_prescurt
         FROM users u
         JOIN personal p ON u.id_personal = p.id
         LEFT JOIN grade g ON p.id_grad = g.id_grad
         LEFT JOIN structuri struct ON p.id_struct = struct.id_struct
         LEFT JOIN substructuri substruct ON p.id_substr = substruct.id_substr
         ORDER BY p.nume, p.prenume"
    );
    $usersList = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea listei de utilizatori.';
}
?>

<h1 class="h2">Management Utilizatori</h1>
<p class="text-muted">Vizualizați și administrați conturile de utilizator.</p>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>


<div class="card content-card mt-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle table-prestige">
            <thead>
                <tr>
                    <th>Nume Complet</th>
                    <th>Structura</th>
                    <th>Rol Curent</th>
                    <th style="width: 350px;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr><td colspan="4" class="text-center">Nu există utilizatori înregistrați.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['nume'] . ' ' . $user['prenume']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $structuraAfisata = htmlspecialchars($user['structura_prescurt']);
                                        if (!empty($user['substructura_prescurt'])) {
                                            $structuraAfisata .= ' / ' . htmlspecialchars($user['substructura_prescurt']);
                                        }
                                        echo $structuraAfisata;
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        switch($user['rol']) {
                                            case 1: echo '<span class="badge bg-danger">Admin</span>'; break;
                                            case 2: echo '<span class="badge bg-info">Supervizor</span>'; break;
                                            case 3: echo '<span class="badge bg-primary">Editor</span>'; break;
                                            case 4: echo '<span class="badge bg-success">Utilizator</span>'; break;
                                            case 5: echo '<span class="badge bg-warning text-dark">Vizitator</span>'; break;
                                            default: echo '<span class="badge bg-secondary">Necunoscut</span>'; break;
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- Formular Schimbare Rol -->
                                        <form action="users.php" method="post" class="me-2">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="personal_id" value="<?php echo $user['personal_id']; ?>">
                                            <div class="input-group">
                                                <select name="new_role" class="form-select form-select-sm">
                                                    <option value="5" <?php if($user['rol'] == 5) echo 'selected'; ?>>Vizitator</option>
                                                    <option value="4" <?php if($user['rol'] == 4) echo 'selected'; ?>>Utilizator</option>
                                                    <option value="3" <?php if($user['rol'] == 3) echo 'selected'; ?>>Editor</option>
                                                    <option value="2" <?php if($user['rol'] == 2) echo 'selected'; ?>>Supervizor</option>
                                                    <option value="1" <?php if($user['rol'] == 1) echo 'selected'; ?>>Admin</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm">Schimbă</button>
                                            </div>
                                        </form>

                                        <!-- Buton Retrimitere Email -->
                                        <form action="users.php" method="post" class="me-2">
                                            <input type="hidden" name="action" value="resend_confirmation">
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($user['activation_token']); ?>">
                                            <input type="hidden" name="prenume" value="<?php echo htmlspecialchars($user['prenume']); ?>">
                                            <button type="submit" class="btn btn-info btn-sm" 
                                                    title="<?php echo ($user['rol'] == 5) ? 'Retrimite email de confirmare' : 'Contul este deja confirmat'; ?>" 
                                                    <?php if ($user['rol'] != 5) echo 'disabled'; ?>>
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </form>

                                        <!-- Buton Confirmare Manuala -->
                                        <form action="users.php" method="post">
                                            <input type="hidden" name="action" value="confirm_account">
                                            <input type="hidden" name="personal_id" value="<?php echo $user['personal_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm" 
                                                    title="<?php echo ($user['rol'] == 5) ? 'Confirmă manual contul' : 'Contul este deja confirmat'; ?>" 
                                                    <?php if ($user['rol'] != 5) echo 'disabled'; ?>>
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/dashboard_footer.php';
?>