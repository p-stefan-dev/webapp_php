// 1. Luăm toate secțiunile din Configurare (1-10)
$config = $pdo->query("SELECT * FROM osd_configuratie...")->fetchAll();

// 2. Luăm datele salvate pentru ziua respectivă
$rapoarte = $pdo->query("SELECT * FROM osd_rapoarte_interventie WHERE data = '$data_aleasa'")->fetchAll();

// 3. Afișăm
foreach ($config as $sectiune) {
    // Găsim datele pentru această secțiune
    $json_raw = ... (căutăm în $rapoarte unde sectiune_id = $sectiune['id']);
    $date_sectiune = json_decode($json_raw, true); // <--- AICI E "MAGIA", devine Array PHP normal
    
    // Acum afișăm simplu
    echo "<h1>" . $sectiune['titlu'] . "</h1>";
    foreach ($date_sectiune as $id_garda => $valori) {
        echo "Garda: " . $valori['total'] . " oameni";
        echo "Detalii: " . $valori['detalii'];
    }
}