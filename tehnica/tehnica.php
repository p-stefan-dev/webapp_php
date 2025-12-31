<?php
// tehnica.php
// Pagina completă cu Listare + Modal Adăugare + Modal Editare

$pageTitle = 'Management Tehnică';
require_once '../includes/dashboard_header.php';
$id_struct_user = $_SESSION['id_struct'] ?? 0;

// Protecție acces
    if (!isset($userRole) || $userRole != 1) {
        echo '<div class="alert alert-danger">Acces restricționat.</div>';
        require_once '../includes/dashboard_footer.php';
        exit();
    }

$message = '';
$error = '';

// ==========================================================================
// 1. LOGICA DE PROCESARE FORMULARE (ADD & EDIT)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Preluare date comune
    $denumire   = trim($_POST['denumire'] ?? '');
    $nr_inmat   = trim($_POST['nr_inmatriculare'] ?? '');
    $codif      = trim($_POST['codif_teh'] ?? '');
    $tip_auto   = filter_input(INPUT_POST, 'tip_autosp', FILTER_VALIDATE_INT);
    $detalii    = trim($_POST['detalii'] ?? '');
    $id_struct  = filter_input(INPUT_POST, 'id_struct', FILTER_VALIDATE_INT);
    $id_substr  = filter_input(INPUT_POST, 'id_substr', FILTER_VALIDATE_INT) ?: 0;
    $stare      = $_POST['stare'] ?? 'operativ';

    if (empty($denumire)) {
        $error = "Denumirea este obligatorie!";
    } else {
        try {
            if ($action === 'add') {
                // --- INSERT ---
                $sql = "INSERT INTO tehnica (denumire, nr_inmatriculare, codif_teh, tip_autosp, detalii, id_struct, id_substr, stare) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$denumire, $nr_inmat, $codif, $tip_auto, $detalii, $id_struct, $id_substr, $stare]);
                $message = "Echipament adăugat cu succes!";
            } 
            elseif ($action === 'edit') {
                // --- UPDATE ---
                $id_edit = filter_input(INPUT_POST, 'id_tehnica', FILTER_VALIDATE_INT);
                if ($id_edit) {
                    $sql = "UPDATE tehnica SET denumire=?, nr_inmatriculare=?, codif_teh=?, tip_autosp=?, detalii=?, id_struct=?, id_substr=?, stare=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$denumire, $nr_inmat, $codif, $tip_auto, $detalii, $id_struct, $id_substr, $stare, $id_edit]);
                    $message = "Modificările au fost salvate!";
                }
            }
        } catch (PDOException $e) {
            $error = "Eroare baza de date: " . $e->getMessage();
        }
    }
}

// ==========================================================================
// 2. PREGĂTIRE DATE PENTRU AFIȘARE (Listare, Dropdown-uri)
// ==========================================================================

// Preluare Structuri (pentru dropdown-uri)
$structuri = $pdo->query("SELECT id_struct, Structura AS denumire FROM structuri ORDER BY Structura")->fetchAll();

// Configurare Paginare & Căutare
$limit = 10;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

// Configurare Sortare
$allowed_sort = ['denumire' => 't.denumire', 'nr' => 't.nr_inmatriculare', 'codif' => 't.codif_teh', 'tip' => 't.tip_autosp', 'struct' => 's.prescurt', 'stare' => 't.stare'];
$sort_col = $_GET['sort'] ?? 'denumire';
$sort_ord = $_GET['order'] ?? 'asc';
$sort_sql = $allowed_sort[$sort_col] ?? 't.denumire';
$order_sql = strtolower($sort_ord) === 'desc' ? 'DESC' : 'ASC';

// Construire Query Listare
$whereSQL = "";
$params = [];
if ($search) {
    $whereSQL = "WHERE (t.denumire LIKE :s1 OR t.nr_inmatriculare LIKE :s2 OR t.codif_teh LIKE :s3)";
    $params = [':s1' => "%$search%", ':s2' => "%$search%", ':s3' => "%$search%"];
}

