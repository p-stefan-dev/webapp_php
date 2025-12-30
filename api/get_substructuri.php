<?php
// api/get_substructuri.php
require_once '../config/db.php';

header('Content-Type: application/json');

$id_struct = filter_input(INPUT_GET, 'id_struct', FILTER_VALIDATE_INT);

if (!$id_struct) {
    echo json_encode([]);
    exit;
}

try {
    // Selectăm id-ul și denumirea. Asigură-te că în baza de date coloana se numește 'denumire'
    // Dacă în baza de date se numește 'nume_substr' sau altfel, modifică aici:
    $stmt = $pdo->prepare("SELECT id_substr, denumire FROM substructuri WHERE id_struct = ? ORDER BY denumire ASC");
    $stmt->execute([$id_struct]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>