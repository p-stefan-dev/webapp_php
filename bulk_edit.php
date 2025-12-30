<?php
// bulk_edit.php
ob_start();
$pageTitle = 'Editare în Masă';
require_once 'includes/dashboard_header.php';

if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

// --- LOGICA BACKEND (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ids = $input['ids'] ?? [];
    $action = $input['action'] ?? '';

    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'Nu ați selectat persoane.']);
        exit;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // 1. UPDATE GRADE
        if ($action === 'update_grade') {
            $val = $input['value'];
            $params = array_merge([$val], $ids);
            $stmt = $pdo->prepare("UPDATE personal SET id_grad = ? WHERE id IN ($placeholders)");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'count' => count($ids), 'msg' => 'Grade actualizate!']);
        }
        
        // 2. UPDATE STRUCTURA (Doar structura, resetăm substructura)
        elseif ($action === 'update_struct_only') {
            $val = $input['value'];
            $params = array_merge([$val], $ids);
            // Setăm id_struct = X și id_substr = NULL (sau 0)
            $stmt = $pdo->prepare("UPDATE personal SET id_struct = ?, id_substr = 0 WHERE id IN ($placeholders)");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'count' => count($ids), 'msg' => 'Structură atribuită (fără substructură)!']);
        }

        // 3. UPDATE SUBSTRUCTURA (Setăm și părintele automat)
        elseif ($action === 'update_substr_full') {
            $substrId = $input['value'];       // ID Substructură
            $parentId = $input['parent_id'];   // ID Structură Părinte
            
            $params = array_merge([$parentId, $substrId], $ids);
            // Setăm ambele ID-uri
            $stmt = $pdo->prepare("UPDATE personal SET id_struct = ?, id_substr = ? WHERE id IN ($placeholders)");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'count' => count($ids), 'msg' => 'Substructură și Structură părinte atribuite!']);
        }
        
        else {
            throw new Exception("Acțiune necunoscută.");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- ÎNCĂRCARE DATE PENTRU INTERFAȚĂ ---
$grade = $pdo->query("SELECT * FROM grade ORDER BY id_grad")->fetchAll();
$structuri = $pdo->query("SELECT * FROM structuri ORDER BY id_struct")->fetchAll();
// Încărcăm substructurile și le legăm de structuri pentru a ști părintele
$substructuri = $pdo->query("SELECT * FROM substructuri ORDER BY id_struct, denumire")->fetchAll();

ob_end_flush();
?>

<link href="../assets/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between mb-4">
    <h1 class="h2">Editare în Masă</h1>
    <a href="personal.php" class="btn btn-secondary">Înapoi</a>
</div>

<div id="globalStatus"></div>

<div class="card content-card mb-5">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Secțiunea 1: Atribuire Grade</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 border-end">
                <label class="form-label fw-bold">Selectează Personal</label>
                <select class="form-select" id="selGrade" multiple style="width: 100%;"></select>
                <small class="text-muted">Se afișează gradul actual lângă nume.</small>
            </div>
            <div class="col-md-6 ps-md-4">
                <label class="form-label fw-bold">Alege Gradul Nou</label>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach($grade as $g): ?>
                        <button class="btn btn-outline-primary btn-act-grade" 
                                data-id="<?php echo $g['id_grad']; ?>"
                                data-nume="<?php echo htmlspecialchars($g['prescurt']); ?>">
                            <?php echo htmlspecialchars($g['prescurt']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Secțiunea 2: Atribuire Structuri</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 border-end">
                <label class="form-label fw-bold">Selectează Personal</label>
                <select class="form-select" id="selStruct" multiple style="width: 100%;"></select>
                <small class="text-muted">Se afișează structura actuală lângă nume.</small>
            </div>
            
            <div class="col-md-6 ps-md-4">
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-success">A. Structuri Principale</label>
                    <p class="small text-muted mb-1">Click aici setează structura și șterge substructura.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($structuri as $s): ?>
                            <button class="btn btn-outline-success btn-act-struct" 
                                    data-id="<?php echo $s['id_struct']; ?>"
                                    data-nume="<?php echo htmlspecialchars($s['prescurt']); ?>">
                                <?php echo htmlspecialchars($s['prescurt']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr>

                <div>
                    <label class="form-label fw-bold text-info">B. Substructuri</label>
                    <p class="small text-muted mb-1">Click aici setează automat și structura părinte.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($substructuri as $sub): ?>
                            <button class="btn btn-outline-info btn-act-substr" 
                                    data-id="<?php echo $sub['id_substr']; ?>"
                                    data-parent="<?php echo $sub['id_struct']; ?>"
                                    data-nume="<?php echo htmlspecialchars($sub['prescurt']); ?>">
                                <?php echo htmlspecialchars($sub['prescurt']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // --- Configurare Comună Select2 ---
    function initSelect2(selector, contextType) {
        $(selector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Caută persoana...',
            minimumInputLength: 3,
            ajax: {
                url: 'api/search_personal.php',
                dataType: 'json',
                delay: 250,
                data: function(p) { return { term: p.term, context: contextType }; }, // Trimitem contextul!
                processResults: function(data) { return { results: data.results }; }
            }
        });
    }

    // 1. Init pentru Grade (context='grade')
    initSelect2('#selGrade', 'grade');

    // 2. Init pentru Structuri (context='struct')
    initSelect2('#selStruct', 'struct');

    // --- LOGICA CLICK (Funcție ajutătoare pentru fetch) ---
    function sendUpdate(payload, btn) {
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        fetch('bulk_edit.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                $('#globalStatus').html(`<div class="alert alert-success alert-dismissible fade show">${d.msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`);
                // Resetăm inputul corespunzător
                if(payload.action === 'update_grade') $('#selGrade').val(null).trigger('change');
                else $('#selStruct').val(null).trigger('change');
            } else {
                alert('Eroare: ' + d.error);
            }
        })
        .catch(e => alert('Eroare conexiune.'))
        .finally(() => btn.html(originalHtml).prop('disabled', false));
    }

    // --- CLICK: UPDATE GRADE ---
    $('.btn-act-grade').click(function() {
        const ids = $('#selGrade').val();
        if(!ids || !ids.length) return alert('Selectează persoane!');
        if(!confirm('Modifici gradul?')) return;

        sendUpdate({
            action: 'update_grade',
            ids: ids,
            value: $(this).data('id')
        }, $(this));
    });

    // --- CLICK: UPDATE STRUCTURA SIMPLĂ ---
    $('.btn-act-struct').click(function() {
        const ids = $('#selStruct').val();
        if(!ids || !ids.length) return alert('Selectează persoane!');
        if(!confirm('Atribui structura ' + $(this).data('nume') + '? (Substructura va fi ștearsă)')) return;

        sendUpdate({
            action: 'update_struct_only',
            ids: ids,
            value: $(this).data('id')
        }, $(this));
    });

    // --- CLICK: UPDATE SUBSTRUCTURA (Complexe) ---
    $('.btn-act-substr').click(function() {
        const ids = $('#selStruct').val();
        if(!ids || !ids.length) return alert('Selectează persoane!');
        
        const substrName = $(this).data('nume');
        if(!confirm('Atribui substructura ' + substrName + '? (Se va seta automat și structura părinte)')) return;

        sendUpdate({
            action: 'update_substr_full',
            ids: ids,
            value: $(this).data('id'),     // ID Substructură
            parent_id: $(this).data('parent') // ID Părinte (Structură)
        }, $(this));
    });

});
</script>

<?php require_once 'includes/dashboard_footer.php'; ?>