// Count Total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tehnica t $whereSQL");
$stmtCount->execute($params);
$total_records = $stmtCount->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Data
$sql = "SELECT t.*, s.prescurt AS struct_nume, sub.prescurt AS substr_nume 
        FROM tehnica t
        LEFT JOIN structuri s ON t.id_struct = s.id_struct
        LEFT JOIN substructuri sub ON t.id_substr = sub.id_substr
        $whereSQL ORDER BY $sort_sql $order_sql LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$tehnicaList = $stmt->fetchAll();

// Funcție helper sortare link
function sort_link($col, $txt, $curr_col, $curr_ord, $search) {
    $new_ord = ($curr_col === $col && $curr_ord === 'asc') ? 'desc' : 'asc';
    $icon = ($curr_col === $col) ? ($curr_ord === 'asc' ? '▲' : '▼') : '';
    $s_param = $search ? "&search=".urlencode($search) : "";
    return "<a href='?sort=$col&order=$new_ord$s_param' class='text-white text-decoration-none'>$txt $icon</a>";
}
// --- LOGICĂ PENTRU ISTORIC (ULTIMELE 10 ÎNREGISTRĂRI) ---
$istoric = [];
try {
    // Selectăm datele din CRU și facem JOIN cu:
    // 1. coduri_interventie (pentru denumirea misiunii)
    // 2. personal (pentru numele celui care a introdus datele)
    // 3. substructuri (pentru a vedea de unde provine persoana respectivă, dacă e cazul)
    
    $sql_hist = "SELECT 
                    c.int_id, c.nr_int, c.autosp, c.dur, c.date as data_add,
                    ci.nume AS nume_interventie,
                    p.nume AS user_nume, p.prenume AS user_prenume,
                    s.prescurt AS user_substr
                 FROM cru c
                 LEFT JOIN coduri_interventie ci ON c.cod = ci.cod_id
                 LEFT JOIN personal p ON c.user_id = p.id
                 LEFT JOIN substructuri s ON p.id_substr = s.id_substr
                 WHERE c.categorie_id = ? 
                 ORDER BY c.int_id DESC 
                 LIMIT 10";

    $stmt_hist = $pdo->prepare($sql_hist);
    $stmt_hist->execute([$id_struct_user]); // $id_struct_user este definit la începutul paginii
    $istoric = $stmt_hist->fetchAll();

} catch (PDOException $e) {
    // Nu oprim execuția pentru istoric, doar logăm eroarea intern
    error_log("Eroare istoric: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h2">Management Tehnică</h1>
        <p class="text-muted">Total: <strong><?php echo $total_records; ?></strong> echipamente</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i>Adaugă Tehnică
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body py-2">
        <form action="" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_col); ?>">
            <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_ord); ?>">
            <div class="col-auto"><label class="fw-bold">Caută:</label></div>
            <div class="col-auto flex-grow-1">
                <input type="text" name="search" class="form-control form-control-sm" value="<?php echo htmlspecialchars($search); ?>" placeholder="Denumire, Nr. MAI, Cod...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Caută</button>
                <?php if($search): ?><a href="tehnica/tehnica.php" class="btn btn-sm btn-secondary">Reset</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?php echo sort_link('denumire', 'Denumire', $sort_col, $sort_ord, $search); ?></th>
                    <th><?php echo sort_link('nr', 'Nr. MAI', $sort_col, $sort_ord, $search); ?></th>
                    <th><?php echo sort_link('codif', 'Cod', $sort_col, $sort_ord, $search); ?></th>
                    <th>Structură</th>
                    <th>Detalii</th>
                    <th>Stare</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$tehnicaList): ?>
                    <tr><td colspan="7" class="text-center py-4">Nu există date.</td></tr>
                <?php else: ?>
                    <?php foreach ($tehnicaList as $t): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($t['denumire']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['nr_inmatriculare'] ?? '-'); ?></span></td>
                            <td><?php echo htmlspecialchars($t['codif_teh'] ?? '-'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($t['struct_nume'] ?? ''); ?>
                                <?php if($t['substr_nume']) echo '<br><small class="text-muted">↳ '.htmlspecialchars($t['substr_nume']).'</small>'; ?>
                            </td>
                            <td><small class="text-muted"><?php echo substr(htmlspecialchars($t['detalii']??''),0,20).(strlen($t['detalii']??'')>20?'...':''); ?></small></td>
                            <td>
                                <?php 
                                    $cls = ($t['stare']=='operativ') ? 'success' : (($t['stare']=='defect') ? 'danger' : 'secondary');
                                    echo "<span class='badge bg-$cls'>".ucfirst($t['stare'])."</span>";
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-edit"
                                    data-id="<?php echo $t['id']; ?>"
                                    data-denumire="<?php echo htmlspecialchars($t['denumire']); ?>"
                                    data-nr="<?php echo htmlspecialchars($t['nr_inmatriculare']); ?>"
                                    data-codif="<?php echo htmlspecialchars($t['codif_teh']); ?>"
                                    data-tip="<?php echo htmlspecialchars($t['tip_autosp']); ?>"
                                    data-detalii="<?php echo htmlspecialchars($t['detalii']); ?>"
                                    data-struct="<?php echo $t['id_struct']; ?>"
                                    data-substr="<?php echo $t['id_substr']; ?>"
                                    data-stare="<?php echo $t['stare']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($total_pages > 1): ?>
<div class="mt-3 text-center">
    <div class="btn-group">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm <?php echo $page==$i?'btn-primary':'btn-outline-primary'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Adaugă Tehnică Nouă</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Denumire Echipament</label>
                        <input type="text" name="denumire" class="form-control" required placeholder="ex: Autospecială 1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nr. Înmatriculare</label>
                        <input type="text" name="nr_inmatriculare" class="form-control" placeholder="MAI 12345">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stare</label>
                        <select name="stare" class="form-select">
                            <option value="operativ">Operativ</option>
                            <option value="defect">Defect</option>
                            <option value="casat">Casat</option>
                            <option value="rezerva">Rezervă</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Codificare Teh.</label>
                        <input type="text" name="codif_teh" class="form-control" placeholder="ex: AS-01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tip (Număr)</label>
                        <input type="number" name="tip_autosp" class="form-control" placeholder="ex: 1">
                    </div>
                    <div class="col-md-4"></div>

                    <div class="col-md-6">
                        <label class="form-label">Structura</label>
                        <select name="id_struct" id="add_struct" class="form-select" required>
                            <option value="">-- Alege --</option>
                            <?php foreach ($structuri as $s): ?>
                                <option value="<?php echo $s['id_struct']; ?>"><?php echo htmlspecialchars($s['denumire']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Substructura</label>
                        <select name="id_substr" id="add_substr" class="form-select" disabled>
                            <option value="0">Fără substructură</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Detalii / Observații</label>
                        <textarea name="detalii" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="submit" class="btn btn-primary">Salvează</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_tehnica" id="edit_id"> <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editează Tehnică</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Denumire Echipament</label>
                        <input type="text" name="denumire" id="edit_denumire" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nr. Înmatriculare</label>
                        <input type="text" name="nr_inmatriculare" id="edit_nr" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stare</label>
                        <select name="stare" id="edit_stare" class="form-select">
                            <option value="operativ">Operativ</option>
                            <option value="defect">Defect</option>
                            <option value="casat">Casat</option>
                            <option value="rezerva">Rezervă</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Codificare Teh.</label>
                        <input type="text" name="codif_teh" id="edit_codif" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tip (Număr)</label>
                        <input type="number" name="tip_autosp" id="edit_tip" class="form-control">
                    </div>
                    <div class="col-md-4"></div>

                    <div class="col-md-6">
                        <label class="form-label">Structura</label>
                        <select name="id_struct" id="edit_struct" class="form-select" required>
                            <option value="">-- Alege --</option>
                            <?php foreach ($structuri as $s): ?>
                                <option value="<?php echo $s['id_struct']; ?>"><?php echo htmlspecialchars($s['denumire']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Substructura</label>
                        <select name="id_substr" id="edit_substr" class="form-select">
                            <option value="0">Fără substructură</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Detalii / Observații</label>
                        <textarea name="detalii" id="edit_detalii" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="submit" class="btn btn-warning">Salvează Modificările</button>
            </div>
        </form>
        <div class="row mt-5">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Ultimele 10 rapoarte introduse</h5>
                <span class="badge bg-secondary">Structura Curentă</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>Nr. Intervenție</th>
                                <th>Indicativ Auto</th>
                                <th>Tip Intervenție (Cod)</th>
                                <th class="text-center">Durată</th>
                                <th>Operator (User)</th>
                                <th class="text-end">Data Adăugării</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($istoric)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Nu există înregistrări recente pentru această structură.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($istoric as $rec): ?>
                                    <tr>
                                        <td class="text-center text-muted">
                                            #<?php echo htmlspecialchars($rec['int_id']); ?>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?php echo htmlspecialchars($rec['nr_int']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($rec['autosp']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                // Afișăm numele codului sau un fallback
                                                echo htmlspecialchars($rec['nume_interventie'] ?? 'Cod Necunoscut'); 
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo htmlspecialchars($rec['dur']); ?> min
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column" style="line-height: 1.2;">
                                                <span class="fw-bold" style="font-size: 0.9rem;">
                                                    <i class="fas fa-user-edit me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($rec['user_nume'] . ' ' . $rec['user_prenume']); ?>
                                                </span>
                                                <small class="text-muted fst-italic">
                                                    <?php 
                                                        // Afișăm substructura dacă există, altfel "-"
                                                        echo !empty($rec['user_substr']) 
                                                            ? htmlspecialchars($rec['user_substr']) 
                                                            : '<span class="text-secondary">-</span>'; 
                                                    ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-end small text-muted">
                                            <?php 
                                                // Formatare dată (ex: 30.12.2025 14:30)
                                                echo date('d.m.Y H:i', strtotime($rec['data_add'])); 
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // --- A. FUNCȚIE REUTILIZABILĂ PENTRU ÎNCĂRCARE SUBSTRUCTURI ---
    function loadSubstructuri(structSelect, substrSelect, selectedSubstrId = null) {
        const idStruct = $(structSelect).val();
        const $target = $(substrSelect);
        
        $target.html('<option value="0">Se încarcă...</option>').prop('disabled', true);

        if (idStruct) {
            $.getJSON('../api/get_substructuri.php', { id_struct: idStruct }, function(data) {
                let options = '<option value="0">Fără substructură</option>';
                if (data.length > 0) {
                    $.each(data, function(key, val) {
                        // Verificăm dacă e substructura selectată anterior (pentru Edit)
                        const isSelected = (selectedSubstrId && String(val.id_substr) === String(selectedSubstrId)) ? 'selected' : '';
                        options += `<option value="${val.id_substr}" ${isSelected}>${val.denumire}</option>`;
                    });
                }
                $target.html(options).prop('disabled', false);
            }).fail(function() {
                $target.html('<option value="0">Eroare încărcare</option>');
            });
        } else {
            $target.html('<option value="0">Fără substructură</option>').prop('disabled', true);
        }
    }

    // --- B. EVENT LISTENERS PENTRU MODAL ADĂUGARE ---
    $('#add_struct').change(function() {
        loadSubstructuri('#add_struct', '#add_substr');
    });

    // --- C. EVENT LISTENERS PENTRU MODAL EDITARE ---
    // 1. Când se schimbă structura manual în editare
    $('#edit_struct').change(function() {
        loadSubstructuri('#edit_struct', '#edit_substr');
    });

    // 2. Când se deschide modalul de editare (populare date)
    $('.btn-edit').click(function() {
        // Preluăm datele din atributele butonului
        const btn = $(this);
        
        $('#edit_id').val(btn.data('id'));
        $('#edit_denumire').val(btn.data('denumire'));
        $('#edit_nr').val(btn.data('nr'));
        $('#edit_codif').val(btn.data('codif'));
        $('#edit_tip').val(btn.data('tip'));
        $('#edit_stare').val(btn.data('stare'));
        $('#edit_detalii').val(btn.data('detalii'));
        
        const structId = btn.data('struct');
        const substrId = btn.data('substr');

        // Setăm Structura
        $('#edit_struct').val(structId);

        // Declanșăm încărcarea substructurilor și setăm valoarea după ce s-au încărcat
        loadSubstructuri('#edit_struct', '#edit_substr', substrId);
    });
});
</script>

<?php require_once '../includes/dashboard_footer.php'; ?>