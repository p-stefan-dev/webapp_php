<?php
// config/db.php

// ==========================================================================
// 1. CONFIGURARE CALE PROIECT (BASE URL) - CRITIC PENTRU SUB-FOLDERE
// ==========================================================================

// Definește calea web a proiectului tău.
// Dacă proiectul tău este în: C:/xampp/htdocs/gestiune_isu/
// Atunci BASE_URL trebuie să fie: '/gestiune_isu/'
// Dacă proiectul este direct în rădăcină (htdocs), pune doar '/'

define('BASE_URL', '/'); // <--- MODIFICĂ AICI !!!


// ==========================================================================
// 2. SETĂRI BAZĂ DE DATE
// ==========================================================================

define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'db_webapp'); // Asigură-te că numele bazei e corect
define('DB_USER', 'root');
define('DB_PASS', '');

// Setari pentru DSN (Data Source Name)
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

// Optiuni pentru PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Afiseaza erorile ca exceptii
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Returneaza rezultatele ca array asociativ
    PDO::ATTR_EMULATE_PREPARES   => false,                // Dezactiveaza emularea instructiunilor preparate
];

try {
    // Crearea instantei PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // În caz de eroare fatală la conexiune, oprim tot și afișăm un mesaj clar
    die("Eroare critică de conectare la baza de date: " . $e->getMessage());
}

// Conexiunea este acum disponibila in variabila $pdo
?>