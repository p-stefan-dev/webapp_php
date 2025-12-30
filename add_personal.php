<?php
// add_personal.php

$pageTitle = 'Adăugare Personal';
require_once 'includes/dashboard_header.php';

// Protectie - doar adminii (rol=1) pot accesa aceasta pagina
if (!isset($userRole) || $userRole != 1) {
    echo '<div class="alert alert-danger">Acces restricționat.</div>';
    require_once 'includes/dashboard_footer.php';
    exit();
}

$successMessage = '';
$errorMessage = '';

// --- LOGICA DE PROCESARE A FORMULARULUI (INSERT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preluare si validare date
    $nume = trim($_POST['nume']);
    $prenume = trim($_POST['prenume']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    
    // Preluam ID-urile pentru localizare
    $judet_id = filter_input(INPUT_POST, 'judet', FILTER_VALIDATE_INT);
    $uat_id = filter_input(INPUT_POST, 'uat', FILTER_VALIDATE_INT);
    // Atenție: Localitatea vine ca Text (nume), nu ca ID, din scriptul JS
    $localitate_nume = trim($_POST['localitate']); 

    $tip_serviciu = $_POST['tip_serviciu'];
    $id_grad = filter_input(INPUT_POST, 'id_grad', FILTER_VALIDATE_INT);
    $id_struct = filter_input(INPUT_POST, 'id_struct', FILTER_VALIDATE_INT);
    $id_substr = filter_input(INPUT_POST, 'id_substr', FILTER_VALIDATE_INT);
    
    // Validare simplă
    if (empty($nume) || empty($prenume) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Numele, prenumele și email-ul sunt obligatorii și trebuie să fie valide.';
    } else {
        try {
            // --- PAS CRITIC: Preluam numele corecte din DB folosind ID-urile selectate ---
            // Baza ta de date salvează Numele județului, nu ID-ul.
            $judet_nume = null;
            $uat_nume = null;

            if ($judet_id) {
                $judet_nume = $pdo->query("SELECT nume_judet FROM judete WHERE id = " . (int)$judet_id)->fetchColumn();
            }
            if ($uat_id) {
                $uat_nume = $pdo->query("SELECT nume_uat FROM uat WHERE id = " . (int)$uat_id)->fetchColumn();
            }
            // -----------------------------------------------------------------------------
            
            $sql = "INSERT INTO personal (
                        nume, prenume, email, telefon, 
                        judet, uat, localitate, tip_serviciu,
                        id_grad, id_struct, id_substr, data_adaugarii
                    ) VALUES (
                        ?, ?, ?, ?, 
                        ?, ?, ?, ?,
                        ?, ?, ?, NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nume, $prenume, $email, $telefon,
                $judet_nume, $uat_nume, $localitate_nume, $tip_serviciu,
                $id_grad, $id_struct, ($id_substr == 0 ? null : $id_substr)
            ]);

            // Redirecționare către lista de personal
            $_SESSION['success_message'] = 'Angajatul a fost adăugat cu succes!';
            header('Location: personal/personal.php');
            exit();

        } catch (PDOException $e) {
            $errorMessage = 'A apărut o eroare la salvarea datelor: ' . $e->getMessage();
        }
    }
}

