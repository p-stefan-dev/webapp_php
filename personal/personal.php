<?php
// personal.php

$pageTitle = 'Management Personal';
require_once '../includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once '../includes/dashboard_footer.php';
    exit();
}

// --- 1. CONFIGURĂRI PAGINARE ȘI CĂUTARE ---
$limit = 10; // Maxim 10 persoane pe pagină
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? ''); // Termenul de căutare

// --- 2. LOGICA DE SORTARE (Existentă + Adaptată) ---
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

if (!array_key_exists($sort_column, $allowed_sort_columns)) {
    $sort_column = 'nume';
}
if (!in_array(strtolower($sort_order), ['asc', 'desc'])) {
    $sort_order = 'asc';
}

$order_by_clause = "ORDER BY " . $allowed_sort_columns[$sort_column] . " " . strtoupper($sort_order);

// --- 3. CONSTRUIREA INTEROGĂRII SQL ---
$personalList = [];
$total_pages = 1;
$total_records = 0;

try {
try {
    // A. Construim clauza WHERE pentru căutare
    $whereSQL = "";
    $params = [];
    
    if (!empty($search)) {
        // CORECTAT: Folosim :search1 și :search2 pentru a evita eroarea HY093
        $whereSQL = "WHERE (p.nume LIKE :search1 OR p.prenume LIKE :search2)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
    }

    // B. Numărăm totalul de înregistrări (pentru paginare)
    $countSql = "SELECT COUNT(*) FROM personal p $whereSQL";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $total_records = $stmtCount->fetchColumn();
    
    // Calculăm totalul paginilor (evităm împărțirea la 0)
    $total_pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;

    // C. Extragem datele efective (cu LIMIT și OFFSET)
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
         $whereSQL
         $order_by_clause
         LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    
    // Bind parametri existenți (search1, search2)
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    // Bind LIMIT și OFFSET (trebuie să fie int explicit)
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $personalList = $stmt->fetchAll();

} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea listei de personal: ' . $e->getMessage();
}

} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea listei de personal: ' . $e->getMessage();
}

// --- Helper pentru link-uri de sortare care păstrează și căutarea ---
function sort_link($column, $text, $current_sort, $current_order, $search_term) {
    $order = ($current_sort == $column && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($current_sort == $column) {
        $icon = ($current_order == 'asc') ? '<i class="fas fa-sort-up ms-1"></i>' : '<i class="fas fa-sort-down ms-1"></i>';
    } else {
        $icon = '<i class="fas fa-sort ms-1 text-muted"></i>';
    }
    // Păstrăm și parametrul de căutare în URL
    $search_param = !empty($search_term) ? '&search=' . urlencode($search_term) : '';
    return "<a href=\"?sort=$column&order=$order$search_param\" class=\"text-white text-decoration-none\">$text $icon</a>";
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h2">Management Personal</h1>
        <p class="text-muted">Total personal găsit: <strong><?php echo $total_records; ?></strong></p>
    </div>
    <div>
        <a href="personal/add_personal.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Adaugă
        </a>
        <a href="personal/bulk_edit.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Editare
        </a>
        <a href="personal/import_personal.php" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Import
        </a>
        <a href="personal/export_personal.php" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Export
        </a>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form action="personal/personal.php" method="GET" id="searchForm" class="row g-3 align-items-center">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
            <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">

            <div class="col-auto">
                <label for="searchInput" class="col-form-label fw-bold">Caută Personal:</label>
            </div>
            <div class="col-auto flex-grow-1">
                <input type="text" id="searchInput" name="search" class="form-control" 
                       placeholder="Scrie minim 3 litere din nume..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            </div>
            <div class="col-auto">
                <?php if(!empty($search)): ?>
                    <a href="personal/personal.php" class="btn btn-secondary">Resetează</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Caută</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle table-prestige mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?php echo sort_link('nume', 'Nume Complet', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('grad', 'Grad', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('structura', 'Structura', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('localitate', 'Locație', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('tip_serviciu', 'Tip', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('rol', 'Rol', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('stare', 'Stare', $sort_column, $sort_order, $search); ?></th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personalList)): ?>
                    <tr><td colspan="8" class="text-center py-4">Nu am găsit rezultate conform criteriilor.</td></tr>
                <?php else: ?>
                    <?php foreach ($personalList as $person): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($person['nume'] . ' ' . $person['prenume']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($person['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($person['nume_grad'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                    $struct = htmlspecialchars($person['structura_prescurt'] ?? '');
                                    if (!empty($person['substructura_prescurt'])) {
                                        $struct .= ' / ' . htmlspecialchars($person['substructura_prescurt']);
                                    }
                                    echo $struct ?: '-';
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $loc = htmlspecialchars($person['uat'] ?? '');
                                    if (!empty($person['localitate'])) {
                                        $loc .= ' / ' . htmlspecialchars($person['localitate']);
                                    }
                                    echo $loc ?: '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($person['tip_serviciu'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                    switch($person['rol']) {
                                        case 1: echo '<span class="badge bg-danger">Admin</span>'; break;
                                        case 4: echo '<span class="badge bg-success">User</span>'; break;
                                        default: echo '<span class="badge bg-secondary">Other</span>'; break;
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
                                <a href="personal/edit_personal.php?id=<?php echo $person['id']; ?>&return_url=personal/personal.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>">Anterior</a>
        </li>

        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>">Următor</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    let timeout = null;

    searchInput.addEventListener('input', function() {
        const val = this.value.trim();

        // Anulăm timer-ul anterior dacă utilizatorul tastează rapid
        clearTimeout(timeout);

        // Setăm un nou timer de 800ms (debounce)
        timeout = setTimeout(function() {
            // Regula: Caută dacă sunt minim 3 caractere SAU dacă s-a șters tot (reset)
            if (val.length >= 3 || val.length === 0) {
                searchForm.submit();
            }
        }, 800); 
    });
    
    // Focus pe input după reload ca să nu piardă utilizatorul cursorul
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('search')) {
        const len = searchInput.value.length;
        searchInput.focus();
        searchInput.setSelectionRange(len, len);
    }
});
</script>

<?php
require_once '../includes/dashboard_footer.php';
?>