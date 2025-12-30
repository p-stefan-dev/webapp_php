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
// LOGICA DE PROCESARE (POST)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. Salvare Titlu App (Metoda Robusta UPSERT) ---
    if (isset($_POST['save_title'])) {
        $newTitle = trim($_POST['dashboard_title']);
        if (!empty($newTitle)) {
            try {
                // Folosim INSERT ... ON DUPLICATE KEY UPDATE pentru a crea rândul dacă nu există
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('dashboard_title', ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$newTitle]);
                $successMessage = 'Numele aplicației a fost actualizat!';
            } catch (PDOException $e) { 
                $errorMessage = 'Eroare la salvare: ' . $e->getMessage(); 
            }
        } else {
            $errorMessage = 'Numele aplicației nu poate fi gol.';
        }
    }

    // --- 2. Upload Imagine Login (Metoda Robusta UPSERT) ---
    if (isset($_POST['upload_image'])) {
        if (isset($_FILES['login_background']) && $_FILES['login_background']['error'] == 0) {
            $targetDir = "assets/images/";
            // Creăm folderul dacă nu există
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            
            $allowedTypes = ['jpg', 'jpeg', 'png'];
            $fileName = basename($_FILES["login_background"]["name"]);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($fileType, $allowedTypes)) {
                $errorMessage = 'Eroare: Sunt permise doar fișiere de tip JPG, JPEG, PNG.';
            } elseif ($_FILES["login_background"]["size"] > $maxSize) {
                $errorMessage = 'Eroare: Fișierul este prea mare (maxim 2MB).';
            } else {
                // Generăm un nume unic pentru a evita conflictele de cache
                $newFileName = uniqid('bg_', true) . '.' . $fileType;
                $targetFile = $targetDir . $newFileName;

                if (move_uploaded_file($_FILES["login_background"]["tmp_name"], $targetFile)) {
                    try {
                        // Salvăm calea în DB folosind UPSERT
                        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('login_background_image', ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$targetFile]);
                        $successMessage = 'Imaginea de fundal a fost actualizată cu succes!';
                    } catch (PDOException $e) { 
                        $errorMessage = 'Eroare la salvarea în baza de date: ' . $e->getMessage(); 
                    }
                } else {
                    $errorMessage = 'Eroare la mutarea fișierului pe server.';
                }
            }
        } else {
            $errorMessage = 'Nu a fost selectat niciun fișier valid.';
        }
    }

    // --- 3. Adaugare Structura ---
    if (isset($_POST['add_structura'])) {
        $denumire = trim($_POST['structura_denumire']);
        $prescurtare = trim($_POST['structura_prescurtare']);
        if (!empty($denumire) && !empty($prescurtare)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO structuri (Structura, prescurt) VALUES (?, ?)");
                $stmt->execute([$denumire, $prescurtare]);
                $successMessage = 'Structura a fost adăugată!';
            } catch (PDOException $e) { $errorMessage = 'Eroare SQL (posibil duplicat).'; }
        }
    }
    
    // --- 4. Editare Structura ---
    if (isset($_POST['edit_structura'])) {
        $id = filter_input(INPUT_POST, 'edit_structura_id', FILTER_VALIDATE_INT);
        $denumire = trim($_POST['edit_structura_denumire']);
        $prescurtare = trim($_POST['edit_structura_prescurtare']);
        if ($id) {
            try {
                $stmt = $pdo->prepare("UPDATE structuri SET Structura = ?, prescurt = ? WHERE id_struct = ?");
                $stmt->execute([$denumire, $prescurtare, $id]);
                $successMessage = 'Structura a fost actualizată!';
            } catch (PDOException $e) { $errorMessage = 'Eroare la actualizare.'; }
        }
    }

    // --- 5. Adaugare Substructura ---
    if (isset($_POST['add_substructura'])) {
        $id_struct = filter_input(INPUT_POST, 'id_struct', FILTER_VALIDATE_INT);
        $denumire = trim($_POST['substructura_denumire']);
        $prescurtare = trim($_POST['substructura_prescurtare']);
        if ($id_struct && !empty($denumire)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO substructuri (id_struct, denumire, prescurt) VALUES (?, ?, ?)");
                $stmt->execute([$id_struct, $denumire, $prescurtare]);
                $successMessage = 'Substructura a fost adăugată!';
            } catch (PDOException $e) { $errorMessage = 'Eroare la adăugare substructură.'; }
        }
    }

    // --- 6. Editare Substructura ---
    if (isset($_POST['edit_substructura'])) {
        $id = filter_input(INPUT_POST, 'edit_substructura_id', FILTER_VALIDATE_INT);
        $id_struct = filter_input(INPUT_POST, 'edit_substructura_id_struct', FILTER_VALIDATE_INT);
        $denumire = trim($_POST['edit_substructura_denumire']);
        $prescurtare = trim($_POST['edit_substructura_prescurtare']);

        if ($id && $id_struct) {
            try {
                $stmt = $pdo->prepare("UPDATE substructuri SET id_struct = ?, denumire = ?, prescurt = ? WHERE id_substr = ?");
                $stmt->execute([$id_struct, $denumire, $prescurtare, $id]);
                $successMessage = 'Substructura a fost actualizată!';
            } catch (PDOException $e) { $errorMessage = 'Eroare la actualizare substructură.'; }
        }
    }
}


