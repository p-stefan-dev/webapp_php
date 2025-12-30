<?php
session_start();
require_once __DIR__ . '/DataSource.php';
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';

use Phppot\DataSource;
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

$database = new DataSource();

// Funcția de export
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    try {
        $substante = $database->select("
            SELECT s.nr_onu, s.denumire_substanta, s.id_fisa 
            FROM substante s
            ORDER BY s.nr_onu ASC
        ");
        
        $fise = $database->select("SELECT id_fisa, masuri_psi, altele, nume_fisa FROM fise");

        $xlsx = new SimpleXLSXGen();
        
        // Sheet Substante
        $sheetSubstante = [['Nr. ONU', 'Denumire Substanta', 'ID Fisa']];
        foreach ($substante as $row) {
            $sheetSubstante[] = [$row['nr_onu'], $row['denumire_substanta'], $row['id_fisa']];
        }
        $xlsx->addSheet($sheetSubstante, 'Substante');
        
        // Sheet Fise
        $sheetFise = [['ID Fisa', 'Masuri PSI', 'Altele', 'Nume Fisa']];
        foreach ($fise as $row) {
            $sheetFise[] = [$row['id_fisa'], $row['masuri_psi'], $row['altele'], $row['nume_fisa']];
        }
        $xlsx->addSheet($sheetFise, 'Fise');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export_adr_' . date('Y-m-d') . '.xlsx"');
        $xlsx->saveAs('php://output');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Eroare export: " . $e->getMessage();
        header("Location: import_adr.php");
        exit();
    }
}

