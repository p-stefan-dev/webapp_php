<?php
// edit_personal.php

$pageTitle = 'Editare Personal';
require_once 'includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$person_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$person_id) {
    echo '<div class="alert alert-danger">ID invalid sau lipsă.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$return_url = $_REQUEST['return_url'] ?? 'personal.php'; 

$successMessage = '';
$errorMessage = '';

// ==========================================================================
// 1. LOGICA DE SALVARE A DATELOR (POST)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preluare si validare date
    $nume = trim($_POST['nume']);
    $prenume = trim($_POST['prenume']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    
    // Preluam ID-urile selectate
    $judet_id = filter_input(INPUT_POST, 'judet', FILTER_VALIDATE_INT);
    $uat_id = filter_input(INPUT_POST, 'uat', FILTER_VALIDATE_INT);
    $localitate_nume = trim($_POST['localitate']); // Aici vine numele, nu ID-ul

    $tip_serviciu = $_POST['tip_serviciu'];
    $id_grad = filter_input(INPUT_POST, 'id_grad', FILTER_VALIDATE_INT);
    $id_struct = filter_input(INPUT_POST, 'id_struct', FILTER_VALIDATE_INT);
    $id_substr = filter_input(INPUT_POST, 'id_substr', FILTER_VALIDATE_INT);
    
    if (empty($nume) || empty($prenume) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Numele, prenumele și email-ul sunt obligatorii și trebuie să fie valide.';
    } else {
        try {
            // --- FIX: Preluam numele corecte din DB folosind noile coloane ---
            $judet_nume = null;
            $uat_nume = null;

            if ($judet_id) {
                $judet_nume = $pdo->query("SELECT nume_judet FROM judete WHERE id = " . (int)$judet_id)->fetchColumn();
            }
            if ($uat_id) {
                $uat_nume = $pdo->query("SELECT nume_uat FROM uat WHERE id = " . (int)$uat_id)->fetchColumn();
            }
            // ---------------------------------------------------------------
            
            $sql = "UPDATE personal SET 
                        nume = ?, prenume = ?, email = ?, telefon = ?, 
                        judet = ?, uat = ?, localitate = ?, tip_serviciu = ?,
                        id_grad = ?, id_struct = ?, id_substr = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nume, $prenume, $email, $telefon,
                $judet_nume, $uat_nume, $localitate_nume, $tip_serviciu,
                $id_grad, $id_struct, ($id_substr == 0 ? null : $id_substr),
                $person_id
            ]);

            $_SESSION['success_message'] = 'Datele au fost actualizate cu succes!';
            header('Location: ' . $return_url);
            exit();

        } catch (PDOException $e) {
            $errorMessage = 'A apărut o eroare la salvarea datelor: ' . $e->getMessage();
        }
    }
}

