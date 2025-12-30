<?php
// import_personal.php
$pageTitle = 'Import Personal';
require_once 'includes/dashboard_header.php';
require_once 'lib/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$message = '';
$error = '';
$updatedRows = 0;
$insertedRows = 0; // Contor nou pentru inserări
$skippedRows = [];

if (isset($_FILES['excelFile'])) {
    if ($_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['excelFile']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($filePath)) {
            $pdo->beginTransaction();
            try {
                $header = $xlsx->rows()[0];
                $headerMap = array_flip($header);

                // --- 1. DEFINIREA COLOANELOR ---
                // Adăugăm și noile câmpuri din structura DB (username, rol, activ, etc.)
                // Dacă nu există în Excel, le vom gestiona cu valori default la INSERT
                $columnsToProcess = [
                    'nume', 'prenume', 'email', 'telefon', 'username',
                    'judet', 'uat', 'localitate', 
                    'tip_serviciu', 'id_grad', 'id_struct', 'id_substr', 
                    'clasa', 'activ', 'rol', 'curs_smurd'
                ];
                
                // Verificăm doar coloanele critice. ID-ul este opțional la inserare, dar necesar la update.
                // Totuși, ca să nu crape scriptul, verificăm dacă există coloana 'id' în header, chiar dacă e goală pe rânduri.
                if (!isset($headerMap['id'])) {
                     throw new Exception("Coloana 'id' lipsește din fișierul Excel. Chiar dacă e goală pentru noii angajați, coloana trebuie să existe în antet.");
                }

                // --- 2. PREGĂTIRE SQL UPDATE ---
                // Construim dinamic setarea parametrilor: nume=:nume, prenume=:prenume...
                $updateSet = [];
                foreach ($columnsToProcess as $col) {
                    // Verificăm dacă coloana există în Excel înainte să o adăugăm la SQL
                    if (isset($headerMap[$col])) {
                        $updateSet[] = "$col = :$col";
                    }
                }
                $sql_update = "UPDATE personal SET " . implode(', ', $updateSet) . " WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);

                // --- 3. PREGĂTIRE SQL INSERT ---
                // Construim dinamic inserarea
                $insertCols = [];
                $insertVals = [];
                foreach ($columnsToProcess as $col) {
                    if (isset($headerMap[$col])) {
                        $insertCols[] = $col;
                        $insertVals[] = ":$col";
                    }
                }
                // Adăugăm data_add automat
                $sql_insert = "INSERT INTO personal (" . implode(', ', $insertCols) . ", data_add) VALUES (" . implode(', ', $insertVals) . ", NOW())";
                $stmt_insert = $pdo->prepare($sql_insert);


                // --- 4. ITERARE RÂNDURI ---
                foreach ($xlsx->rows() as $r => $row) {
                    if ($r === 0) continue; // Skip header

                    // Preluăm ID-ul
                    $id = isset($row[$headerMap['id']]) ? trim($row[$headerMap['id']]) : '';
                    
                    // Preluăm valorile pentru parametri
                    $params = [];
                    foreach ($columnsToProcess as $col) {
                        if (isset($headerMap[$col])) {
                            $val = $row[$headerMap[$col]];
                            
                            // Logica pentru câmpuri goale
                            if ($val === '') {
                                // Pentru câmpuri numerice sau flag-uri, punem default dacă e gol
                                if (in_array($col, ['rol'])) $val = 4; // Default User
                                elseif (in_array($col, ['activ'])) $val = 1; // Default Activ
                                elseif (in_array($col, ['curs_smurd'])) $val = 0;
                                elseif (in_array($col, ['id_substr'])) $val = 0; // Fara substructura
                                else $val = null; // Pentru restul (text), NULL
                            }
                            $params[":$col"] = $val;
                        }
                    }

                    // --- LOGICA DE DECIZIE (UPDATE vs INSERT) ---
                    if (!empty($id) && is_numeric($id)) {
                        // --> CAZ 1: ID EXISTĂ -> UPDATE
                        // Verificăm întâi dacă ID-ul există fizic în baza de date (ca să nu încercăm update pe un ID șters/inexistent)
                        $checkStmt = $pdo->prepare("SELECT id FROM personal WHERE id = ?");
                        $checkStmt->execute([$id]);
                        
                        if ($checkStmt->fetch()) {
                            // ID-ul există, facem Update
                            $params[':id'] = $id;
                            $stmt_update->execute($params);
                            $updatedRows++;
                        } else {
                            // ID-ul e specificat (ex: 9999) dar nu există în baza de date.
                            // Îl inserăm ca nou (ignorăm ID-ul din excel și lăsăm auto-increment, sau îl forțăm).
                            // Aici aleg varianta sigură: INSERT NOU (auto-increment), ignorând ID-ul invalid din Excel.
                            $stmt_insert->execute($params);
                            $insertedRows++;
                        }
                    } else {
                        // --> CAZ 2: ID GOL -> INSERT
                        // Validare minimală pentru insert (Nume/Prenume obligatoriu)
                        if (empty($params[':nume']) || empty($params[':prenume'])) {
                             $skippedRows[] = "Rândul " . ($r + 1) . ": Sărit (Nume/Prenume lipsă pentru angajat nou).";
                             continue;
                        }
                        
                        $stmt_insert->execute($params);
                        $insertedRows++;
                    }
                }

                $pdo->commit();
                $message = "Procesare completă!<br>
                            <strong>Actualizate:</strong> $updatedRows<br>
                            <strong>Adăugate (Noi):</strong> $insertedRows";

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Eroare în timpul procesării: ' . $e->getMessage();
            }
        } else {
            $error = 'Eroare la parsarea fișierului XLSX: ' . SimpleXLSX::parseError();
        }
    } else {
        $error = 'Eroare la încărcarea fișierului: ' . $_FILES['excelFile']['error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h2">Import Mixt (Update & Insert)</h1>
        <p class="text-muted">Dacă rândul are <strong>ID</strong>, se actualizează. Dacă <strong>ID</strong> e gol, se creează angajat nou.</p>
    </div>
    <div>
        <a href="personal.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Înapoi la Listă
        </a>
    </div>
</div>

<div class="card content-card mt-4">
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($skippedRows)): ?>
            <div class="alert alert-warning">
                <strong>Atenție la următoarele rânduri:</strong>
                <ul>
                    <?php foreach ($skippedRows as $skipped): ?>
                        <li><?php echo htmlspecialchars($skipped); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="import_personal.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excelFile" class="form-label">Selectați fișierul Excel (.xlsx)</label>
                <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx" required>
                <div class="form-text">
                    <ul class="mb-0">
                        <li><strong>Pentru Update:</strong> Păstrați ID-ul din export.</li>
                        <li><strong>Pentru Adăugare:</strong> Lăsați coloana <code>id</code> goală în Excel.</li>
                        <li>Coloanele suportate: nume, prenume, email, telefon, username, judet, uat, localitate, tip_serviciu, id_grad, id_struct, id_substr, clasa, activ, rol, curs_smurd.</li>
                    </ul>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Importă Datele
            </button>
        </form>
    </div>
</div>

<?php
require_once 'includes/dashboard_footer.php';
?>