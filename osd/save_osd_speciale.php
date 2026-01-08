<?php
// osd/save_osd_speciale.php
require_once '../config/db.php';

if (session_status() == PHP_SESSION_NONE) session_start();

// 1. Verificare Autentificare
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 2. PRELUARE DATE ---
    $data_curenta = $_POST['data_curenta'] ?? '';
    
    // A. TOTALURI (Numerice)
    $total_go    = (int)($_POST['total_go'] ?? 0);
    $total_msud  = (int)($_POST['total_msud'] ?? 0);
    $total_cjcci = (int)($_POST['total_cjcci'] ?? 0);
    
    // B. DATE GRUPA OPERATIVĂ (Array de la Select2)
    // Structura: ['sef' => [ID, ...], 'emi' => [ID...]]
    $go_data = $_POST['go'] ?? []; 
    
    // C. DATE MSUD (Array structurat)
    // Structura: ['osd_sch1' => [ID], 'osd_sch2' => [ID]...]
    $msud_data = $_POST['msud'] ?? [];

    // D. DATE CJCCI (Array Complex)
    // Structura din formular vine ca: $_POST['cjcci']['selections'][...] si $_POST['cjcci']['obs']
    $cjcci_post = $_POST['cjcci'] ?? [];
    
    $cjcci_data = [
        'selections' => $cjcci_post['selections'] ?? [],
        'obs'        => trim($cjcci_post['obs'] ?? ''),
        // NOU: Salvăm starea checkbox-ului (1 sau 0)
        'same_as_osd' => isset($cjcci_post['same_as_osd']) ? 1 : 0
    ];

    // --- 3. VERIFICARE PERMISIUNI ---
    try {
        // Aflăm cine deține Secțiunea 1
        $stmtConfig = $pdo->prepare("SELECT id_struct FROM osd_configuratie WHERE sectiune_id = 1");
        $stmtConfig->execute();
        $owner_id = $stmtConfig->fetchColumn();

        $user_rol = $_SESSION['rol'] ?? 0;
        $user_struct = $_SESSION['id_struct'] ?? 0;

        // Adminul (1) sau membrul structurii responsabile poate salva
        if ($user_rol != 1 && $user_struct != $owner_id) {
            die("Eroare: Nu aveți permisiunea de a edita Secțiunea Specială (Grupa Operativă).");
        }

        // --- 4. ENCODARE JSON ---
        // Folosim JSON_UNESCAPED_UNICODE pentru a păstra diacriticele lizibile în baza de date
        $json_go    = json_encode($go_data, JSON_UNESCAPED_UNICODE);
        $json_msud  = json_encode($msud_data, JSON_UNESCAPED_UNICODE);
        $json_cjcci = json_encode($cjcci_data, JSON_UNESCAPED_UNICODE);

        // --- 5. SALVARE ÎN BAZA DE DATE (UPSERT) ---
        $sql = "INSERT INTO osd_rapoarte_speciale 
                (data, id_struct_responsabila, total_go, json_grup_operativ, total_msud, json_msud, total_cjcci, json_cjcci) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    id_struct_responsabila = VALUES(id_struct_responsabila),
                    total_go            = VALUES(total_go),
                    json_grup_operativ  = VALUES(json_grup_operativ),
                    total_msud          = VALUES(total_msud),
                    json_msud           = VALUES(json_msud),
                    total_cjcci         = VALUES(total_cjcci),
                    json_cjcci          = VALUES(json_cjcci)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data_curenta,
            $owner_id,
            $total_go,
            $json_go,
            $total_msud,
            $json_msud,
            $total_cjcci,
            $json_cjcci
        ]);

        // --- 6. REDIRECT ---
        header("Location: registru_osd.php?data=" . urlencode($data_curenta) . "&success=1");
        exit();

    } catch (PDOException $e) {
        die("Eroare Bază de Date: " . $e->getMessage());
    }

} else {
    // Acces direct fără POST
    header('Location: registru_osd.php');
    exit();
}
?>