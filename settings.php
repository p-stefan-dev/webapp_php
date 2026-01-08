<?php
// settings.php

require_once 'config/db.php';
$pageTitle = 'Setări Aplicație';
require_once 'includes/dashboard_header.php';

// Protectie suplimentara - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$successMessage = '';
$errorMessage = '';

// ==========================================================================
// 0. INITIALIZARE TABELE OSD
// ==========================================================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS osd_configuratie (
        sectiune_id INT NOT NULL PRIMARY KEY,
        id_struct INT DEFAULT NULL,
        titlu_custom VARCHAR(100) DEFAULT NULL,
        json_settings TEXT DEFAULT NULL
    )");
    
    $count = $pdo->query("SELECT COUNT(*) FROM osd_configuratie")->fetchColumn();
    if ($count == 0) {
        $stmtInit = $pdo->prepare("INSERT INTO osd_configuratie (sectiune_id, titlu_custom) VALUES (?, ?)");
        for ($i=1; $i<=10; $i++) {
            $titlu = ($i == 1) ? 'GRUPA OPERATIVĂ / MSUD / CJCCI' : "Secțiunea $i";
            $stmtInit->execute([$i, $titlu]);
        }
    }
} catch (PDOException $e) { /* Ignoram daca exista */ }


// ==========================================================================
// LOGICA DE PROCESARE (POST)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. Configurare OSD ---
    if (isset($_POST['save_osd_config'])) {
        try {
            $pdo->beginTransaction();
            $stmtUpdate = $pdo->prepare("UPDATE osd_configuratie SET id_struct = ?, titlu_custom = ? WHERE sectiune_id = ?");
            for ($i = 1; $i <= 10; $i++) {
                $struct_id = !empty($_POST["struct_$i"]) ? $_POST["struct_$i"] : null;
                $titlu = !empty($_POST["titlu_$i"]) ? $_POST["titlu_$i"] : "Secțiunea $i";
                $stmtUpdate->execute([$struct_id, $titlu, $i]);
            }
            $pdo->commit();
            $successMessage = 'Configurația secțiunilor OSD a fost actualizată!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = 'Eroare salvare OSD: ' . $e->getMessage();
        }
    }

    // --- 2. Titlu App ---
    if (isset($_POST['save_title'])) {
        $newTitle = trim($_POST['dashboard_title']);
        if (!empty($newTitle)) {
            try {
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('dashboard_title', ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$newTitle]);
                $successMessage = 'Numele aplicației a fost actualizat!';
            } catch (PDOException $e) { $errorMessage = 'Eroare la salvare: ' . $e->getMessage(); }
        }
    }

    // --- 3. Upload Imagine ---
    if (isset($_POST['upload_image'])) {
        if (isset($_FILES['login_background']) && $_FILES['login_background']['error'] == 0) {
            $targetDir = "assets/images/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            
            $allowedTypes = ['jpg', 'jpeg', 'png'];
            $fileName = basename($_FILES["login_background"]["name"]);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileType, $allowedTypes)) {
                $newFileName = uniqid('bg_', true) . '.' . $fileType;
                $targetFile = $targetDir . $newFileName;
                if (move_uploaded_file($_FILES["login_background"]["tmp_name"], $targetFile)) {
                    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('login_background_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$targetFile]);
                    $successMessage = 'Imaginea de fundal a fost actualizată!';
                }
            }
        }
    }

    // --- 4. Structuri ---
    if (isset($_POST['add_structura'])) {
        $stmt = $pdo->prepare("INSERT INTO structuri (Structura, prescurt) VALUES (?, ?)");
        $stmt->execute([$_POST['structura_denumire'], $_POST['structura_prescurtare']]);
        $successMessage = 'Structură adăugată!';
    }
    if (isset($_POST['edit_structura'])) {
        $stmt = $pdo->prepare("UPDATE structuri SET Structura=?, prescurt=? WHERE id_struct=?");
        $stmt->execute([$_POST['edit_structura_denumire'], $_POST['edit_structura_prescurtare'], $_POST['edit_structura_id']]);
        $successMessage = 'Structură actualizată!';
    }

    // --- 5. Substructuri ---
    if (isset($_POST['add_substructura'])) {
        $stmt = $pdo->prepare("INSERT INTO substructuri (id_struct, denumire, prescurt) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['id_struct'], $_POST['substructura_denumire'], $_POST['substructura_prescurtare']]);
        $successMessage = 'Substructură adăugată!';
    }
    if (isset($_POST['edit_substructura'])) {
        $stmt = $pdo->prepare("UPDATE substructuri SET id_struct=?, denumire=?, prescurt=? WHERE id_substr=?");
        $stmt->execute([$_POST['edit_substructura_id_struct'], $_POST['edit_substructura_denumire'], $_POST['edit_substructura_prescurtare'], $_POST['edit_substructura_id']]);
        $successMessage = 'Substructură actualizată!';
    }
}


