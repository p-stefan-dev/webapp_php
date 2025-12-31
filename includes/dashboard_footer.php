</main>

        <footer class="mt-auto p-3 text-center text-muted small">
            &copy; <?php echo date('Y'); ?> WebApp Intranet. Toate drepturile rezervate.
        </footer>
    </div>
    </div>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // --- Connection Checker ---
    const checkInterval = 5000; 
    let isOffline = false; 

    // FIX: Definim calea absolută către fișierul de verificare
    // Astfel, va funcționa și din /personal/, și din /tehnica/
    const checkUrl = '<?php echo BASE_URL; ?>check_connection.php';
    const homeUrl = '<?php echo BASE_URL; ?>index.php';

    function checkConnection() {
        fetch(checkUrl)
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
                        window.location.href = homeUrl; 
                    }
                    isOffline = false;
                } else {
                    // JSON-ul indica o eroare
                    if (!isOffline) {
                        console.log('Connection problem reported by server.');
                        // Poți comenta alerta dacă e prea sâcâitoare
                        // alert('Conexiune la server indisponibilă. Contactați administratorul!');
                    }
                    isOffline = true;
                }
            })
            .catch(error => {
                // Eroare de retea (404 Not Found intra tot aici la fetch in anumite cazuri, sau la primul then)
                console.error('Connection check failed:', error);
                if (!isOffline) {
                     // Aceasta alerta aparea pentru ca nu gasea fisierul check_connection.php
                     // Acum ar trebui sa dispara.
                    // alert('Conexiune la server indisponibilă. Contactați administratorul!');
                }
                isOffline = true;
            });
    }

    // Verificam conexiunea la incarcarea paginii
    checkConnection();

    // Si apoi setam intervalul
    setInterval(checkConnection, checkInterval);
});
</script>
</body>
</html>