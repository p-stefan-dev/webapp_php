<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platformă Nefuncțională</title>
    <link rel="stylesheet" href="assets/css/status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="status-container">
        <div class="icon-wrapper">
            <i class="fas fa-satellite-dish"></i>
            <div class="oblique-bar"></div>
        </div>
        <h1>Platformă Temporar Nefuncțională</h1>
        <p>Ne pare rău, dar se pare că există o problemă de conexiune sau platforma este în mentenanță.</p>
        <p>Vă rugăm să reveniți mai târziu. Personalul tehnic a fost notificat.</p>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Connection Checker ---
    const checkInterval = 5000; // 5 secunde
    let isOffline = true; 

    function checkConnection() {
        fetch('check_connection.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok, status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'ok') {
                    // Conexiunea a revenit
                    if (isOffline) {
                        console.log('Connection restored. Redirecting to home...');
                        window.location.href = 'index.php'; // Redirectionare la pagina principala
                    }
                    isOffline = false;
                } else {
                    isOffline = true;
                }
            })
            .catch(error => {
                console.error('Connection check failed:', error);
                isOffline = true;
            });
    }

    // Verificam conexiunea la incarcarea paginii
    checkConnection();

    // Si apoi setam intervalul pentru verificari periodice
    setInterval(checkConnection, checkInterval);
});
</script>
</body>
</html>