// ==========================================================================
// PRELUARE DATE PENTRU AFISARE
// ==========================================================================
try {
    // Preluam Titlu
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'dashboard_title'");
    $stmt->execute();
    $row = $stmt->fetch();
    $currentTitle = $row ? $row['setting_value'] : ''; // Default gol daca nu exista

    // Preluam Liste
    $structuri = $pdo->query("SELECT id_struct, Structura AS denumire, prescurt FROM structuri ORDER BY id_struct")->fetchAll();
    $substructuri = $pdo->query("SELECT s.id_substr, s.denumire, s.prescurt, s.id_struct, st.prescurt AS structura_prescurt 
                                  FROM substructuri s 
                                  JOIN structuri st ON s.id_struct = st.id_struct 
                                  ORDER BY st.prescurt, s.denumire")->fetchAll();

} catch (PDOException $e) {
    $currentTitle = 'Eroare DB';
    $structuri = [];
    $substructuri = [];
}
?>

<h1 class="h2">Setări Aplicație</h1>
<p class="text-muted">Modificați setările globale și adăugați elemente noi.</p>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card content-card h-100">
            <div class="card-body">
                <h5 class="card-title">Setări Generale</h5>
                <form action="settings.php" method="post">
                    <div class="mb-3">
                        <label for="dashboard_title" class="form-label">Numele Aplicației</label>
                        <input type="text" class="form-control" id="dashboard_title" name="dashboard_title" value="<?php echo htmlspecialchars($currentTitle); ?>" placeholder="Ex: Portal Angajați" required>
                    </div>
                    <button type="submit" name="save_title" class="btn btn-primary">Salvează Numele</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card content-card h-100">
            <div class="card-body">
                <h5 class="card-title">Imagine de Fundal Login</h5>
                <form action="settings.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="login_background" class="form-label">Încărcați o imagine nouă (Max 2MB)</label>
                        <input class="form-control" type="file" id="login_background" name="login_background" accept="image/png, image/jpeg" required>
                    </div>
                    <button type="submit" name="upload_image" class="btn btn-primary">Încarcă Imaginea</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card content-card h-100">
            <div class="card-body">
                <h5 class="card-title">Adaugă Structură Nouă</h5>
                <form action="settings.php" method="post">
                    <div class="mb-3">
                        <label for="structura_denumire" class="form-label">Denumire Structură</label>
                        <input type="text" class="form-control" id="structura_denumire" name="structura_denumire" required>
                    </div>
                    <div class="mb-3">
                        <label for="structura_prescurtare" class="form-label">Prescurtare</label>
                        <input type="text" class="form-control" id="structura_prescurtare" name="structura_prescurtare" required>
                    </div>
                    <button type="submit" name="add_structura" class="btn btn-success">Adaugă Structura</button>
                </form>
                <hr>
                <h5 class="card-title mt-4">Structuri Existente</h5>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>ID</th><th>Denumire</th><th>Prescurtare</th><th>Acțiuni</th></tr></thead>
                        <tbody>
                            <?php foreach($structuri as $item): ?>
                            <tr>
                                <td><?php echo $item['id_struct']; ?></td>
                                <td><?php echo htmlspecialchars($item['denumire']); ?></td>
                                <td><?php echo htmlspecialchars($item['prescurt']); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editStructuraModal"
                                            data-id="<?php echo $item['id_struct']; ?>"
                                            data-denumire="<?php echo htmlspecialchars($item['denumire']); ?>"
                                            data-prescurtare="<?php echo htmlspecialchars($item['prescurt']); ?>">
                                        Edit
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
            <div class="card-body">
                 <h5 class="card-title">Adaugă Substructură Nouă</h5>
                <form action="settings.php" method="post">
                     <div class="mb-3">
                        <label for="id_struct_sub" class="form-label">Alege Structura Părinte</label>
                        <select class="form-select" id="id_struct_sub" name="id_struct" required>
                            <option value="" disabled selected>Selectați o structură...</option>
                            <?php foreach ($structuri as $structura): ?>
                                <option value="<?php echo $structura['id_struct']; ?>"><?php echo htmlspecialchars($structura['denumire']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="substructura_denumire" class="form-label">Denumire Substructură</label>
                        <input type="text" class="form-control" id="substructura_denumire" name="substructura_denumire" required>
                    </div>
                    <div class="mb-3">
                        <label for="substructura_prescurtare" class="form-label">Prescurtare (Opțional)</label>
                        <input type="text" class="form-control" id="substructura_prescurtare" name="substructura_prescurtare">
                    </div>
                    <button type="submit" name="add_substructura" class="btn btn-success">Adaugă Substructura</button>
                </form>
                <hr>
                <h5 class="card-title mt-4">Substructuri Existente</h5>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-striped">
                         <thead><tr><th>ID</th><th>Structura</th><th>Denumire</th><th>Prescurtare</th><th>Acțiuni</th></tr></thead>
                        <tbody>
                            <?php foreach($substructuri as $item): ?>
                            <tr>
                                <td><?php echo $item['id_substr']; ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($item['structura_prescurt']); ?></small></td>
                                <td><?php echo htmlspecialchars($item['denumire']); ?></td>
                                <td><?php echo htmlspecialchars($item['prescurt']); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editSubstructuraModal"
                                            data-id="<?php echo $item['id_substr']; ?>"
                                            data-id-struct="<?php echo $item['id_struct']; ?>"
                                            data-denumire="<?php echo htmlspecialchars($item['denumire']); ?>"
                                            data-prescurtare="<?php echo htmlspecialchars($item['prescurt']); ?>">
                                        Edit
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

<div class="modal fade" id="editStructuraModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editare Structură</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="settings.php" method="post">
        <div class="modal-body">
            <input type="hidden" name="edit_structura_id" id="edit_structura_id">
            <div class="mb-3">
                <label for="edit_structura_denumire" class="form-label">Denumire</label>
                <input type="text" class="form-control" name="edit_structura_denumire" id="edit_structura_denumire" required>
            </div>
            <div class="mb-3">
                <label for="edit_structura_prescurtare" class="form-label">Prescurtare</label>
                <input type="text" class="form-control" name="edit_structura_prescurtare" id="edit_structura_prescurtare" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
          <button type="submit" name="edit_structura" class="btn btn-primary">Salvează</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editSubstructuraModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editare Substructură</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="settings.php" method="post">
        <div class="modal-body">
            <input type="hidden" name="edit_substructura_id" id="edit_substructura_id">
            <div class="mb-3">
                <label for="edit_substructura_id_struct" class="form-label">Structura Părinte</label>
                <select class="form-select" name="edit_substructura_id_struct" id="edit_substructura_id_struct" required>
                    <?php foreach ($structuri as $structura): ?>
                        <option value="<?php echo $structura['id_struct']; ?>"><?php echo htmlspecialchars($structura['denumire']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="edit_substructura_denumire" class="form-label">Denumire</label>
                <input type="text" class="form-control" name="edit_substructura_denumire" id="edit_substructura_denumire" required>
            </div>
            <div class="mb-3">
                <label for="edit_substructura_prescurtare" class="form-label">Prescurtare (Opțional)</label>
                <input type="text" class="form-control" name="edit_substructura_prescurtare" id="edit_substructura_prescurtare">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
          <button type="submit" name="edit_substructura" class="btn btn-primary">Salvează</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/dashboard_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Script pentru Modal Editare Structura
    const editStructuraModal = document.getElementById('editStructuraModal');
    if(editStructuraModal) {
        editStructuraModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            // Preluam datele din atributele butonului
            const id = button.getAttribute('data-id');
            const denumire = button.getAttribute('data-denumire');
            const prescurtare = button.getAttribute('data-prescurtare');

            // Le punem in input-urile din modal
            editStructuraModal.querySelector('#edit_structura_id').value = id;
            editStructuraModal.querySelector('#edit_structura_denumire').value = denumire;
            editStructuraModal.querySelector('#edit_structura_prescurtare').value = prescurtare;
        });
    }

    // 2. Script pentru Modal Editare Substructura
    const editSubstructuraModal = document.getElementById('editSubstructuraModal');
    if(editSubstructuraModal) {
        editSubstructuraModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const id_struct = button.getAttribute('data-id-struct');
            const denumire = button.getAttribute('data-denumire');
            const prescurtare = button.getAttribute('data-prescurtare');

            editSubstructuraModal.querySelector('#edit_substructura_id').value = id;
            editSubstructuraModal.querySelector('#edit_substructura_id_struct').value = id_struct; // Selecteaza automat parintele
            editSubstructuraModal.querySelector('#edit_substructura_denumire').value = denumire;
            editSubstructuraModal.querySelector('#edit_substructura_prescurtare').value = prescurtare;
        });
    }
});
</script>