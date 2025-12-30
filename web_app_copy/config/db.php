<?php
// config/db.php

// Setari pentru conectarea la baza de date
// ATENTIE: Inlocuiti cu datele reale ale serverului MySQL.
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'db_webapp'); // Inlocuiti cu numele bazei de date
define('DB_USER', 'root'); // Inlocuiti cu utilizatorul bazei de date
define('DB_PASS', ''); // Inlocuiti cu parola

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
    // Aruncam exceptia mai departe pentru a fi prinsa de scriptul care include acest fisier
    throw $e;
}

// Conexiunea este acum disponibila in variabila $pdo
?>
