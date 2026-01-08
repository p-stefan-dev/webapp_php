<?php
// personal/personal.php

$pageTitle = 'Management Personal';
require_once '../includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once '../includes/dashboard_footer.php';
    exit();
}

// --- DEFINIRE ROLURI ---
$roluri_disponibile = [
    1 => 'Admin',
    2 => 'Struct_Admin',
    3 => 'User',
    4 => 'Vizitator'
];

// --- 1. CONFIGURĂRI PAGINARE ȘI CĂUTARE ---
$limit = 10; 
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');

// --- 2. LOGICA DE SORTARE ---
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

// --- 3. SQL ---
$personalList = [];
$total_pages = 1;
$total_records = 0;

try {
    $whereSQL = "";
    $params = [];
    
    if (!empty($search)) {
        $whereSQL = "WHERE (p.nume LIKE :search1 OR p.prenume LIKE :search2)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
    }

    $countSql = "SELECT COUNT(*) FROM personal p $whereSQL";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $total_records = $stmtCount->fetchColumn();
    
    $total_pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;

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
    foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $personalList = $stmt->fetchAll();

} catch (PDOException $e) {
    $errorMessage = 'Eroare DB: ' . $e->getMessage();
}

function sort_link($column, $text, $current_sort, $current_order, $search_term) {
    $order = ($current_sort == $column && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = ($current_sort == $column) ? (($current_order == 'asc') ? '▲' : '▼') : '';
    $search_param = !empty($search_term) ? '&search=' . urlencode($search_term) : '';
    return "<a href=\"?sort=$column&order=$order$search_param\" class=\"text-white text-decoration-none\">$text $icon</a>";
}
?>

<style>
    .table { font-size: 1.1rem !important; }
    .table td, .table th { padding: 12px 15px !important; vertical-align: middle; }
    .btn, .form-control, .form-select { font-size: 1.05rem !important; }
    .badge { font-size: 0.95rem !important; padding: 6px 10px !important; }
    small, .text-muted { font-size: 0.95rem !important; }
    h1.h2 { font-size: 2rem !important; }

    /* SWITCH CUSTOM VERDE (Stilul cerut) */
    .form-check-input.custom-switch {
        width: 3em; 
        height: 1.5em; 
        cursor: pointer;
        border: 2px solid #6c757d;
        background-color: #e9ecef;
    }
    .form-check-input.custom-switch:checked {
        background-color: #198754;
        border-color: #198754;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Management Personal</h1>
        <p class="text-muted">Total personal găsit: <strong><?php echo $total_records; ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="add_personal.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Adaugă</a>
        <a href="bulk_edit.php" class="btn btn-primary"><i class="fas fa-pencil-alt me-2"></i>Editare</a>
        <a href="import_personal.php" class="btn btn-success"><i class="fas fa-file-excel me-2"></i>Import</a>
        <a href="export_personal.php" class="btn btn-success"><i class="fas fa-file-excel me-2"></i>Export</a>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">Actualizare reușită!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body p-4">
        <form action="" method="GET" id="searchForm" class="row g-3 align-items-center">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
            <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
            <div class="col-auto"><label class="col-form-label fw-bold" style="font-size: 1.1rem;">Caută:</label></div>
            <div class="col-auto flex-grow-1">
                <input type="text" id="searchInput" name="search" class="form-control form-control-lg" 
                       placeholder="Nume..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-lg">Caută</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?php echo sort_link('nume', 'Nume', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('grad', 'Grad', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('structura', 'Structura', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('localitate', 'Locație', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('rol', 'Rol (Modificabil)', $sort_column, $sort_order, $search); ?></th>
                    <th><?php echo sort_link('stare', 'Stare', $sort_column, $sort_order, $search); ?></th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personalList)): ?>
                    <tr><td colspan="7" class="text-center py-4">Lipsă rezultate.</td></tr>
                <?php else: ?>
                    <?php foreach ($personalList as $person): ?>
                        <?php
                            $nume = htmlspecialchars($person['nume'] . ' ' . $person['prenume']);
                            $struct = htmlspecialchars($person['structura_prescurt'] ?? '-');
                            if (!empty($person['substructura_prescurt'])) $struct .= ' / ' . htmlspecialchars($person['substructura_prescurt']);
                            
                            $loc = htmlspecialchars($person['uat'] ?? '');
                            if (!empty($person['localitate'])) $loc .= ' / ' . htmlspecialchars($person['localitate']);
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?php echo $nume; ?></span><br>
                                <small class="text-muted"><?php echo htmlspecialchars($person['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($person['nume_grad'] ?? '-'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $struct; ?></span></td>
                            <td><?php echo $loc ?: '-'; ?></td>

                            <td>
                                <select class="form-select role-changer" 
                                        data-id="<?php echo $person['id']; ?>" 
                                        style="min-width: 140px;">
                                    <?php foreach ($roluri_disponibile as $val => $label): ?>
                                        <option value="<?php echo $val; ?>" <?php echo ($person['rol'] == $val) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input custom-switch status-changer" 
                                           type="checkbox" 
                                           data-id="<?php echo $person['id']; ?>"
                                           <?php echo ($person['activ'] == 1) ? 'checked' : ''; ?>>
                                </div>
                            </td>

                            <td>
                                <a href="edit_personal.php?id=<?php echo $person['id']; ?>&return_url=personal.php" class="btn btn-sm btn-primary">
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
    <ul class="pagination justify-content-center pagination-lg">
        <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>">Prev</a>
        </li>
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // Funcție generică pentru update
    function updatePerson(id, field, value) {
        $.post('../api/update_personal_inline.php', {
            id: id,
            field: field,
            value: value
        }, function(response) {
            const res = JSON.parse(response);
            if (res.success) {
                // Afișăm Toast de succes
                var toastEl = document.getElementById('liveToast');
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            } else {
                alert('Eroare: ' + (res.message || 'Necunoscută'));
            }
        }).fail(function() {
            alert('Eroare conexiune server.');
        });
    }

    // 1. Schimbare ROL
    $('.role-changer').on('change', function() {
        const id = $(this).data('id');
        const newVal = $(this).val();
        updatePerson(id, 'rol', newVal);
    });

    // 2. Schimbare STARE (Activ/Inactiv)
    $('.status-changer').on('change', function() {
        const id = $(this).data('id');
        const newVal = $(this).is(':checked') ? 1 : 0;
        updatePerson(id, 'activ', newVal);
    });

    // Căutare debounce
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    let timeout = null;
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                if (searchInput.value.trim().length >= 3 || searchInput.value.trim().length === 0) {
                    searchForm.submit();
                }
            }, 800);
        });
        // Focus
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('search')) {
            const len = searchInput.value.length;
            searchInput.focus();
            searchInput.setSelectionRange(len, len);
        }
    }
});
</script>

<?php require_once '../includes/dashboard_footer.php'; ?>