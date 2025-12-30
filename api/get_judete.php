<?php
// api/get_judete.php
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8'); // Header corect

try {
    $pdo->exec("SET NAMES 'utf8mb4'"); // Fortare encoding

    // Atentie: Folosim 'nume_judet' conform noii structuri
    $stmt = $pdo->query("SELECT id, nume_judet FROM judete ORDER BY nume_judet ASC");
    $judete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Codare sigura pentru diacritice
    echo json_encode($judete, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("Eroare Judete: " . $e->getMessage());
    echo json_encode([]);
}
?>