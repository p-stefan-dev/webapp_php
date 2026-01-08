<?php
// my_profile.php

$pageTitle = 'Profilul Meu';
require_once 'includes/dashboard_header.php';

$person_id = $_SESSION['user_id'];

// --- Preluarea datelor personale ---
try {
    $sql = "SELECT 
            p.id, p.nume, p.prenume, p.email, p.telefon, p.rol, p.activ,
            p.judet, p.uat, p.localitate, p.tip_serviciu,
            g.nume_grad,
            struct.Structura AS structura,
            substruct.denumire AS substructura
         FROM personal p
         LEFT JOIN grade g ON p.id_grad = g.id_grad
         LEFT JOIN structuri struct ON p.id_struct = struct.id_struct
         LEFT JOIN substructuri substruct ON p.id_substr = substruct.id_substr
         WHERE p.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$person_id]);
    $person = $stmt->fetch();

    if (!$person) {
        echo '<div class="alert alert-danger">Profilul nu a fost găsit.</div>';
        require_once 'includes/dashboard_footer.php';
        exit();
    }

} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea datelor profilului.';
}

?>

<h1 class="h2">Profilul Meu</h1>
<p class="text-muted">Aici puteți vizualiza datele dumneavoastră personale.</p>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<?php if ($person): ?>
<div class="card content-card mt-4 shadow-sm">
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-6">
                <h5 class="text-primary border-bottom pb-2 mb-3">Date Generale</h5>
                
                <p class="mb-2"><strong>Nume Complet:</strong> 
                    <span class="fs-5 ms-2"><?php echo htmlspecialchars($person['nume'] . ' ' . $person['prenume']); ?></span>
                </p>
                
                <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($person['email']); ?></p>
                
                <p class="mb-2"><strong>Telefon:</strong> <?php echo htmlspecialchars($person['telefon'] ? $person['telefon'] : 'N/A'); ?></p>
                
                <p class="mb-2"><strong>Grad:</strong> <?php echo htmlspecialchars($person['nume_grad']); ?></p>

                <p class="mb-2"><strong>Rol Aplicație:</strong> 
                    <?php 
                        switch ($person['rol']) {
                            case 1: 
                                echo '<span class="badge bg-danger ms-1">Administrator</span>'; 
                                break;
                            case 2: 
                                echo '<span class="badge bg-warning text-dark ms-1">Admin Structură</span>'; 
                                break;
                            case 3: 
                                echo '<span class="badge bg-primary ms-1">Utilizator</span>'; 
                                break;
                            case 4: 
                                echo '<span class="badge bg-secondary ms-1">Vizitator</span>'; 
                                break;
                            default: 
                                echo '<span class="badge bg-secondary ms-1">Necunoscut</span>';
                        }
                    ?>
                </p>
            </div>

            <div class="col-md-6">
                <h5 class="text-primary border-bottom pb-2 mb-3">Detalii Încadrare</h5>
                
                <p class="mb-2"><strong>Structura:</strong> <?php echo htmlspecialchars($person['structura']); ?></p>
                
                <p class="mb-2"><strong>Substructura:</strong> <?php echo htmlspecialchars($person['substructura'] ? $person['substructura'] : 'N/A'); ?></p>
                
                <p class="mb-2"><strong>Județ:</strong> <?php echo htmlspecialchars($person['judet'] ? $person['judet'] : 'N/A'); ?></p>
                
                <p class="mb-2"><strong>UAT / Localitate:</strong> 
                    <?php echo htmlspecialchars(($person['uat'] ? $person['uat'] : '') . ($person['localitate'] ? ' / ' . $person['localitate'] : '')); ?>
                </p>
                
                <p class="mb-2"><strong>Tip Serviciu:</strong> <?php echo htmlspecialchars($person['tip_serviciu'] ? $person['tip_serviciu'] : 'N/A'); ?></p>
                
                <p class="mb-2"><strong>Stare Cont:</strong> 
                    <?php if($person['activ'] == 1): ?>
                        <span class="badge bg-success">Activ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactiv</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require_once 'includes/dashboard_footer.php';
?>