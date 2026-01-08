<?php
// osd/registru_osd.php
$pageTitle = 'Registru OSD';
require_once '../includes/dashboard_header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// 1. Context Utilizator
$user_id_struct = $_SESSION['id_struct'] ?? 0;
$user_is_admin = ($_SESSION['rol'] ?? 0) == 1;

// 2. Data Planificării
$data_curenta = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d', strtotime('+1 day'));

// 3. Preluare Date
try {
    // Configurație
    $config = $pdo->query("
        SELECT c.*, s.Structura as nume_structura 
        FROM osd_configuratie c
        LEFT JOIN structuri s ON c.id_struct = s.id_struct 
        ORDER BY c.sectiune_id ASC
    ")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

    // Lista Personal (Pt Secțiunea 1)
    $stmtPers = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt as grad FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.activ = 1 ORDER BY p.nume ASC");
    $lista_personal = $stmtPers->fetchAll();

    // Date Existente (Secțiunea 1 - Speciale)
    $stmtData = $pdo->prepare("SELECT * FROM osd_rapoarte_speciale WHERE data = ?");
    $stmtData->execute([$data_curenta]);
    $date_existente = $stmtData->fetch(PDO::FETCH_ASSOC);
    
    $valori_go = $date_existente ? json_decode($date_existente['json_grup_operativ'], true) : [];
    $total_go = $date_existente['total_go'] ?? '';
    $total_msud = $date_existente['total_msud'] ?? '';
    $total_cjcci = $date_existente['total_cjcci'] ?? '';

    // Date Existente (Secțiunile 2-10 - Intervenție/Gărzi)
    // --- ACEASTA ESTE PARTEA ADĂUGATĂ PENTRU A PRELUA DATELE GĂRZILOR ---
    $stmtInt = $pdo->prepare("SELECT * FROM osd_rapoarte_interventie WHERE data = ?");
    $stmtInt->execute([$data_curenta]);
    $rows_interventie = $stmtInt->fetchAll(PDO::FETCH_ASSOC);
    
    $date_interventie = [];
    foreach($rows_interventie as $r) {
        // Folosim 0 dacă nu găsim coloana, ca să nu crape pagina
$id_sub = $r['id_substr'] ?? 0; 
$date_interventie[$r['sectiune_id']][$id_sub] = json_decode($r['json_date'], true);
    }
    // ---------------------------------------------------------------------

} catch (PDOException $e) { die("Eroare: " . $e->getMessage()); }

function canEdit($owner_struct_id, $user_struct, $is_admin) {
    if ($is_admin) return true;
    if (!$owner_struct_id) return false;
    return $owner_struct_id == $user_struct;
}

// Configurare Câmpuri Grupa Operativă (Existent)
$campuri_go = ['sef' => 'Șef Gr. Operativă', 'emi' => 'E.M.I.', 'soa' => 'S.O.A.', 'od' => 'O.D.', 'emsl'=> 'E.M.S.L.', 'ci' => 'C.I.', 'irp' => 'I.R.P.', 'sofer'=>'Șofer G.Op.'];

// NOU: Configurare Roluri MSUD
$roluri_msud = [
    'osd'    => 'O.S.D.',
    'ssd'    => 'S.S.D.',
    'aj_ssd' => 'Aj. S.S.D.',
    'radio'  => 'Radiotel.'
];

// Gestionare date MSUD existente
$valori_msud = [];
if (!empty($date_existente['json_msud'])) {
    $decoded = json_decode($date_existente['json_msud'], true);
    if (is_array($decoded)) {
        $valori_msud = $decoded;
    }
}

// GESTIONARE DATE CJCCI
$valori_cjcci = []; 
$text_cjcci_obs = ''; 
$cjcci_same_as_osd = 1; 

if (!empty($date_existente['json_cjcci'])) {
    $decoded = json_decode($date_existente['json_cjcci'], true);
    if (is_array($decoded)) {
        $valori_cjcci = $decoded['selections'] ?? [];
        $text_cjcci_obs = $decoded['obs'] ?? '';
        $cjcci_same_as_osd = $decoded['same_as_osd'] ?? 0;
    } else {
        $text_cjcci_obs = $decoded;
        $cjcci_same_as_osd = 1; 
    }
}
?>

<link href="../assets/css/select2.min.css" rel="stylesheet" />
<link href="../assets/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    /* --- STILURI GENERALE & CULORI --- */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice { 
        color: #000 !important; 
        background-color: #e9ecef !important; 
        border: 1px solid #ced4da; 
    }
    .form-label, .small.fw-bold { color: #212529 !important; }
    input[readonly], textarea[readonly], select[disabled] { 
        background-color: #f8f9fa !important; 
        color: #6c757d !important; 
        cursor: not-allowed; 
    }
    .accordion-button::after { filter: invert(1); } /* Săgeată albă */

    /* --- CHECKBOX CUSTOM (SWITCH) --- */
    #checkSameAsOSD {
        border: 2px solid #6c757d !important;
        background-color: #e9ecef !important;
        opacity: 1 !important;
    }
    #checkSameAsOSD:checked {
        background-color: #198754 !important;
        border-color: #198754 !important;
        background-image: url("../assets/images/switch-circle.svg") !important;
    }

    /* --- MODIFICARE DIMENSIUNI TEXT (User Interface) --- */
    .form-label, .small, small, .fw-bold.small { font-size: 1.1rem !important; }
    .form-control, .form-select { font-size: 1.1rem !important; padding: 0.6rem 0.75rem; }
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 1.1rem !important;   
        min-height: 45px !important;    
        display: flex !important;       
        align-items: center !important; 
    }
    .select2-container--bootstrap-5 .select2-selection .select2-selection__rendered {
        line-height: normal !important; 
        padding-top: 0 !important;
        color: #000 !important;         
        font-weight: 500;               
    }
    .select2-results__option { font-size: 1.2rem !important; padding: 10px 15px !important; }
    .select2-search__field { font-size: 1.2rem !important; height: 40px !important; }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice {
        font-size: 1rem !important;
        padding: 5px 10px !important;
        margin-top: 0px !important; 
    }
    .form-check-label { font-size: 1.1rem !important; }
