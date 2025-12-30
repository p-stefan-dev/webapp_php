<?php
// adauga_cru.php
$pageTitle = 'Adaugă Raport C.R.U.';
require_once '../includes/dashboard_header.php';

// 1. Verificare acces
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// 2. Preluare date context (Structura utilizatorului)
// Presupunem că id_struct este în sesiune.
$id_struct_user = $_SESSION['id_struct'] ?? 0;
$nume_structura = "Nedefinit";

try {
    // Aflăm numele structurii utilizatorului pentru afișare
    $stmt = $pdo->prepare("SELECT Structura FROM structuri WHERE id_struct = ?");
    $stmt->execute([$id_struct_user]);
    $nume_structura = $stmt->fetchColumn() ?: "Structură Necunoscută";

    // Preluăm lista de CODURI DE INTERVENȚIE (din tabelul `coduri_interventie` creat anterior)
    $coduri = $pdo->query("SELECT cod_id, nume FROM coduri_interventie WHERE activ = 1 ORDER BY cod_id ASC")->fetchAll();

    // Preluăm lista de PERSONAL (doar din structura curentă) pentru dropdown-uri
    $stmt = $pdo->prepare("SELECT id, CONCAT(nume, ' ', prenume) as nume_complet 
                           FROM personal 
                           WHERE id_struct = ? AND activ = 1 
                           ORDER BY nume ASC");
    $stmt->execute([$id_struct_user]);
    $personal_list = $stmt->fetchAll();

} catch (PDOException $e) {
    die('<div class="alert alert-danger">Eroare sistem: ' . $e->getMessage() . '</div>');
}

// 3. Procesare Formular
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preluare date
    // --- MODIFICARE: Numărul este preluat exact cum îl scrie utilizatorul ---
    $nr_int_input = trim($_POST['nr_int'] ?? ''); 
    
    $tip_reg      = $_POST['tip_reg'] ?? null;
    $autosp       = strtoupper(trim($_POST['autosp'] ?? ''));
    $nr_serv      = (int)($_POST['nr_serv'] ?? 0);
    $cod_id       = $_POST['cod'] ?? '';
    
    // Date Timp
    $d1 = $_POST['d1'] ?? '';
    $t1 = $_POST['t1'] ?? '';
    $d2 = $_POST['d2'] ?? '';
    $t2 = $_POST['t2'] ?? '';
    
    // Valori calculate
    $durata_calc = 0;
    $cru_calc = 0;

    // Date Suplimentare
    $nr_asist = (int)($_POST['nr_asist'] ?? 0);
    $nr_km    = (int)($_POST['nr_km'] ?? 0);
    $accident = isset($_POST['accident']) ? 1 : 0;
    $rcp      = isset($_POST['rcp']) ? 1 : 0;

    // Liste Personal
    $pers_amb_arr = $_POST['pers_amb'] ?? [];
    $pers_stg_arr = $_POST['pers_stingere'] ?? [];
    
    // --- VALIDĂRI ---
    if (empty($nr_int_input)) $errors[] = "Numărul de ordine este obligatoriu.";
    if (empty($autosp))       $errors[] = "Indicativul autospecialei este obligatoriu.";
    if ($nr_serv <= 0)        $errors[] = "Numărul de servanți trebuie să fie minim 1.";
    if (empty($cod_id))       $errors[] = "Selectați un cod de intervenție.";
    if (empty($tip_reg))      $errors[] = "Selectați tipul raportului (Stingere/SMURD).";
    
    // Validare Timp
    if ($d1 && $t1 && $d2 && $t2) {
        $start = strtotime("$d1 $t1");
        $end   = strtotime("$d2 $t2");
        if ($end <= $start) {
            $errors[] = "Data/Ora sosirii trebuie să fie după plecare.";
        } else {
            $durata_calc = round(($end - $start) / 60); // Minute
            $cru_calc = $durata_calc * $nr_serv;
        }
    } else {
        $errors[] = "Datele de plecare și sosire sunt incomplete.";
    }

    // Validare Cod 1 (Asistență Medicală)
    if ($cod_id == 1) {
        if ($nr_asist <= 0) $errors[] = "Pentru Asistență Medicală, nr. asistați este obligatoriu.";
        if ($nr_km <= 0)    $errors[] = "Pentru Asistență Medicală, nr. KM este obligatoriu.";
    }

    // --- SALVARE ÎN TABELUL `cru` ---
    if (empty($errors)) {
        try {
            // Transformăm array-urile de personal în string (ex: "1,5,9")
            $pers_amb_str = !empty($pers_amb_arr) ? implode(',', $pers_amb_arr) : '';
            $pers_stg_str = !empty($pers_stg_arr) ? implode(',', $pers_stg_arr) : '';

            $sql = "INSERT INTO cru (
                        categorie_id, user_id, nr_int, autosp, nr_serv, 
                        pers_amb, pers_stingere, cod, 
                        data_plc, data_sos, dur, min, 
                        tip_reg, accident, rcp, asistati, km, 
                        activ, date
                    ) VALUES (
                        ?, ?, ?, ?, ?, 
                        ?, ?, ?, 
                        ?, ?, ?, ?, 
                        ?, ?, ?, ?, ?, 
                        1, NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_struct_user, 
                $_SESSION['user_id'], 
                $nr_int_input, // Inserăm exact ce a scris utilizatorul
                $autosp, 
                $nr_serv,
                $pers_amb_str, 
                $pers_stg_str, 
                $cod_id,
                "$d1 $t1", 
                "$d2 $t2", 
                $durata_calc, 
                $cru_calc,
                $tip_reg, 
                $accident, 
                $rcp, 
                $nr_asist, 
                $nr_km
            ]);

            $successMessage = "Raportul <strong>" . htmlspecialchars($nr_int_input) . "</strong> a fost salvat cu succes!";
            
            // Golire formular post-submit (opțional)
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = "Eroare bază de date: " . $e->getMessage();
        }
    }
}
?>

