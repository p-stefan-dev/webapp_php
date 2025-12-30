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
$skippedRows = [];

if (isset($_FILES['excelFile'])) {
    if ($_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['excelFile']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($filePath)) {
            $pdo->beginTransaction();
            try {
                $header = $xlsx->rows()[0];
                $headerMap = array_flip($header);

                // Coloanele pe care dorim sa le actualizam
                $columnsToUpdate = [
                    'nume', 'prenume', 'telefon', 'judet', 'uat', 'localitate',
                    'tip_serviciu', 'id_grad', 'id_struct', 'id_substr', 'clasa'
                ];
                
                // Verificam daca toate coloanele necesare exista
                $requiredColumns = array_merge($columnsToUpdate, ['email']);
                foreach($requiredColumns as $col) {
                    if (!isset($headerMap[$col])) {
                        throw new Exception("Coloana necesară '$col' nu a fost găsită în fișierul Excel.");
                    }
                }

                $sql = "UPDATE personal SET 
                            nume = :nume, prenume = :prenume, telefon = :telefon, 
                            judet = :judet, uat = :uat, localitate = :localitate, 
                            tip_serviciu = :tip_serviciu, id_grad = :id_grad, 
                            id_struct = :id_struct, id_substr = :id_substr, clasa = :clasa
                        WHERE email = :email";

                $stmt = $pdo->prepare($sql);

                foreach ($xlsx->rows() as $r => $row) {
                    if ($r === 0) { // Sarim peste randul de antet
                        continue;
                    }

                    $email = trim($row[$headerMap['email']]);
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skippedRows[] = "Rândul " . ($r + 1) . ": Email invalid sau lipsă.";
                        continue;
                    }

                    $params = [':email' => $email];
                    foreach ($columnsToUpdate as $col) {
                        $params[':' . $col] = $row[$headerMap[$col]];
                    }

                    $stmt->execute($params);
                    $updatedRows += $stmt->rowCount();
                }

                $pdo->commit();
                $message = "Procesare finalizată. Au fost actualizate cu succes $updatedRows înregistrări.";

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Eroare în timpul procesării: ' . $e->getMessage();
            }
        } else {
            $error = 'Eroare la parsarea fișierului XLSX: ' . SimpleXLSX::parseError();
        }
    } else {
        $error = 'Eroare la încărcarea fișierului. Cod eroare: ' . $_FILES['excelFile']['error'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h2">Import Personal</h1>
        <p class="text-muted">Actualizați datele personalului folosind un fișier Excel (.xlsx).</p>
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
                <strong>Următoarele rânduri au fost sărite:</strong>
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
                    Fișierul trebuie să conțină coloanele: <strong>email</strong> (ca identificator unic) și coloanele pe care doriți să le actualizați (ex: nume, prenume, telefon, etc.).<br>
                    Este recomandat să folosiți un fișier generat prin funcția de <a href="export_personal.php">Export Excel</a>.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Încarcă și Actualizează
            </button>
        </form>
    </div>
</div>

<?php
require_once 'includes/dashboard_footer.php';
?>
