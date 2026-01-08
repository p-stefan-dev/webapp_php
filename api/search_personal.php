<?php
// api/search_personal.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$dbPath = '../config/db.php';
if (!file_exists($dbPath)) {
    echo json_encode(['results' => []]);
    exit;
}
require_once $dbPath;

$term = $_GET['term'] ?? '';

if (strlen($term) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    // 1. Forțăm UTF-8 pe conexiune (CRITIC pentru diacritice)
    $pdo->exec("SET NAMES 'utf8mb4'");

    // 2. Interogarea (folosim COALESCE ca să nu avem NULL în text)
    $sql = "SELECT p.id, 
                   CONCAT(
                       COALESCE(g.prescurt, ''), ' ', 
                       p.nume, ' ', 
                       p.prenume, ' - ', 
                       COALESCE(s.prescurt, '')
                   ) as text 
            FROM personal p
            LEFT JOIN grade g ON p.id_grad = g.id_grad
            LEFT JOIN structuri s ON p.id_struct = s.id_struct
            WHERE p.nume LIKE :term1 OR p.prenume LIKE :term2
            ORDER BY p.nume ASC 
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':term1' => "%$term%", ':term2' => "%$term%"]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Trimitem JSON cu opțiunea UNESCAPED_UNICODE
    // Asta face ca "Ștefan" să rămână "Ștefan", nu "\u0218tefan"
    echo json_encode(['results' => $data], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['results' => []]);
}
?>