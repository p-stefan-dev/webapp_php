<?php
// osd/save_osd_interventie.php

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// 1. Preluare date de control
$data_curenta = $_POST['data_curenta'] ?? '';
$sectiune_id = filter_input(INPUT_POST, 'sectiune_id', FILTER_VALIDATE_INT);
$id_struct_owner = filter_input(INPUT_POST, 'id_struct_owner', FILTER_VALIDATE_INT);

// Datele efective din formular (Structura: date[ID_SUBSTR][field] = value)
$date_post = $_POST['date'] ?? [];

// 2. Validări de bază
if (empty($data_curenta) || empty($sectiune_id)) {
    die("Eroare: Date incomplete.");
}

// 3. Verificare Drepturi (Admin sau Proprietar Structură Părinte)
$user_id_struct = $_SESSION['id_struct'] ?? 0;
$user_rol = $_SESSION['rol'] ?? 0;

$can_edit = ($user_rol == 1) || ($user_id_struct == $id_struct_owner);

if (!$can_edit) {
    die("Acces interzis: Nu aveți drepturi pentru această secțiune.");
}

try {
    $pdo->beginTransaction();

    // 4. Procesarea fiecărei substructuri (Gărzi)
    // Array-ul $date_post are cheile egale cu id_substr (ex: 16 => [...date...])
    foreach ($date_post as $id_substr => $valori) {
        
        // Sanitizează ID-ul substructurii
        $id_substr = (int)$id_substr;
        
        // Dacă ID-ul e 0 sau invalid, sărim peste el
        if ($id_substr <= 0) continue;

        // Transformăm array-ul de valori în JSON pentru stocare
        $json_date = json_encode($valori, JSON_UNESCAPED_UNICODE);

        // 5. Inserare sau Actualizare (ON DUPLICATE KEY UPDATE)
        // Aici folosim id_substr preluat din cheia array-ului, nu din sesiune!
        $sql = "INSERT INTO osd_rapoarte_interventie 
                (data, sectiune_id, id_substr, json_date, data_actualizare) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                json_date = VALUES(json_date), 
                data_actualizare = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data_curenta, $sectiune_id, $id_substr, $json_date]);
    }

    $pdo->commit();

    // 6. Redirecționare înapoi cu succes
    header("Location: registru_osd.php?data=$data_curenta&success=1");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Eroare la salvare: " . $e->getMessage());
}
?>