<?php
// api/search_tehnica.php
error_reporting(0); // Oprim erorile vizibile pentru producție
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$id_struct = isset($_GET['id_struct']) ? (int)$_GET['id_struct'] : 0;

if ($id_struct <= 0) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $pdo->exec("SET NAMES 'utf8mb4'");

    // REINTRODUCEM JOIN-UL PENTRU A LUA PRESCURTAREA SUBSTRUCTURII
    // Folosim parametrii unici :term1, :term2, :term3 pentru a evita eroarea SQL
    $sql = "SELECT t.id, t.codif_teh, t.nr_inmatriculare, t.denumire, s.prescurt
            FROM tehnica t
            LEFT JOIN substructuri s ON t.id_substr = s.id_substr
            WHERE t.id_struct = :id_struct 
            AND (
                t.codif_teh LIKE :term1 OR 
                t.nr_inmatriculare LIKE :term2 OR
                t.denumire LIKE :term3
            )
            ORDER BY t.codif_teh ASC, t.denumire ASC
            LIMIT 50";

    $stmt = $pdo->prepare($sql);
    
    $wildcard_term = "%$term%";
    
    $stmt->execute([
        ':id_struct' => $id_struct,
        ':term1' => $wildcard_term,
        ':term2' => $wildcard_term,
        ':term3' => $wildcard_term
    ]);
    
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // CONSTRUIRE FORMAT: Cod - Nr - Substructura
        $parti = [];
        
        // 1. Cod sau Denumire (dacă nu are cod)
        $parti[] = !empty($row['codif_teh']) ? $row['codif_teh'] : $row['denumire'];
        
        // 2. Număr înmatriculare
        $parti[] = !empty($row['nr_inmatriculare']) ? $row['nr_inmatriculare'] : 'Fără Nr';
        
        // 3. Substructura (Prescurtare)
        // Dacă nu găsim prescurtarea, punem un minus
        $parti[] = !empty($row['prescurt']) ? $row['prescurt'] : '-';

        $results[] = [
            'id' => $row['id'],
            'text' => implode(' - ', $parti)
        ];
    }

    echo json_encode(['results' => $results]);

} catch (Exception $e) {
    // Returnăm array gol în caz de eroare, ca să nu strice interfața
    echo json_encode(['results' => []]);
}
?>