// ==========================================================================
// 2. PRELUAREA DATELOR INITIALE (GET)
// ==========================================================================
try {
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch();

    if (!$person) {
        echo '<div class="alert alert-danger">Persoana nu a fost găsită.</div>';
        require_once 'includes/dashboard_footer.php';
        exit();
    }
    
    // --- FIX: Găsim ID-ul județului bazat pe nume_judet ---
    $person_judet_id = null;
    if (!empty($person['judet'])) {
        $stmt_jud = $pdo->prepare("SELECT id FROM judete WHERE nume_judet = ?");
        $stmt_jud->execute([$person['judet']]);
        $person_judet_id = $stmt_jud->fetchColumn();
    }

    // --- FIX: Găsim ID-ul UAT-ului bazat pe nume_uat ---
    $person_uat_id = null;
    if ($person_judet_id && !empty($person['uat'])) {
        $stmt_uat = $pdo->prepare("SELECT id FROM uat WHERE nume_uat = ? AND id_judet = ?");
        $stmt_uat->execute([$person['uat'], $person_judet_id]);
        $person_uat_id = $stmt_uat->fetchColumn();
    }
    // ----------------------------------------------------

    // Preluam listele pentru dropdown-uri statice
    $grade = $pdo->query("SELECT id_grad, nume_grad FROM grade ORDER BY id_grad")->fetchAll();
    $structuri = $pdo->query("SELECT id_struct, Structura AS denumire FROM structuri ORDER BY id_struct")->fetchAll();
    
    $substructuri = [];
    if ($person['id_struct']) {
         $stmt = $pdo->prepare("SELECT id_substr, denumire FROM substructuri WHERE id_struct = ? ORDER BY denumire");
         $stmt->execute([$person['id_struct']]);
         $substructuri = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    $errorMessage = 'Eroare la preluarea datelor.';
}
?>

<h1 class="h2">Editare Personal</h1>
<p class="text-muted">Modificați detaliile pentru: <strong><?php echo htmlspecialchars($person['nume'] . ' ' . $person['prenume']); ?></strong></p>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<form action="edit_personal.php?id=<?php echo $person_id; ?>&return_url=<?php echo htmlspecialchars($return_url); ?>" method="post" class="card content-card mt-4">
    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="id_grad" class="form-label">Grad</label>
                <select class="form-select" id="id_grad" name="id_grad" required>
                    <?php foreach($grade as $grad): ?>
                    <option value="<?php echo $grad['id_grad']; ?>" <?php if($grad['id_grad'] == $person['id_grad']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($grad['nume_grad']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="nume" class="form-label">Nume</label>
                <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($person['nume']); ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="prenume" class="form-label">Prenume</label>
                <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo htmlspecialchars($person['prenume']); ?>" required>
            </div>
             <div class="col-md-3 mb-3">
                <label for="tip_serviciu" class="form-label">Tip Serviciu</label>
                <select class="form-select" id="tip_serviciu" name="tip_serviciu">
                     <option value="" <?php if(empty($person['tip_serviciu'])) echo 'selected'; ?>>Nespecificat</option>
                     <option value="8ore" <?php if($person['tip_serviciu'] == '8ore') echo 'selected'; ?>>8 ore</option>
                     <option value="tura1" <?php if($person['tip_serviciu'] == 'tura1') echo 'selected'; ?>>Tura 1</option>
                     <option value="tura2" <?php if($person['tip_serviciu'] == 'tura2') echo 'selected'; ?>>Tura 2</option>
                     <option value="tura3" <?php if($person['tip_serviciu'] == 'tura3') echo 'selected'; ?>>Tura 3</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($person['email']); ?>" required>
            </div>
             <div class="col-md-6 mb-3">
                <label for="telefon" class="form-label">Telefon</label>
                <input type="text" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($person['telefon']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="id_struct" class="form-label">Structura</label>
                 <select class="form-select" id="id_struct" name="id_struct" required>
                    <?php foreach($structuri as $structura): ?>
                    <option value="<?php echo $structura['id_struct']; ?>" <?php if($structura['id_struct'] == $person['id_struct']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($structura['denumire']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="id_substr" class="form-label">Substructura</label>
                <select class="form-select" id="id_substr" name="id_substr">
                    <option value="0">Fără substructură</option>
                     <?php foreach ($substructuri as $substructura): ?>
                        <option value="<?php echo $substructura['id_substr']; ?>" <?php if($substructura['id_substr'] == $person['id_substr']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($substructura['denumire']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="judet" class="form-label">Județ</label>
                <select class="form-select" id="judet" name="judet"></select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="uat" class="form-label">UAT</label>
                <select class="form-select" id="uat" name="uat" disabled></select>
            </div>
             <div class="col-md-4 mb-3">
                <label for="localitate" class="form-label">Localitate</label>
                <select class="form-select" id="localitate" name="localitate" disabled></select>
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-primary">Salvează Modificările</button>
        <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-secondary">Anulează</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    console.log("✅ Script inițializat.");

    const judetSelect = document.getElementById('judet');
    const uatSelect = document.getElementById('uat');
    const localitateSelect = document.getElementById('localitate');

    const initialJudetId = '<?php echo $person_judet_id; ?>';
    const initialUatId = '<?php echo $person_uat_id; ?>';
    // Folosim addslashes pentru a evita erori JS dacă numele conține apostrof
    const initialLocalitateName = '<?php echo isset($person['localitate']) ? addslashes($person['localitate']) : ''; ?>';

    // 1. Funcție robustă pentru populare
    function populateSelect(selectElement, items, selectedValue, isNameValue = false) {
        selectElement.innerHTML = ''; 
        selectElement.add(new Option('-- Selectați --', ''));

        if (!items || items.length === 0) {
            console.warn(`⚠️ Nicio dată primită pentru ${selectElement.id}`);
            return;
        }

        console.log(`Populez ${selectElement.id} cu ${items.length} elemente.`);

        items.forEach(item => {
            let textDisplay = "Nespecificat";
            
            // Verificare explicită pentru noile denumiri de coloane
            if (item.nume_judet) textDisplay = item.nume_judet;
            else if (item.nume_uat) textDisplay = item.nume_uat;
            else if (item.nume_localitate) textDisplay = item.nume_localitate;
            else if (item.denumire) textDisplay = item.denumire;
            else if (item.nume) textDisplay = item.nume; // Fallback vechi

            // Dacă isNameValue e true, punem Textul ca valoare (pt Localități). Altfel ID-ul.
            const value = isNameValue ? textDisplay : item.id;
            
            const option = new Option(textDisplay, value);
            
            // Comparare sigură (string cu string)
            if (selectedValue && String(value).trim() === String(selectedValue).trim()) {
                option.selected = true;
            }
            selectElement.add(option);
        });
        
        selectElement.disabled = false;
    }

    // 2. Încarcă UAT-uri
    async function loadUat(judetId) {
        console.log("📡 Cerere UAT pentru Județ:", judetId);
        try {
            const response = await fetch(`api/get_uat.php?id_judet=${judetId}`);
            const data = await response.json();
            return data;
        } catch (e) {
            console.error("❌ Eroare la încărcarea UAT:", e);
            return [];
        }
    }

    // 3. Încarcă Localități
    async function loadLocalitati(uatId) {
        console.log("📡 Cerere Localități pentru UAT:", uatId);
        try {
            const response = await fetch(`api/get_localitati.php?id_uat=${uatId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            console.log("📥 Date Localități primite:", data); 
            return data;
        } catch (e) {
            console.error("❌ Eroare la încărcarea Localităților:", e);
            return [];
        }
    }

    // Event Listeners
    judetSelect.addEventListener('change', async function() {
        uatSelect.innerHTML = '<option value="">Se încarcă...</option>';
        localitateSelect.innerHTML = '<option value="">Alegeți UAT...</option>';
        uatSelect.disabled = true;
        localitateSelect.disabled = true;

        const uatData = await loadUat(this.value);
        populateSelect(uatSelect, uatData, null);
    });

    uatSelect.addEventListener('change', async function() {
        localitateSelect.innerHTML = '<option value="">Se încarcă...</option>';
        localitateSelect.disabled = true;
        
        const localitatiData = await loadLocalitati(this.value);
        // IMPORTANT: true la final pentru că salvăm NUMELE localității
        populateSelect(localitateSelect, localitatiData, null, true); 
    });

    // --- INITIALIZARE ---
    try {
        // 1. Incarcam judetele
        const judeteResponse = await fetch('api/get_judete.php');
        const judeteData = await judeteResponse.json();
        populateSelect(judetSelect, judeteData, initialJudetId);

        // 2. Daca exista un judet initial, incarcam UAT-urile
        if (initialJudetId) {
            const uatData = await loadUat(initialJudetId);
            populateSelect(uatSelect, uatData, initialUatId);
        }

        // 3. Daca exista un UAT initial, incarcam localitatile
        if (initialUatId) {
            const localitatiData = await loadLocalitati(initialUatId);
            populateSelect(localitateSelect, localitatiData, initialLocalitateName, true);
        }
    } catch (error) {
        console.error("A apărut o eroare la inițializare:", error);
    }
    
    // --- Logica pentru dropdown-ul de substructuri (neschimbată) ---
    const structuraSelect = document.getElementById('id_struct');
    const substructuraSelect = document.getElementById('id_substr');

    if (structuraSelect && substructuraSelect) {
        structuraSelect.addEventListener('change', function() {
            const id_struct = this.value;
            substructuraSelect.innerHTML = '<option value="">Se încarcă...</option>';
            substructuraSelect.disabled = true;

            if (id_struct) {
                fetch('api/get_substructuri.php?id_struct=' + id_struct)
                    .then(response => response.json())
                    .then(data => {
                        substructuraSelect.innerHTML = '<option value="0">Fără substructură</option>';
                        if (data.length > 0) {
                            data.forEach(function(substructura) {
                                const option = new Option(substructura.denumire, substructura.id_substr);
                                substructuraSelect.add(option);
                            });
                        }
                        substructuraSelect.disabled = false;
                    });
            }
        });
    }
});
</script>

<?php
require_once 'includes/dashboard_footer.php';
?>