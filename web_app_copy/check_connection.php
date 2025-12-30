<?php
// check_connection.php

// Setam header-ul pentru a returna JSON
header('Content-Type: application/json');

// Incercam sa ne conectam la baza de date
try {
    // Includem fisierul de configurare a bazei de date.
    // Folosim @ pentru a suprima warning-urile in caz ca fisierul nu poate fi inclus
    // (de ex. in timpul unei erori de server), astfel incat sa putem returna un JSON valid.
    @require_once 'config/db.php';

    // Daca variabila $pdo a fost creata cu succes in db.php, inseamna ca conexiunea a reusit.
    if (isset($pdo)) {
        // Putem face o interogare simpla pentru a fi si mai siguri
        $pdo->query('SELECT 1');
        echo json_encode(['status' => 'ok']);
    } else {
        // Daca db.php a fost inclus, dar $pdo nu este setat, e o problema de configurare
        throw new Exception('Variabila PDO nu este configurata.');
    }
} catch (Throwable $e) {
    // Orice exceptie (PDOException de la conexiune, sau Exception-ul nostru) va fi prinsa aici.
    // Setam un cod de status HTTP pentru a reflecta eroarea
    http_response_code(503); // Service Unavailable
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
