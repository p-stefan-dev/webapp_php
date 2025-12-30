<?php
// api/get_localitati.php

// 1. Includem configurarea (care e corectă)
require_once '../config/db.php';

// 2. Spunem browserului că trimitem JSON UTF-8 (Foarte important!)
header('Content-Type: application/json; charset=utf-8');

$id_uat = filter_input(INPUT_GET, 'id_uat', FILTER_VALIDATE_INT);

if ($id_uat) {
    try {
        // 3. Forțăm încă o dată conexiunea pe UTF-8 (Măsură de siguranță)
        $pdo->exec("SET NAMES 'utf8mb4'");

        // 4. Selectăm coloana 'nume_localitate'
        $stmt = $pdo->prepare("SELECT id, nume_localitate FROM localitati WHERE id_uat = ? ORDER BY nume_localitate ASC");
        $stmt->execute([$id_uat]);
        
        $localitati = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Verificăm dacă lista e goală (DB nu a găsit nimic)
        if (!$localitati) {
            echo json_encode([]);
            exit;
        }

        // 6. Codăm JSON cu opțiunea JSON_UNESCAPED_UNICODE
        // Aceasta ajută la păstrarea diacriticelor (ș, ț, ă) nealterate
        $json = json_encode($localitati, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            // Dacă apare o eroare de codare, o trimitem ca să știm de ea
            echo json_encode(["error" => "Eroare JSON: " . json_last_error_msg()]);
        } else {
            echo $json;
        }

    } catch (PDOException $e) {
        // Logăm eroarea și returnăm array gol
        error_log("Eroare SQL: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>