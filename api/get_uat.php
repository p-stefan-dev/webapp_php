<?php
// api/get_uat.php
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8'); // Header corect

$id_judet = filter_input(INPUT_GET, 'id_judet', FILTER_VALIDATE_INT);

if ($id_judet) {
    try {
        $pdo->exec("SET NAMES 'utf8mb4'"); // Fortare encoding

        // Atentie: Folosim 'nume_uat' conform noii structuri
        $stmt = $pdo->prepare("SELECT id, nume_uat FROM uat WHERE id_judet = ? ORDER BY nume_uat ASC");
        
        $stmt->execute([$id_judet]);
        $rezultate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Codare sigura pentru diacritice
        echo json_encode($rezultate, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        error_log("Eroare UAT: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>