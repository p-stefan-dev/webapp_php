<?php
// export_personal.php

// Includem conexiunea la baza de date si biblioteca SimpleXLSXGen
require_once 'config/db.php';
require_once 'lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

try {
    // Preluam datele brute din tabela personal
    $sql = "SELECT 
            id, id_old, nume, prenume, email, telefon, 
            judet, uat, localitate, tip_serviciu, 
            id_grad, id_struct, id_substr, 
            clasa, activ, rol
         FROM personal
         ORDER BY nume ASC, prenume ASC";
    
    $stmt = $pdo->query($sql);
    $dataRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definim antetul tabelului exact ca numele coloanelor din DB
    $header = [
        'id', 'id_old', 'nume', 'prenume', 'email', 'telefon',
        'judet', 'uat', 'localitate', 'tip_serviciu',
        'id_grad', 'id_struct', 'id_substr',
        'clasa', 'activ', 'rol'
    ];

    // Pregatim datele pentru Excel
    $excelData = [];
    $excelData[] = $header;

    // Adaugam randurile cu date brute
    foreach ($dataRows as $row) {
        // Asiguram ordinea corecta a coloanelor
        $orderedRow = [];
        foreach($header as $colName) {
            $orderedRow[] = $row[$colName];
        }
        $excelData[] = $orderedRow;
    }

    // Generam si trimitem fisierul Excel
    $xlsx = SimpleXLSXGen::fromArray($excelData, 'Personal');
    
    $filename = 'personal_export_' . date('Y-m-d_H-i') . '.xlsx';

    // Setam headerele pentru a forta descarcarea fisierului
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $xlsx->saveAs('php://output');
    exit();

} catch (PDOException $e) {
    // Logheaza eroarea sau afiseaza un mesaj prietenos
    error_log("Eroare PDO la generarea exportului: " . $e->getMessage());
    die("A apărut o eroare la generarea fișierului Excel. Vă rugăm să încercați mai târziu.");
} catch (Exception $e) {
    error_log("Eroare generala la generarea exportului: " . $e->getMessage());
    die("A apărut o eroare neașteptată. Contactați administratorul.");
}
