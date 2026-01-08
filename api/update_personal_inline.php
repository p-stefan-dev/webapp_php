<?php
// api/update_personal_inline.php
require_once '../config/db.php';
session_start();

// 1. Verificare securitate (Doar Adminii au voie)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

// 2. Preluare date
$id = $_POST['id'] ?? null;
$field = $_POST['field'] ?? null; // 'rol' sau 'activ'
$value = $_POST['value'] ?? null;

if (!$id || !$field || !isset($value)) {
    echo json_encode(['success' => false, 'message' => 'Date incomplete']);
    exit;
}

// 3. Validare câmpuri permise
if (!in_array($field, ['rol', 'activ'])) {
    echo json_encode(['success' => false, 'message' => 'Câmp invalid']);
    exit;
}

try {
    // 4. Actualizare în baza de date
    // Construim query-ul dinamic dar sigur
    $sql = "UPDATE personal SET $field = :val WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':val' => $value,
        ':id'  => $id
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>