// Funcția de import cu suprascriere pentru ambele tabele
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fisier_excel'])) {
    $file = $_FILES['fisier_excel'];
    
    try {
        // 1. Validare fișier
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Eroare la încărcare fișier.");
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("Fișierul depășește dimensiunea maximă de 5MB");
        }
        
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'xlsx') {
            throw new Exception("Doar fișiere .xlsx sunt acceptate");
        }

        // 2. Procesare Excel
        $xlsx = SimpleXLSX::parse($file['tmp_name']);
        if (!$xlsx) {
            throw new Exception("Eroare la parsare Excel: " . SimpleXLSX::parseError());
        }

        // 3. Verificare sheet-uri
        $sheetNames = $xlsx->sheetNames();
        if (!in_array('Substante', $sheetNames) || !in_array('Fise', $sheetNames)) {
            throw new Exception("Fișierul Excel trebuie să conțină sheet-urile 'Substante' și 'Fise'");
        }

        // 4. Început tranzacție
        $conn = $database->getConnection();
        $conn->begin_transaction();

        // =============================================
        // 5. Procesare Fise (cu suprascriere)
        // =============================================
        $fiseIndex = array_search('Fise', $sheetNames);
        $fiseRows = $xlsx->rows($fiseIndex);
        $fiseHeader = array_shift($fiseRows);
        
        $fiseMap = [
            'id_fisa' => array_search('ID Fisa', $fiseHeader),
            'masuri_psi' => array_search('Masuri PSI', $fiseHeader),
            'altele' => array_search('Altele', $fiseHeader),
            'nume_fisa' => array_search('Nume Fisa', $fiseHeader)
        ];
        
        if ($fiseMap['id_fisa'] === false) {
            throw new Exception("Sheet-ul 'Fise' are coloane lipsă sau denumiri incorecte");
        }

        foreach ($fiseRows as $index => $row) {
            $id_fisa = trim($row[$fiseMap['id_fisa']]);
            if (empty($id_fisa)) {
                continue;
            }
            
            $existing = $database->select("SELECT id FROM fise WHERE id_fisa = ?", "s", [$id_fisa]);
            if (!empty($existing)) {
                $database->update(
                    "UPDATE fise SET masuri_psi = ?, altele = ?, nume_fisa = ? WHERE id_fisa = ?",
                    "ssss",
                    [
                        $row[$fiseMap['masuri_psi']],
                        $row[$fiseMap['altele']],
                        $row[$fiseMap['nume_fisa']],
                        $id_fisa
                    ]
                );
            } else {
                $database->insert(
                    "INSERT INTO fise (id_fisa, masuri_psi, altele, nume_fisa) VALUES (?, ?, ?, ?)",
                    "ssss",
                    [
                        $id_fisa,
                        $row[$fiseMap['masuri_psi']],
                        $row[$fiseMap['altele']],
                        $row[$fiseMap['nume_fisa']]
                    ]
                );
            }
        }

        // =============================================
        // 6. Procesare Substante (cu suprascriere)
        // =============================================
        $substanteIndex = array_search('Substante', $sheetNames);
        $substanteRows = $xlsx->rows($substanteIndex);
        $substanteHeader = array_shift($substanteRows);

        $substanteMap = [
            'nr_onu' => array_search('Nr. ONU', $substanteHeader),
            'denumire' => array_search('Denumire Substanta', $substanteHeader),
            'id_fisa' => array_search('ID Fisa', $substanteHeader)
        ];

        if ($substanteMap['nr_onu'] === false || $substanteMap['denumire'] === false) {
            throw new Exception("Sheet-ul 'Substante' trebuie să conțină coloanele 'Nr. ONU' și 'Denumire Substanta'");
        }

        foreach ($substanteRows as $index => $row) {
            $nr_onu = trim($row[$substanteMap['nr_onu']]);
            $denumire = trim($row[$substanteMap['denumire']]);
            $id_fisa = ($substanteMap['id_fisa'] !== false) ? trim($row[$substanteMap['id_fisa']]) : null;

            if (empty($nr_onu)) {
                continue;
            }

            $existing = $database->select("SELECT id FROM substante WHERE nr_onu = ?", "s", [$nr_onu]);
            
            if (!empty($existing)) {
                $database->update(
                    "UPDATE substante SET denumire_substanta = ?, id_fisa = ? WHERE nr_onu = ?",
                    "sss",
                    [$denumire, $id_fisa, $nr_onu]
                );
            } else {
                $database->insert(
                    "INSERT INTO substante (nr_onu, denumire_substanta, id_fisa) VALUES (?, ?, ?)",
                    "sss",
                    [$nr_onu, $denumire, $id_fisa]
                );
            }
        }

        // 7. Confirmă tranzacția
        $conn->commit();
        $_SESSION['flash_message'] = "Import realizat cu succes!";
        
    } catch (Exception $e) {
        // 8. Anulează tranzacția în caz de eroare
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }
        $_SESSION['flash_error'] = "Eroare import: " . $e->getMessage();
    }
    
    header("Location: import_adr.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export ADR</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            background-color: #121212;
            color: #e0e0e0;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 40px auto;
            padding: 30px;
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        h1, h2 {
            text-align: center;
            color: #c0392b;
        }
        h1 {
            margin-bottom: 30px;
        }
        h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .panel {
            margin-bottom: 30px;
        }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            border: 1px solid transparent;
            font-weight: bold;
        }
        .alert-success { 
            background-color: #2e7d32; 
            color: #fff;
            border-color: #388e3c;
        }
        .alert-error { 
            background-color: #b52e2e; 
            color: #fff;
            border-color: #d32f2f;
        }
        .form-group { 
            margin-bottom: 20px; 
            text-align: center;
        }
        .btn { 
            padding: 12px 25px; 
            background-color: #007bff; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-export {
            background-color: #17a2b8;
        }
        .btn-export:hover {
            background-color: #117a8b;
        }
        .btn-import {
            background-color: #28a745;
        }
        .btn-import:hover {
            background-color: #1e7e34;
        }
        input[type="file"] {
            color: #ccc;
        }
        /* Stil pentru a face inputul de fișier mai vizibil */
        input[type="file"]::file-selector-button {
            background-color: #444;
            color: #fff;
            border: 1px solid #666;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestionare Date ADR</h1>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="panel">
            <h2>Export Date</h2>
            <p style="text-align: center;">Exportă toate substanțele și fișele de pericol într-un fișier .xlsx.</p>
            <div style="text-align: center;">
                <a href="?action=export" class="btn btn-export">Exportă în Excel</a>
            </div>
        </div>

        <div class="panel">
            <h2>Import Date</h2>
            <p style="text-align: center;">Încarcă un fișier .xlsx pentru a adăuga sau suprascrie datele. Fișierul trebuie să conțină sheet-urile 'Substante' și 'Fise'.</p>
            <form method="post" enctype="multipart/form-data" style="text-align: center;">
                <div class="form-group">
                    <input type="file" name="fisier_excel" accept=".xlsx" required>
                </div>
                <button type="submit" class="btn btn-import">Importă din Excel</button>
            </form>
        </div>
    </div>
</body>
</html>
