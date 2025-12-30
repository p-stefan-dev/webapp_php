<?php
// export_personal.php

// Includem conexiunea la baza de date si biblioteca SimpleXLSXGen
require_once 'config/db.php';
require_once 'lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// Opțional: Verificare drepturi (dacă ai sistemul de login activ pe sesiune)
/*
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    die("Acces restricționat.");
}
*/

try {
    // Preluam datele brute din tabela personal
    // MODIFICARE: Am adăugat 'username' și 'curs_smurd' în interogare
    $sql = "SELECT 
            id, id_old, nume, prenume, email, telefon, username,
            judet, uat, localitate, tip_serviciu, 
            id_grad, id_struct, id_substr, 
            clasa, activ, rol, curs_smurd
         FROM personal
         ORDER BY nume ASC, prenume ASC";
    
    $stmt = $pdo->query($sql);
    $dataRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definim antetul tabelului exact ca numele coloanelor din DB
    // MODIFICARE: Am adăugat coloanele noi și în antet
    $header = [
        'id', 'id_old', 'nume', 'prenume', 'email', 'telefon', 'username',
        'judet', 'uat', 'localitate', 'tip_serviciu',
        'id_grad', 'id_struct', 'id_substr',
        'clasa', 'activ', 'rol', 'curs_smurd'
    ];

    // Pregatim datele pentru Excel
    $excelData = [];
    $excelData[] = $header;

    // Adaugam randurile cu date brute
    foreach ($dataRows as $row) {
        // Asiguram ordinea corecta a coloanelor
        $orderedRow = [];
        foreach($header as $colName) {
            // Verificăm dacă cheia există, altfel punem null (evită erori de index)
            $orderedRow[] = isset($row[$colName]) ? $row[$colName] : '';
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

    // Curățăm buffer-ul de ieșire pentru a nu corupe fișierul Excel cu spații goale
    if (ob_get_length()) ob_clean();
    flush();

    $xlsx->saveAs('php://output');
    exit();

} catch (PDOException $e) {
    error_log("Eroare PDO la generarea exportului: " . $e->getMessage());
    die("A apărut o eroare la generarea fișierului Excel. Vă rugăm să încercați mai târziu.");
} catch (Exception $e) {
    error_log("Eroare generala la generarea exportului: " . $e->getMessage());
    die("A apărut o eroare neașteptată. Contactați administratorul.");
}
?>