<link href="../assets/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Adăugare Raport C.R.U.</h1>
        <p class="text-muted">Calcul Resurse Umane & Raportare</p>
    </div>
    <a href="../home.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Înapoi</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Eroare!</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="" method="POST" id="cruForm">
    <div class="row">
        
        <div class="col-lg-8">
            <div class="card content-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Date Generale Intervenție</h5>
                </div>
                <div class="card-body">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small text-uppercase fw-bold">Subunitate</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($nume_structura); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tip Raport <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_reg" id="rap_stingere" value="1" <?php echo (($_POST['tip_reg']??'')=='1')?'checked':''; ?> required>
                                    <label class="form-check-label" for="rap_stingere">Raport STINGERE</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_reg" id="rap_smurd" value="2" <?php echo (($_POST['tip_reg']??'')=='2')?'checked':''; ?>>
                                    <label class="form-check-label" for="rap_smurd">Raport S.M.U.R.D.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="nr_int" class="form-label fw-bold">Număr Intervenție <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nr_int" id="nr_int" 
                                   placeholder="ex: AR/3228/2025" 
                                   value="<?php echo htmlspecialchars($_POST['nr_int']??''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="autosp" class="form-label fw-bold">Indicativ Autospecială</label>
                            <input type="text" class="form-control text-uppercase" name="autosp" id="autosp" 
                                   placeholder="ex: AT5003" 
                                   value="<?php echo htmlspecialchars($_POST['autosp']??''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nr_serv" class="form-label fw-bold">Nr. Servanți</label>
                            <input type="number" class="form-control" name="nr_serv" id="nr_serv" min="1" 
                                   value="<?php echo htmlspecialchars($_POST['nr_serv']??''); ?>" required>
                            <div class="form-text">Folosit la calculul C.R.U.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cod" class="form-label fw-bold">Cod Intervenție / Tip Misiune</label>
                        <select class="form-select" name="cod" id="cod" required>
                            <option value="">-- Selectează Codul --</option>
                            <?php foreach($coduri as $cod): ?>
                                <option value="<?php echo $cod['cod_id']; ?>" <?php echo (($_POST['cod']??'') == $cod['cod_id']) ? 'selected' : ''; ?>>
                                    <?php echo $cod['cod_id'] . ' - ' . htmlspecialchars($cod['nume']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>

                    <h6 class="text-primary mb-3">Calcul Timpi și Durată</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Data Plecare</label>
                            <input type="date" class="form-control" name="d1" id="d1" value="<?php echo $_POST['d1'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Ora Plecare</label>
                            <input type="time" class="form-control" name="t1" id="t1" value="<?php echo $_POST['t1'] ?? date('H:i'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Data Sosire</label>
                            <input type="date" class="form-control" name="d2" id="d2" value="<?php echo $_POST['d2'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Ora Sosire</label>
                            <input type="time" class="form-control" name="t2" id="t2" value="<?php echo $_POST['t2'] ?? date('H:i'); ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-light border d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Durată (min):</strong> <span id="display_dur" class="fw-bold fs-5 text-primary">0</span>
                        </div>
                        <div>
                            <strong>Rezultat C.R.U.:</strong> <span id="display_cru" class="fw-bold fs-5 text-success">0</span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card content-card mb-4" id="card_specific" style="display:none;">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-ambulance me-2"></i>Detalii Suplimentare</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nr_asist" class="form-label fw-bold">Nr. Persoane Asistate</label>
                            <input type="number" class="form-control" name="nr_asist" id="nr_asist" min="0" value="<?php echo $_POST['nr_asist']??0; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="nr_km" class="form-label fw-bold">KM Parcurși</label>
                            <input type="number" class="form-control" name="nr_km" id="nr_km" min="0" value="<?php echo $_POST['nr_km']??0; ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="accident" id="accident" value="1" <?php echo (isset($_POST['accident']))?'checked':''; ?>>
                            <label class="form-check-label" for="accident">Accident Rutier</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="rcp" id="rcp" value="1" <?php echo (isset($_POST['rcp']))?'checked':''; ?>>
                            <label class="form-check-label" for="rcp">Resuscitare (RCP)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card content-card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Echipaj</h5>
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label for="pers_amb" class="form-label fw-bold text-success">Personal SMURD / Ambulanță</label>
                        <select class="form-select select2-personal" name="pers_amb[]" id="pers_amb" multiple>
                            <?php foreach($personal_list as $pers): ?>
                                <option value="<?php echo $pers['id']; ?>" <?php echo (in_array($pers['id'], $_POST['pers_amb']??[])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pers['nume_complet']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="pers_stingere" class="form-label fw-bold text-danger">Personal STINGERE</label>
                        <select class="form-select select2-personal" name="pers_stingere[]" id="pers_stingere" multiple>
                            <?php foreach($personal_list as $pers): ?>
                                <option value="<?php echo $pers['id']; ?>" <?php echo (in_array($pers['id'], $_POST['pers_stingere']??[])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pers['nume_complet']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Salvează Raport
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>

<?php require_once '../includes/dashboard_footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // 1. Inițializare Select2
    $('.select2-personal').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Caută și selectează...",
        allowClear: true
    });

    // 2. Funcție Calcul Timp & CRU
    function calculateCRU() {
        const d1 = $('#d1').val();
        const t1 = $('#t1').val();
        const d2 = $('#d2').val();
        const t2 = $('#t2').val();
        const servanti = parseInt($('#nr_serv').val()) || 0;

        if (d1 && t1 && d2 && t2) {
            const start = new Date(`${d1}T${t1}`);
            const end = new Date(`${d2}T${t2}`);

            if (end > start) {
                const diffMs = end - start;
                const diffMins = Math.round(diffMs / 60000); // ms -> minute
                const cru = diffMins * servanti;

                $('#display_dur').text(diffMins);
                $('#display_cru').text(cru);
            } else {
                $('#display_dur').text("Eroare (Sosire < Plecare)");
                $('#display_cru').text("0");
            }
        }
    }

    $('#d1, #t1, #d2, #t2, #nr_serv').on('change input', calculateCRU);

    // 3. Afișare Câmpuri Specifice
    function toggleSpecificFields() {
        const cod = $('#cod').val();
        // ID-ul 1 este ASISTENTA MEDICALA
        if (cod == '1') {
            $('#card_specific').slideDown();
            if (!$('input[name="tip_reg"]:checked').val()) {
                $('#rap_smurd').prop('checked', true);
            }
        } else {
            $('#card_specific').slideUp();
        }
    }

    $('#cod').on('change', toggleSpecificFields);
    
    calculateCRU();
    toggleSpecificFields();
});
</script>