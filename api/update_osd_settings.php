<?php
// api/update_osd_settings.php
require_once '../config/db.php';
session_start();

// 1. Verificare securitate (Doar Adminii)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

// 2. Preluare date
$sectiune_id = filter_input(INPUT_POST, 'sectiune_id', FILTER_VALIDATE_INT);
$id_substr = filter_input(INPUT_POST, 'id_substr', FILTER_VALIDATE_INT);
$field = $_POST['field'] ?? ''; // 'functii', 'misiuni', 'tehnica'
$value = filter_input(INPUT_POST, 'value', FILTER_VALIDATE_INT); // 0 sau 1

if (!$sectiune_id || !$id_substr || !in_array($field, ['functii', 'misiuni', 'tehnica'])) {
    echo json_encode(['success' => false, 'message' => 'Date incomplete sau invalide']);
    exit;
}

try {
    // 3. Preluăm JSON-ul actual
    $stmt = $pdo->prepare("SELECT json_settings FROM osd_configuratie WHERE sectiune_id = ?");
    $stmt->execute([$sectiune_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $settings = [];
    if ($row && !empty($row['json_settings'])) {
        $settings = json_decode($row['json_settings'], true);
    }

    // 4. Actualizăm valoarea
    // Structura: $settings['substructuri'][ID_SUBSTR][FIELD] = 0 sau 1
    if (!isset($settings['substructuri'])) {
        $settings['substructuri'] = [];
    }
    if (!isset($settings['substructuri'][$id_substr])) {
        $settings['substructuri'][$id_substr] = [];
    }

    $settings['substructuri'][$id_substr][$field] = $value;

    // 5. Salvăm înapoi în DB
    $newJson = json_encode($settings);
    $update = $pdo->prepare("UPDATE osd_configuratie SET json_settings = ? WHERE sectiune_id = ?");
    $update->execute([$newJson, $sectiune_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>