// --- Preluarea listelor pentru dropdown-uri statice ---
try {
    $grade = $pdo->query("SELECT id_grad, nume_grad FROM grade ORDER BY id_grad")->fetchAll();
    $structuri = $pdo->query("SELECT id_struct, Structura AS denumire FROM structuri ORDER BY id_struct")->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Eroare la încărcarea listelor: ' . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center">
    <h1 class="h2">Adăugare Personal Nou</h1>
    <a href="personal/personal.php" class="btn btn-secondary">Înapoi la Listă</a>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<form action="personal/add_personal.php" method="post" class="card content-card mt-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="id_grad" class="form-label">Grad</label>
                <select class="form-select" id="id_grad" name="id_grad" required>
                    <option value="">-- Alegeți Gradul --</option>
                    <?php foreach($grade as $grad): ?>
                    <option value="<?php echo $grad['id_grad']; ?>">
                        <?php echo htmlspecialchars($grad['nume_grad']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="nume" class="form-label">Nume</label>
                <input type="text" class="form-control" id="nume" name="nume" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="prenume" class="form-label">Prenume</label>
                <input type="text" class="form-control" id="prenume" name="prenume" required>
            </div>
             <div class="col-md-3 mb-3">
                <label for="tip_serviciu" class="form-label">Tip Serviciu</label>
                <select class="form-select" id="tip_serviciu" name="tip_serviciu">
                     <option value="">-- Selectați --</option>
                     <option value="8ore">8 ore</option>
                     <option value="tura1">Tura 1</option>
                     <option value="tura2">Tura 2</option>
                     <option value="tura3">Tura 3</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
             <div class="col-md-6 mb-3">
                <label for="telefon" class="form-label">Telefon</label>
                <input type="text" class="form-control" id="telefon" name="telefon">
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="id_struct" class="form-label">Structura</label>
                 <select class="form-select" id="id_struct" name="id_struct" required>
                    <option value="">-- Alegeți Structura --</option>
                    <?php foreach($structuri as $structura): ?>
                    <option value="<?php echo $structura['id_struct']; ?>">
                        <?php echo htmlspecialchars($structura['denumire']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="id_substr" class="form-label">Substructura</label>
                <select class="form-select" id="id_substr" name="id_substr" disabled>
                    <option value="0">Selectați întâi structura</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="judet" class="form-label">Județ</label>
                <select class="form-select" id="judet" name="judet">
                    <option value="">Se încarcă...</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="uat" class="form-label">UAT</label>
                <select class="form-select" id="uat" name="uat" disabled>
                    <option value="">Selectați județul</option>
                </select>
            </div>
             <div class="col-md-4 mb-3">
                <label for="localitate" class="form-label">Localitate</label>
                <select class="form-select" id="localitate" name="localitate" disabled>
                    <option value="">Selectați UAT</option>
                </select>
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-primary">Adaugă Angajat</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const judetSelect = document.getElementById('judet');
    const uatSelect = document.getElementById('uat');
    const localitateSelect = document.getElementById('localitate');

    // 1. Funcție robustă pentru populare (Aceeași ca la Editare)
    function populateSelect(selectElement, items, selectedValue, isNameValue = false) {
        selectElement.innerHTML = ''; 
        selectElement.add(new Option('-- Selectați --', ''));

        if (!items || items.length === 0) return;

        items.forEach(item => {
            let textDisplay = "Nespecificat";
            
            // Verificare explicită pentru noile denumiri de coloane
            if (item.nume_judet) textDisplay = item.nume_judet;
            else if (item.nume_uat) textDisplay = item.nume_uat;
            else if (item.nume_localitate) textDisplay = item.nume_localitate;
            else if (item.denumire) textDisplay = item.denumire; // Pt structuri
            
            // Dacă isNameValue e true, punem Textul ca valoare (pt Localități). Altfel ID-ul.
            const value = isNameValue ? textDisplay : item.id;
            
            const option = new Option(textDisplay, value);
            selectElement.add(option);
        });
        selectElement.disabled = false;
    }

    // 2. Fetch Helper
    async function fetchData(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (e) {
            console.error("Eroare fetch:", e);
            return [];
        }
    }

    // 3. Event Listeners
    judetSelect.addEventListener('change', async function() {
        uatSelect.innerHTML = '<option value="">Se încarcă...</option>';
        localitateSelect.innerHTML = '<option value="">Selectați UAT</option>';
        uatSelect.disabled = true;
        localitateSelect.disabled = true;

        if (this.value) {
            const uatData = await fetchData(`api/get_uat.php?id_judet=${this.value}`);
            populateSelect(uatSelect, uatData, null);
        }
    });

    uatSelect.addEventListener('change', async function() {
        localitateSelect.innerHTML = '<option value="">Se încarcă...</option>';
        localitateSelect.disabled = true;
        
        if (this.value) {
            const localitatiData = await fetchData(`api/get_localitati.php?id_uat=${this.value}`);
            // IMPORTANT: true la final pentru că salvăm NUMELE localității
            populateSelect(localitateSelect, localitatiData, null, true); 
        }
    });

    // 4. Initializare (Încărcăm doar județele la început)
    const judeteData = await fetchData('api/get_judete.php');
    populateSelect(judetSelect, judeteData, null);

    // --- Logica pentru Substructuri ---
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
                            data.forEach(function(sub) {
                                substructuraSelect.add(new Option(sub.denumire, sub.id_substr));
                            });
                        }
                        substructuraSelect.disabled = false;
                    });
            } else {
                 substructuraSelect.innerHTML = '<option value="0">Selectați întâi structura</option>';
            }
        });
    }
});
</script>

<?php
require_once 'includes/dashboard_footer.php';
?>