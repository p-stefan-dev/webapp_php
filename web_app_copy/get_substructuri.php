<?php
// get_substructuri.php

require_once 'config/db.php';

// Setam header-ul pentru a returna JSON
header('Content-Type: application/json');

// Preluam id-ul structurii din cererea GET
$id_struct = isset($_GET['id_struct']) ? (int)$_GET['id_struct'] : 0;

if ($id_struct > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id_substr, denumire FROM substructuri WHERE id_struct = ? ORDER BY denumire");
        $stmt->execute([$id_struct]);
        $substructuri = $stmt->fetchAll();

        echo json_encode($substructuri);
    } catch (PDOException $e) {
        // Returnam un array gol in caz de eroare
        echo json_encode([]);
    }
} else {
    // Returnam un array gol daca nu s-a furnizat un id valid
    echo json_encode([]);
}
