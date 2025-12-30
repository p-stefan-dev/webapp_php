<?php
// personal.php

$pageTitle = 'Management Personal';
require_once 'includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

// --- Logica de Sortare ---
$allowed_sort_columns = [
    'nume' => 'p.nume',
    'grad' => 'g.nume_grad',
    'structura' => 'structura_prescurt',
    'localitate' => 'p.uat',
    'tip_serviciu' => 'p.tip_serviciu',
    'rol' => 'p.rol',
    'stare' => 'p.activ'
];

$sort_column = $_GET['sort'] ?? 'nume';
$sort_order = $_GET['order'] ?? 'asc';

// Validare
if (!array_key_exists($sort_column, $allowed_sort_columns)) {
    $sort_column = 'nume';
}
if (!in_array(strtolower($sort_order), ['asc', 'desc'])) {
    $sort_order = 'asc';
}

$order_by_clause = "ORDER BY " . $allowed_sort_columns[$sort_column] . " " . strtoupper($sort_order);


// --- Preluarea listei de personal ---
$personalList = [];
try {
    $sql = "SELECT 
            p.id, p.nume, p.prenume, p.email, p.telefon, p.rol, p.activ,
            p.judet, p.uat, p.localitate, p.tip_serviciu,
            g.nume_grad,
            struct.prescurt AS structura_prescurt,
            substruct.prescurt AS substructura_prescurt
         FROM personal p
         LEFT JOIN grade g ON p.id_grad = g.id_grad
         LEFT JOIN structuri struct ON p.id_struct = struct.id_struct
         LEFT JOIN substructuri substruct ON p.id_substr = substruct.id_substr
         $order_by_clause";

    $stmt = $pdo->query($sql);
    $personalList = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea listei de personal.';
}
?>

<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h2">Management Personal</h1>
        <p class="text-muted">Vizualizați și administrați tot personalul din baza de date.</p>
    </div>
    <div>
                <a href="import_personal.php" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Import Excel
        </a>
        <a href="export_personal.php" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Export Excel
        </a>

    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>


<?php
// Functie ajutatoare pentru a genera link-urile de sortare
function sort_link($column, $text, $current_sort, $current_order) {
    $order = ($current_sort == $column && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($current_sort == $column) {
        $icon = ($current_order == 'asc') ? '<i class="fas fa-sort-up ms-1"></i>' : '<i class="fas fa-sort-down ms-1"></i>';
    } else {
        $icon = '<i class="fas fa-sort ms-1 text-muted"></i>';
    }
    return "<a href=\"?sort=$column&order=$order\" class=\"text-white text-decoration-none\">$text $icon</a>";
}
?>

<div class="card content-card mt-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle table-prestige">
            <thead>
                <tr>
                    <th><?php echo sort_link('nume', 'Nume Complet', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('grad', 'Grad', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('structura', 'Structura', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('localitate', 'UAT / Localitate', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('tip_serviciu', 'Tip Serviciu', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('rol', 'Rol', $sort_column, $sort_order); ?></th>
                    <th><?php echo sort_link('stare', 'Stare', $sort_column, $sort_order); ?></th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personalList)): ?>
                    <tr><td colspan="8" class="text-center">Nu există personal înregistrat.</td></tr>
                <?php else: ?>
                    <?php foreach ($personalList as $person): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($person['nume'] . ' ' . $person['prenume']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($person['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($person['nume_grad']); ?></td>
                            <td>
                                <?php 
                                    $structuraAfisata = htmlspecialchars($person['structura_prescurt']);
                                    if (!empty($person['substructura_prescurt'])) {
                                        $structuraAfisata .= ' / ' . htmlspecialchars($person['substructura_prescurt']);
                                    }
                                    echo $structuraAfisata;
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $locatieAfisata = htmlspecialchars($person['uat']);
                                    if (!empty($person['localitate'])) {
                                        $locatieAfisata .= ' / ' . htmlspecialchars($person['localitate']);
                                    }
                                    echo $locatieAfisata;
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($person['tip_serviciu']); ?></td>
                            <td>
                                <?php 
                                    switch($person['rol']) {
                                        case 1: echo '<span class="badge bg-danger">Admin</span>'; break;
                                        case 2: echo '<span class="badge bg-info">Supervizor</span>'; break;
                                        case 3: echo '<span class="badge bg-primary">Editor</span>'; break;
                                        case 4: echo '<span class="badge bg-success">Utilizator</span>'; break;
                                        case 5: echo '<span class="badge bg-warning text-dark">Vizitator</span>'; break;
                                        default: echo '<span class="badge bg-secondary">N/A</span>'; break;
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if ($person['activ'] == 1): ?>
                                    <span class="badge bg-success">Activ</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_personal.php?id=<?php echo $person['id']; ?>&return_url=personal.php" class="btn btn-sm btn-primary" title="Editează"><i class="fas fa-edit"></i></a>
                                <!-- Aici se pot adauga butoane de activare/dezactivare -->
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
