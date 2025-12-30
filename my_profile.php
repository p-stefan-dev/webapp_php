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
<div class="card content-card mt-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Nume Complet:</strong> <?php echo htmlspecialchars($person['nume'] . ' ' . $person['prenume']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($person['email']); ?></p>
                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($person['telefon'] ? $person['telefon'] : 'N/A'); ?></p>
                <p><strong>Grad:</strong> <?php echo htmlspecialchars($person['nume_grad']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Structura:</strong> <?php echo htmlspecialchars($person['structura']); ?></p>
                <p><strong>Substructura:</strong> <?php echo htmlspecialchars($person['substructura'] ? $person['substructura'] : 'N/A'); ?></p>
                <p><strong>Județ:</strong> <?php echo htmlspecialchars($person['judet'] ? $person['judet'] : 'N/A'); ?></p>
                <p><strong>UAT / Localitate:</strong> <?php echo htmlspecialchars(($person['uat'] ? $person['uat'] : '') . ($person['localitate'] ? ' / ' . $person['localitate'] : '')); ?></p>
                <p><strong>Tip Serviciu:</strong> <?php echo htmlspecialchars($person['tip_serviciu'] ? $person['tip_serviciu'] : 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require_once 'includes/dashboard_footer.php';
?>