// ==========================================================================
// PRELUARE DATE
// ==========================================================================
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'dashboard_title'");
    $stmt->execute();
    $currentTitle = $stmt->fetchColumn() ?: '';

    $structuri = $pdo->query("SELECT id_struct, Structura AS denumire, prescurt FROM structuri ORDER BY id_struct")->fetchAll();
    $substructuri = $pdo->query("SELECT s.*, st.prescurt AS structura_prescurt FROM substructuri s JOIN structuri st ON s.id_struct = st.id_struct ORDER BY st.prescurt, s.denumire")->fetchAll();

    $osd_config = $pdo->query("SELECT * FROM osd_configuratie ORDER BY sectiune_id ASC")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

} catch (PDOException $e) {
    $currentTitle = 'Eroare DB';
    $structuri = [];
    $substructuri = [];
    $osd_config = [];
}
?>

<style>
    /* Switch Custom Verde */
    .form-check-input.custom-switch {
        width: 2.5em; height: 1.25em; cursor: pointer;
        border: 2px solid #6c757d; background-color: #e9ecef;
    }
    .form-check-input.custom-switch:checked {
        background-color: #198754; border-color: #198754;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
    }
    .substructure-row {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        transition: background-color 0.3s;
    }
    .substructure-row:hover { background-color: #fff; }
</style>

<h1 class="h2 mb-4">Setări Aplicație</h1>

<?php if ($successMessage): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">Generale & Structuri</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="osd-tab" data-bs-toggle="tab" data-bs-target="#osd" type="button">Configurare OSD</button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    
    <div class="tab-pane fade show active" id="general">
        
        <h5 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-cogs me-2"></i>Setări Vizuale</h5>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Nume Aplicație</h6>
                        <form action="" method="post">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="dashboard_title" value="<?php echo htmlspecialchars($currentTitle); ?>" required>
                            </div>
                            <button type="submit" name="save_title" class="btn btn-sm btn-primary">Salvează</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Imagine Login</h6>
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input class="form-control form-control-sm" type="file" name="login_background" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-sm btn-primary">Încarcă</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="fas fa-sitemap me-2"></i>Administrare Organigramă</h5>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card content-card h-100">
                    <div class="card-header bg-light fw-bold">Structuri (Detașamente/Secții)</div>
                    <div class="card-body">
                        <form action="" method="post" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="structura_denumire" placeholder="Nume Structură" required>
                                <input type="text" class="form-control" name="structura_prescurtare" placeholder="Prescurt" required>
                                <button class="btn btn-success" type="submit" name="add_structura"><i class="fas fa-plus"></i></button>
                            </div>
                        </form>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light"><tr><th>ID</th><th>Nume</th><th>Act</th></tr></thead>
                                <tbody>
                                    <?php foreach($structuri as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id_struct']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($item['denumire']); ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($item['prescurt']); ?></div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary py-0" data-bs-toggle="modal" data-bs-target="#editStructuraModal"
                                                data-id="<?php echo $item['id_struct']; ?>"
                                                data-denumire="<?php echo htmlspecialchars($item['denumire']); ?>"
                                                data-prescurtare="<?php echo htmlspecialchars($item['prescurt']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card content-card h-100">
                    <div class="card-header bg-light fw-bold">Substructuri (Gărzi/Puncte Lucru)</div>
                    <div class="card-body">
                        <form action="" method="post" class="mb-3">
                            <select class="form-select mb-2" name="id_struct" required>
                                <option value="">Alege Structura Părinte...</option>
                                <?php foreach ($structuri as $s): ?>
                                    <option value="<?php echo $s['id_struct']; ?>"><?php echo htmlspecialchars($s['denumire']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group">
                                <input type="text" class="form-control" name="substructura_denumire" placeholder="Nume Substructură" required>
                                <input type="text" class="form-control" name="substructura_prescurtare" placeholder="Prescurt" style="max-width: 150px;">
                                <button class="btn btn-success" type="submit" name="add_substructura"><i class="fas fa-plus"></i></button>
                            </div>
                        </form>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light"><tr><th>Părinte</th><th>Nume</th><th>Act</th></tr></thead>
                                <tbody>
                                    <?php foreach($substructuri as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['structura_prescurt']); ?></span></td>
                                        <td><?php echo htmlspecialchars($item['denumire']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary py-0" data-bs-toggle="modal" data-bs-target="#editSubstructuraModal"
                                                data-id="<?php echo $item['id_substr']; ?>"
                                                data-id-struct="<?php echo $item['id_struct']; ?>"
                                                data-denumire="<?php echo htmlspecialchars($item['denumire']); ?>"
                                                data-prescurtare="<?php echo htmlspecialchars($item['prescurt']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="osd">
        <div class="card content-card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Configurare Secțiuni Registru OSD</h5>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    
                    <div class="alert alert-info d-flex align-items-center p-2 mb-3">
                        <i class="fas fa-info-circle fs-4 me-3"></i>
                        <div class="small">
                            <strong>Instrucțiuni:</strong> Selectați structura responsabilă și salvați. După salvare, vor apărea substructurile asociate. 
                            Folosiți switch-urile pentru a activa/dezactiva modulele (Funcții, Misiuni, Tehnică) pentru fiecare gardă în parte.
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <?php 
                                $currentStruct = $osd_config[$i]['id_struct'] ?? ''; 
                                $currentTitle = $osd_config[$i]['titlu_custom'] ?? (($i==1) ? 'GRUPA OPERATIVĂ / MSUD / CJCCI' : "Secțiunea $i");
                                $isSpecial = ($i == 1);
                                
                                // Decodare setări JSON pentru această secțiune
                                $jsonSettings = isset($osd_config[$i]['json_settings']) ? json_decode($osd_config[$i]['json_settings'], true) : [];
                            ?>
                            
                            <div class="col-md-12">
                                <div class="card <?php echo $isSpecial ? 'border-primary shadow-sm' : 'border-secondary bg-light'; ?>">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center <?php echo $isSpecial ? 'bg-primary text-white' : ''; ?>">
                                        <span class="fw-bold">Secțiunea <?php echo $i; ?> <?php if($isSpecial) echo '(Fixă)'; ?></span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-md-4 border-end">
                                                <div class="mb-3">
                                                    <label class="form-label small text-muted mb-0">Titlu Secțiune</label>
                                                    <input type="text" class="form-control fw-bold" name="titlu_<?php echo $i; ?>" value="<?php echo htmlspecialchars($currentTitle); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small text-muted mb-0">Structura Responsabilă</label>
                                                    <select class="form-select <?php echo $isSpecial ? 'border-primary' : ''; ?>" name="struct_<?php echo $i; ?>">
                                                        <option value="">-- Niciuna (Ascunsă) --</option>
                                                        <?php foreach ($structuri as $s): ?>
                                                            <option value="<?php echo $s['id_struct']; ?>" <?php echo ($currentStruct == $s['id_struct']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($s['denumire']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="text-muted small">
                                                    <em>* Salvați pagina pentru a încărca substructurile după selectare.</em>
                                                </div>
                                            </div>

                                            <div class="col-md-8">
                                                <?php if ($currentStruct && !$isSpecial): ?>
                                                    <label class="form-label small fw-bold text-dark mb-2">Configurare Module per Substructură</label>
                                                    <?php
                                                        // Căutăm substructurile pentru structura salvată
                                                        $subs = $pdo->prepare("SELECT * FROM substructuri WHERE id_struct = ? ORDER BY denumire ASC");
                                                        $subs->execute([$currentStruct]);
                                                        $lista_subs = $subs->fetchAll();
                                                    ?>

                                                    <?php if (count($lista_subs) > 0): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-borderless align-middle mb-0">
                                                                <thead>
                                                                    <tr class="text-muted small border-bottom">
                                                                        <th>Substructură</th>
                                                                        <th class="text-center">1. Funcții Op.</th>
                                                                        <th class="text-center">2. Misiuni</th>
                                                                        <th class="text-center">3. Tehnică</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($lista_subs as $sb): ?>
                                                                        <?php 
                                                                            // Citim setările salvate sau default 0 (dezactivat)
                                                                            $s_functii = $jsonSettings['substructuri'][$sb['id_substr']]['functii'] ?? 0;
                                                                            $s_misiuni = $jsonSettings['substructuri'][$sb['id_substr']]['misiuni'] ?? 0;
                                                                            $s_tehnica = $jsonSettings['substructuri'][$sb['id_substr']]['tehnica'] ?? 0;
                                                                        ?>
                                                                        <tr class="substructure-row border-bottom">
                                                                            <td class="fw-bold ps-2"><?php echo htmlspecialchars($sb['denumire']); ?></td>
                                                                            
                                                                            <td class="text-center">
                                                                                <div class="form-check form-switch d-inline-block">
                                                                                    <input class="form-check-input custom-switch setting-toggle" type="checkbox" 
                                                                                           data-sectiune="<?php echo $i; ?>" 
                                                                                           data-substr="<?php echo $sb['id_substr']; ?>" 
                                                                                           data-field="functii"
                                                                                           <?php echo $s_functii ? 'checked' : ''; ?>>
                                                                                </div>
                                                                            </td>

                                                                            <td class="text-center">
                                                                                <div class="form-check form-switch d-inline-block">
                                                                                    <input class="form-check-input custom-switch setting-toggle" type="checkbox" 
                                                                                           data-sectiune="<?php echo $i; ?>" 
                                                                                           data-substr="<?php echo $sb['id_substr']; ?>" 
                                                                                           data-field="misiuni"
                                                                                           <?php echo $s_misiuni ? 'checked' : ''; ?>>
                                                                                </div>
                                                                            </td>

                                                                            <td class="text-center">
                                                                                <div class="form-check form-switch d-inline-block">
                                                                                    <input class="form-check-input custom-switch setting-toggle" type="checkbox" 
                                                                                           data-sectiune="<?php echo $i; ?>" 
                                                                                           data-substr="<?php echo $sb['id_substr']; ?>" 
                                                                                           data-field="tehnica"
                                                                                           <?php echo $s_tehnica ? 'checked' : ''; ?>>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted small fst-italic">Nu există substructuri definite pentru această structură.</div>
                                                    <?php endif; ?>
                                                <?php elseif ($isSpecial): ?>
                                                    <div class="alert alert-light border mb-0 text-center">Configurația pentru Grupa Operativă este standard.</div>
                                                <?php else: ?>
                                                    <div class="text-muted small mt-4 text-center">Selectați și salvați o structură pentru a configura detaliile.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <hr class="mt-4">
                    <button type="submit" name="save_osd_config" class="btn btn-success btn-lg w-100 shadow">
                        <i class="fas fa-save me-2"></i>Salvează Configurația Generală
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="editStructuraModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editare Structură</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
          <input type="hidden" name="edit_structura_id" id="edit_structura_id">
          <div class="mb-3"><label>Denumire</label><input type="text" class="form-control" name="edit_structura_denumire" id="edit_structura_denumire" required></div>
          <div class="mb-3"><label>Prescurtare</label><input type="text" class="form-control" name="edit_structura_prescurtare" id="edit_structura_prescurtare"></div>
      </div>
      <div class="modal-footer"><button type="submit" name="edit_structura" class="btn btn-primary">Salvează</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="editSubstructuraModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editare Substructură</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
          <input type="hidden" name="edit_substructura_id" id="edit_substructura_id">
          <div class="mb-3">
              <label>Structura Părinte</label>
              <select class="form-select" name="edit_substructura_id_struct" id="edit_substructura_id_struct">
                  <?php foreach ($structuri as $s): ?><option value="<?php echo $s['id_struct']; ?>"><?php echo htmlspecialchars($s['denumire']); ?></option><?php endforeach; ?>
              </select>
          </div>
          <div class="mb-3"><label>Denumire</label><input type="text" class="form-control" name="edit_substructura_denumire" id="edit_substructura_denumire" required></div>
          <div class="mb-3"><label>Prescurtare</label><input type="text" class="form-control" name="edit_substructura_prescurtare" id="edit_substructura_prescurtare"></div>
      </div>
      <div class="modal-footer"><button type="submit" name="edit_substructura" class="btn btn-primary">Salvează</button></div>
    </form>
  </div>
</div>

<?php require_once 'includes/dashboard_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Gestionare Toggles (AJAX)
    const toggles = document.querySelectorAll('.setting-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const sectiuneId = this.getAttribute('data-sectiune');
            const idSubstr = this.getAttribute('data-substr');
            const field = this.getAttribute('data-field');
            const value = this.checked ? 1 : 0;

            // Trimitem datele la API
            fetch('api/update_osd_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `sectiune_id=${sectiuneId}&id_substr=${idSubstr}&field=${field}&value=${value}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Setare salvată:', field, value);
                } else {
                    alert('Eroare la salvare: ' + (data.message || 'Necunoscută'));
                    this.checked = !this.checked; // Revenim la starea anterioară
                }
            })
            .catch(err => {
                console.error(err);
                alert('Eroare conexiune.');
                this.checked = !this.checked;
            });
        });
    });

    // Populate Edit Structura
    const editSModal = document.getElementById('editStructuraModal');
    if(editSModal) {
        editSModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            this.querySelector('#edit_structura_id').value = btn.getAttribute('data-id');
            this.querySelector('#edit_structura_denumire').value = btn.getAttribute('data-denumire');
            this.querySelector('#edit_structura_prescurtare').value = btn.getAttribute('data-prescurtare');
        });
    }
    // Populate Edit Substructura
    const editSubModal = document.getElementById('editSubstructuraModal');
    if(editSubModal) {
        editSubModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            this.querySelector('#edit_substructura_id').value = btn.getAttribute('data-id');
            this.querySelector('#edit_substructura_id_struct').value = btn.getAttribute('data-id-struct');
            this.querySelector('#edit_substructura_denumire').value = btn.getAttribute('data-denumire');
            this.querySelector('#edit_substructura_prescurtare').value = btn.getAttribute('data-prescurtare');
        });
    }
});
</script>