</style>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i><strong>Succes!</strong> Datele pentru data de <?php echo date('d.m.Y', strtotime($data_curenta)); ?> au fost salvate și actualizate.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Registru OSD</h1>
        <p class="text-muted">Planificare pentru: <strong><?php echo date('d.m.Y', strtotime($data_curenta)); ?></strong></p>
    </div>
    <form action="" method="GET" class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border">
        <label class="fw-bold small text-muted mb-0 me-2"><i class="fas fa-calendar-alt"></i> Data:</label>
        <input type="date" name="data" class="form-control form-control-sm" 
               value="<?php echo $data_curenta; ?>" 
               onchange="this.form.submit()" 
               style="width: auto;">
    </form>
</div>

<div class="container-fluid p-0">
    
<?php for ($i = 1; $i <= 10; $i++): ?>
        <?php 
            $sect = $config[$i] ?? null;
            $owner_id = $sect['id_struct'] ?? null;
            
            if (empty($owner_id)) continue; 

            // Drepturi
            $is_my_section = ($owner_id == $user_id_struct);
            $can_edit = ($user_is_admin || $is_my_section);
            
            // Colapsare
            $collapse_class = $is_my_section ? 'show' : '';
            $aria_expanded = $is_my_section ? 'true' : 'false';
            $chevron_icon = $is_my_section ? 'fa-chevron-down' : 'fa-chevron-right';

            // UI
            $titlu = $sect['titlu_custom'] ?? "Secțiunea $i";
            $nume_structura = $sect['nume_structura'] ?? "Necunoscut";
            
            // --- LOGICĂ NOUĂ: Verificare existență date pentru colorare ---
            $exista_date = false;
            if ($i == 1) {
                // Pentru Secțiunea 1 verificăm dacă array-ul $date_existente nu e gol
                $exista_date = !empty($date_existente);
            } else {
                // Pentru Secțiunile 2-10 verificăm dacă există date în array-ul $date_interventie pentru secțiunea curentă
                $exista_date = !empty($date_interventie[$i]);
            }

            // Stabilim clasa CSS în funcție de rezultat
            // Verde (bg-success) dacă există date, Roșu (bg-danger) dacă nu există
            $status_badge_class = $exista_date ? 'bg-success text-white' : 'bg-danger text-white';
            // -------------------------------------------------------------

            // Stiluri Card
            $bg_header = $can_edit ? 'bg-primary text-white' : 'bg-secondary text-white';
            $border_card = $can_edit ? 'border-primary' : 'border-secondary';
            $disabled_attr = $can_edit ? '' : 'disabled';
            $readonly_attr = $can_edit ? '' : 'readonly';
            
            $collapse_id = 'collapseSection_' . $i;
            $form_action = ($i == 1) ? 'save_osd_speciale.php' : 'save_osd_interventie.php';
        ?>

        <form method="POST" action="<?php echo $form_action; ?>">
            <input type="hidden" name="data_curenta" value="<?php echo $data_curenta; ?>">
            <input type="hidden" name="sectiune_id" value="<?php echo $i; ?>">
            <input type="hidden" name="id_struct_owner" value="<?php echo $owner_id; ?>">

            <div class="card mb-4 shadow-sm <?php echo $border_card; ?>">
                
                <div class="card-header <?php echo $bg_header; ?> d-flex justify-content-between align-items-center" 
                     role="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>" 
                     aria-expanded="<?php echo $aria_expanded; ?>" aria-controls="<?php echo $collapse_id; ?>">
                    
                    <h5 class="mb-0 text-uppercase fw-bold">
                        <i class="fas <?php echo $chevron_icon; ?> me-2 small"></i>
                        <?php echo htmlspecialchars($titlu); ?>
                    </h5>
                    
                    <div class="small <?php echo $status_badge_class; ?> px-2 py-1 rounded shadow-sm">
                        Resp: <strong><?php echo htmlspecialchars($nume_structura); ?></strong>
                        <?php if($can_edit): ?>
                            <i class="fas fa-pen ms-1 text-white"></i>
                        <?php else: ?>
                            <i class="fas fa-lock ms-1 text-white-50"></i>
                        <?php endif; ?>
                    </div>
                    </div>

                <div id="<?php echo $collapse_id; ?>" class="collapse <?php echo $collapse_class; ?>">
                    <div class="card-body bg-white">
                        
                        <?php if ($i == 1): ?>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <h4 class="text-center fw-bold border-bottom pb-2 text-primary">GRUPA OPERATIVĂ</h4>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">TOTAL PERSONAL</label>
                                            <input type="number" name="total_go" class="form-control fw-bold text-center" 
                                                   value="<?php echo htmlspecialchars($total_go); ?>" 
                                                   <?php echo $readonly_attr; ?> placeholder="0">
                                        </div>
                                        <?php foreach ($campuri_go as $key => $label): ?>
                                            <div class="mb-2">
                                                <label class="small fw-bold text-dark mb-0"><?php echo $label; ?></label>
                                                <select class="form-select select2-go-single" name="go[<?php echo $key; ?>][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                    <option></option>
                                                    <?php 
                                                        $selected_ids = $valori_go[$key] ?? [];
                                                        if (!empty($selected_ids)) {
                                                            $ids_str = implode(',', array_map('intval', $selected_ids));
                                                            if($ids_str) {
                                                                $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)");
                                                                while ($p = $stmtPre->fetch()) {
                                                                    echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume'] . ' ' . $p['prenume']) . '</option>';
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <h4 class="text-center fw-bold border-bottom pb-2 text-primary">PERSONAL M.S.U.D.</h4>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">TOTAL PERSONAL</label>
                                            <input type="number" name="total_msud" class="form-control fw-bold text-center" 
                                                   value="<?php echo htmlspecialchars($total_msud); ?>" 
                                                   <?php echo $readonly_attr; ?> placeholder="0">
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6 text-center border-bottom mb-2"><small class="fw-bold text-muted">SCHIMBUL 1</small></div>
                                            <div class="col-6 text-center border-bottom mb-2"><small class="fw-bold text-muted">SCHIMBUL 2</small></div>
                                            <?php foreach ($roluri_msud as $key => $label): ?>
                                                <?php $k1 = $key . '_sch1'; $k2 = $key . '_sch2'; ?>
                                                <div class="col-6 mb-2">
                                                    <label class="small fw-bold text-dark mb-0" style="font-size: 0.75rem;"><?php echo $label; ?></label>
                                                    <select class="form-select select2-msud" name="msud[<?php echo $k1; ?>][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php 
                                                            $selected_ids = $valori_msud[$k1] ?? [];
                                                            if (!empty($selected_ids)) {
                                                                $ids_str = implode(',', array_map('intval', $selected_ids));
                                                                if($ids_str) {
                                                                    $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)");
                                                                    while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; }
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="small fw-bold text-dark mb-0" style="font-size: 0.75rem;"><?php echo $label; ?></label>
                                                    <select class="form-select select2-msud" name="msud[<?php echo $k2; ?>][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php 
                                                            $selected_ids = $valori_msud[$k2] ?? [];
                                                            if (!empty($selected_ids)) {
                                                                $ids_str = implode(',', array_map('intval', $selected_ids));
                                                                if($ids_str) {
                                                                    $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)");
                                                                    while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; }
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 border-bottom mb-1 opacity-25"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <h4 class="text-center fw-bold border-bottom pb-2 text-primary">PERSONAL C.J.C.C.I.</h4>
                                        <div class="bg-white p-3 border rounded mb-3 shadow-sm d-flex align-items-center">
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="checkSameAsOSD" name="cjcci[same_as_osd]" value="1" <?php echo $cjcci_same_as_osd ? 'checked' : ''; ?> <?php echo $disabled_attr; ?> style="width: 3.5em; height: 1.75em; cursor: pointer;">
                                                <label class="form-check-label fw-bold ms-3 mt-1 text-dark" for="checkSameAsOSD" style="cursor: pointer; font-size: 1rem;">Asigurat de O.S.D.</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">TOTAL PERSONAL</label>
                                            <input type="number" name="total_cjcci" class="form-control fw-bold text-center" value="<?php echo htmlspecialchars($total_cjcci); ?>" <?php echo $readonly_attr; ?> placeholder="0">
                                        </div>
                                        <div class="mb-3 border-bottom pb-2">
                                            <div class="row g-2">
                                                <div class="col-12"><small class="fw-bold text-primary">OFIȚERI (Câte 1 pers.)</small></div>
                                                <div class="col-12 mb-1">
                                                    <label class="small text-muted fw-bold mb-0" style="font-size: 0.7rem;">SCHIMBUL 1</label>
                                                    <select class="form-select select2-cjcci-single" name="cjcci[selections][ofiter_sch1][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php $sel_ids = $valori_cjcci['ofiter_sch1'] ?? []; if (!empty($sel_ids)) { $ids_str = implode(',', array_map('intval', $sel_ids)); if($ids_str) { $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)"); while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; } } } ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="small text-muted fw-bold mb-0" style="font-size: 0.7rem;">SCHIMBUL 2</label>
                                                    <select class="form-select select2-cjcci-single" name="cjcci[selections][ofiter_sch2][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php $sel_ids = $valori_cjcci['ofiter_sch2'] ?? []; if (!empty($sel_ids)) { $ids_str = implode(',', array_map('intval', $sel_ids)); if($ids_str) { $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)"); while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; } } } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 border-bottom pb-2">
                                            <div class="row g-2">
                                                <div class="col-12"><small class="fw-bold text-primary">SUBOFIȚERI (Max 2 pers.)</small></div>
                                                <div class="col-12 mb-1">
                                                    <label class="small text-muted fw-bold mb-0" style="font-size: 0.7rem;">SCHIMBUL 1</label>
                                                    <select class="form-select select2-cjcci-multi" name="cjcci[selections][subof_sch1][]" multiple="multiple" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php $sel_ids = $valori_cjcci['subof_sch1'] ?? []; if (!empty($sel_ids)) { $ids_str = implode(',', array_map('intval', $sel_ids)); if($ids_str) { $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)"); while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; } } } ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="small text-muted fw-bold mb-0" style="font-size: 0.7rem;">SCHIMBUL 2</label>
                                                    <select class="form-select select2-cjcci-multi" name="cjcci[selections][subof_sch2][]" multiple="multiple" <?php echo $disabled_attr; ?> style="width: 100%;">
                                                        <?php $sel_ids = $valori_cjcci['subof_sch2'] ?? []; if (!empty($sel_ids)) { $ids_str = implode(',', array_map('intval', $sel_ids)); if($ids_str) { $stmtPre = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)"); while ($p = $stmtPre->fetch()) { echo '<option value="' . $p['id'] . '" selected>' . htmlspecialchars($p['prescurt'] . ' ' . $p['nume']) . '</option>'; } } } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="small fw-bold text-dark">Alte observații</label>
                                            <textarea class="form-control" name="cjcci[obs]" rows="3" maxlength="250" <?php echo $readonly_attr; ?> placeholder="Detalii..."><?php echo htmlspecialchars($text_cjcci_obs); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <?php
                                $stmtSub = $pdo->prepare("SELECT * FROM substructuri WHERE id_struct = ? ORDER BY denumire ASC");
                                $stmtSub->execute([$owner_id]);
                                $gari = $stmtSub->fetchAll();
                            ?>

                            <?php if (empty($gari)): ?>
                                <div class="alert alert-warning mb-0">Structura nu are substructuri definite.</div>
                            <?php else: ?>
                                <div class="row">
                                    <<?php foreach ($gari as $garda): ?>
    <?php 
        // 1. Preluăm datele salvate
        $vals = $date_interventie[$i][$garda['id_substr']] ?? [];
        $get_ids = function($field) use ($vals) { return $vals[$field] ?? []; };

        // 2. Preluăm SETĂRILE DE AFIȘARE
        $jsonConfig = isset($sect['json_settings']) ? json_decode($sect['json_settings'], true) : [];
        
        $show_functii = $jsonConfig['substructuri'][$garda['id_substr']]['functii'] ?? 0;
        $show_misiuni = $jsonConfig['substructuri'][$garda['id_substr']]['misiuni'] ?? 0;
        $show_tehnica = $jsonConfig['substructuri'][$garda['id_substr']]['tehnica'] ?? 0;

        // 3. Calculăm grid-ul
        $active_modules = $show_functii + $show_misiuni + $show_tehnica;
        $col_class = 'col-12';
        if ($active_modules == 3) $col_class = 'col-md-4';
        elseif ($active_modules == 2) $col_class = 'col-md-6';
        elseif ($active_modules == 1) $col_class = 'col-md-12';
    ?>

    <div class="col-12 mb-4">
        <div class="card h-100 border-secondary shadow-sm">
            <div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-uppercase">
                    <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($garda['denumire']); ?>
                </span>
                <div class="small opacity-75">
                    <?php if($show_functii) echo '<i class="fas fa-user-tie mx-1" title="Funcții"></i>'; ?>
                    <?php if($show_misiuni) echo '<i class="fas fa-tasks mx-1" title="Misiuni"></i>'; ?>
                    <?php if($show_tehnica) echo '<i class="fas fa-truck mx-1" title="Tehnică"></i>'; ?>
                </div>
            </div>
            
            <div class="card-body bg-white p-3">
                
                <div class="row mb-3 border-bottom pb-3 g-2">
                    <?php 
                        // Definim cele 4 câmpuri numerice
                        $campuri_efectiv = [
                            'efectiv_control' => 'Efectiv Control',
                            'efectiv_absent'  => 'Efectiv Absent',
                            'efectiv'         => 'Efectiv Prezent', // Cheia originală pentru compatibilitate
                            'efectiv_asigura' => 'Efective care asigură'
                        ];

                        foreach ($campuri_efectiv as $key => $label):
                            $val = $vals[$key] ?? '';
                            if (is_array($val)) $val = ''; 
                    ?>
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-light h-100">
                            <label class="small fw-bold text-muted mb-1 d-block text-center text-truncate" title="<?php echo $label; ?>">
                                <?php echo strtoupper($label); ?>
                            </label>
                            <input type="number" 
                                   class="form-control fw-bold text-center border-secondary" 
                                   name="date[<?php echo $garda['id_substr']; ?>][<?php echo $key; ?>]" 
                                   value="<?php echo htmlspecialchars($val); ?>" 
                                   min="0" max="999" maxlength="3"
                                   oninput="if(this.value.length > 3) this.value = this.value.slice(0, 3);"
                                   <?php echo $disabled_attr; ?> 
                                   placeholder="0">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($active_modules > 0): ?>
                <div class="row g-3">
                    
                    <?php if ($show_functii): ?>
                    <div class="<?php echo $col_class; ?>">
                        <div class="h-100 p-2 border rounded bg-light">
                            <h6 class="text-muted border-bottom pb-2 small fw-bold text-center">1. FUNCȚII OPERATIVE</h6>
                            <?php 
                            $functii = ['sef_gis' => 'Șef GIS', 'disp_sch1' => 'Dispecer Sch 1', 'disp_sch2' => 'Dispecer Sch 2', 'paramedic' => 'Paramedic (Modul 2)'];
                            foreach($functii as $k => $lbl): ?>
                            <div class="mb-2">
                                <label class="small fw-bold mb-0"><?php echo $lbl; ?></label>
                                <select class="form-select select2-personal-single" name="date[<?php echo $garda['id_substr']; ?>][<?php echo $k; ?>][]" <?php echo $disabled_attr; ?> style="width: 100%;">
                                    <option></option>
                                    <?php $sid = $get_ids($k); if(!empty($sid)) { $id=(int)$sid[0]; $p=$pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad=g.id_grad WHERE p.id=$id")->fetch(); if($p) echo '<option value="'.$p['id'].'" selected>'.$p['prescurt'].' '.$p['nume'].'</option>'; } ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($show_misiuni): ?>
                    <div class="<?php echo $col_class; ?>">
                        <div class="h-100 p-2 border rounded bg-light">
                            <h6 class="text-muted border-bottom pb-2 small fw-bold text-center">2. PERSONAL MISIUNI</h6>
                            <?php 
                            // AM SCOS 'alte' DIN LISTĂ
                            $misiuni = [
                                'pirotehnic' => ['label' => 'Misiune Pirotehnică', 'max' => 3], 
                                'scafandri' => ['label' => 'Misiune Scafandri', 'max' => 3], 
                                'alpinisti' => ['label' => 'Misiune Alpiniști', 'max' => 3], 
                                'cbrne' => ['label' => 'Misiune CBRNe', 'max' => 3]
                            ];
                            foreach($misiuni as $key => $cfg): ?>
                            <div class="mb-2">
                                <label class="small fw-bold mb-0"><?php echo $cfg['label']; ?> <span class="text-muted fw-normal" style="font-size:0.7em">(max <?php echo $cfg['max']; ?>)</span></label>
                                <select class="form-select select2-misiuni" name="date[<?php echo $garda['id_substr']; ?>][<?php echo $key; ?>][]" multiple="multiple" data-max="<?php echo $cfg['max']; ?>" <?php echo $disabled_attr; ?> style="width: 100%;">
                                    <?php $ids = $get_ids($key); if (!empty($ids)) { $ids_str = implode(',', array_map('intval', $ids)); if($ids_str) { $stmtP = $pdo->query("SELECT p.id, p.nume, p.prenume, g.prescurt FROM personal p LEFT JOIN grade g ON p.id_grad = g.id_grad WHERE p.id IN ($ids_str)"); while ($p = $stmtP->fetch()) { echo '<option value="'.$p['id'].'" selected>'.htmlspecialchars($p['prescurt'].' '.$p['nume']).'</option>'; } } } ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($show_tehnica): ?>
                    <div class="<?php echo $col_class; ?>">
                        <div class="h-100 p-2 border rounded bg-light">
                            <h6 class="text-muted border-bottom pb-2 small fw-bold text-center">3. TEHNICĂ INDISPONIBILĂ</h6>
                            
                            <label class="small fw-bold mb-1">Tehnică scoasă de pe intervenție</label>
<select class="form-select select2-tehnica" 
        name="date[<?php echo $garda['id_substr']; ?>][tehnica][]" 
        multiple="multiple" 
        
        data-id-struct="<?php echo $garda['id_struct']; ?>" 
        
        <?php echo $disabled_attr; ?> 
        style="width: 100%;">
     <?php 
        $ids = $get_ids('tehnica');
        if (!empty($ids)) {
            $ids_str = implode(',', array_map('intval', $ids));
            if($ids_str) {
                // Interogare pentru afișarea valorilor deja salvate
                $stmtT = $pdo->query("
                    SELECT t.id, t.codif_teh, t.denumire, t.nr_inmatriculare, s.prescurt 
                    FROM tehnica t 
                    LEFT JOIN substructuri s ON t.id_substr = s.id_substr
                    WHERE t.id IN ($ids_str)
                ");
                while ($t = $stmtT->fetch()) { 
                    // Formatare identică cu API-ul
                    $txt = (!empty($t['codif_teh']) ? $t['codif_teh'] : $t['denumire']) . ' - ' . 
                           ($t['nr_inmatriculare'] ?: 'Fără Nr') . ' - ' . 
                           ($t['prescurt'] ?: '-');
                    echo '<option value="'.$t['id'].'" selected>'.htmlspecialchars($txt).'</option>'; 
                }
            }
        }
    ?>
</select>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">Căutați după denumire sau nr. înmatriculare.</div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <?php else: ?>
                    <div class="alert alert-light text-center mb-0 small text-muted border">
                        Niciun modul detaliat activat pentru această structură. (Doar Efective)
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                    
                    <?php if($can_edit): ?>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-end pb-3">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fas fa-save me-2"></i>Salvează <?php echo htmlspecialchars($titlu); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                </div> 
            </div> 
        </form>
    <?php endfor; ?>

</div>

<?php require_once '../includes/dashboard_footer.php'; ?>
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
$(document).ready(function() {

    const commonOpts = {
        theme: 'bootstrap-5',
        allowClear: true,
        width: '100%',
        language: { noResults: () => "Nu s-a găsit", searching: () => "Se caută...", inputTooShort: () => "..." }
    };

    // A. SECȚIUNEA 1 (OSD/CJCCI)
    $('.select2-go-single, .select2-msud').select2({
        ...commonOpts,
        placeholder: 'Selectează...',
        ajax: {
            url: '../api/search_personal.php',
            dataType: 'json',
            delay: 250,
            data: p => ({term: p.term}),
            processResults: data => ({results: data.results})
        }
    });

    $('.select2-cjcci-single').select2({ ...commonOpts, placeholder: 'Ofițer...', ajax: { url: '../api/search_personal.php', dataType: 'json', delay: 250, data: p => ({term: p.term}), processResults: data => ({results: data.results}) } });
    $('.select2-cjcci-multi').select2({ ...commonOpts, placeholder: 'Subofițeri...', maximumSelectionLength: 2, ajax: { url: '../api/search_personal.php', dataType: 'json', delay: 250, data: p => ({term: p.term}), processResults: data => ({results: data.results}) } });

    // B. SECȚIUNILE 2-10 (GĂRZI)
    $('.select2-personal-single').select2({ ...commonOpts, placeholder: 'Selectează...', ajax: { url: '../api/search_personal.php', dataType: 'json', delay: 250, data: p => ({term: p.term}), processResults: data => ({results: data.results}) } });

    $('.select2-misiuni').each(function() {
        const max = $(this).data('max') || 0;
        $(this).select2({
            ...commonOpts,
            placeholder: `Selectează (max ${max})...`,
            maximumSelectionLength: max,
            ajax: { url: '../api/search_personal.php', dataType: 'json', delay: 250, data: p => ({term: p.term}), processResults: data => ({results: data.results}) }
        });
    });

// 4. Tehnică (API Dedicat pe Structură)
    $('.select2-tehnica').each(function() {
        // MODIFICARE: Preluăm ID-ul structurii (Părinte/Detașament)
        const idStruct = $(this).data('id-struct');
        
        $(this).select2({
            ...commonOpts,
            placeholder: 'Caută tehnică (Cod / Nr / Nume)...',
            ajax: {
                url: '../api/search_tehnica.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        term: params.term,
                        // MODIFICARE: Trimitem parametrul id_struct
                        id_struct: idStruct 
                    };
                },
                processResults: data => ({results: data.results})
            }
        });
    });
    // C. LOGICA DE CALCUL
    function updateSectionTotal(selector, inputName) {
        let total = 0;
        $(selector).each(function() {
            const val = $(this).val();
            if (val && val.length > 0) {
                if (Array.isArray(val)) { total += val.length; } else { total++; }
            }
        });
        $('input[name="' + inputName + '"]').val(total);
    }

    $('.select2-go-single').on('change', function() { updateSectionTotal('.select2-go-single', 'total_go'); });
    $('.select2-msud').on('change', function() { updateSectionTotal('.select2-msud', 'total_msud'); });

    // CJCCI Logic
    function copySelect2Data(src, tgt) {
        const s = $(src), t = $(tgt), d = s.select2('data');
        t.empty();
        if (d && d.length > 0) { t.append(new Option(d[0].text, d[0].id, true, true)).trigger('change'); } else { t.val(null).trigger('change'); }
    }

    function syncCjcciWithOsd() {
        const isChecked = $('#checkSameAsOSD').is(':checked');
        const fields = $('.select2-cjcci-single, .select2-cjcci-multi, textarea[name="cjcci[obs]"]');
        if (isChecked) {
            copySelect2Data('select[name="msud[osd_sch1][]"]', 'select[name="cjcci[selections][ofiter_sch1][]"]');
            copySelect2Data('select[name="msud[osd_sch2][]"]', 'select[name="cjcci[selections][ofiter_sch2][]"]');
            $('.select2-cjcci-multi').val(null).trigger('change');
            $('textarea[name="cjcci[obs]"]').val('');
            fields.prop('disabled', true);
            $('input[name="total_cjcci"]').val(0).prop('readonly', true);
        } else {
            fields.prop('disabled', false);
            $('input[name="total_cjcci"]').prop('readonly', false);
            recalcTotalCJCCI();
        }
    }

    function recalcTotalCJCCI() {
        if ($('#checkSameAsOSD').is(':checked')) return;
        let total = 0;
        $('.select2-cjcci-single').each(function(){ const d = $(this).select2('data'); if(d && (Array.isArray(d) ? d.length : d.id)) total++; });
        $('.select2-cjcci-multi').each(function(){ const d = $(this).select2('data'); if(d) total += d.length; });
        $('input[name="total_cjcci"]').val(total);
    }

    $('#checkSameAsOSD').on('change', syncCjcciWithOsd);
    $('.select2-cjcci-single, .select2-cjcci-multi').on('change', recalcTotalCJCCI);
    $('select[name="msud[osd_sch1][]"], select[name="msud[osd_sch2][]"]').on('change', function() { if ($('#checkSameAsOSD').is(':checked')) syncCjcciWithOsd(); });

    // Init
    updateSectionTotal('.select2-go-single', 'total_go');
    updateSectionTotal('.select2-msud', 'total_msud');
    if ($('#checkSameAsOSD').is(':checked')) { syncCjcciWithOsd(); } else { recalcTotalCJCCI(); }
});
</script>