        </main>

        <footer class="mt-auto p-3 text-center text-muted small">
            &copy; <?php echo date('Y'); ?> WebApp Intranet. Toate drepturile rezervate.
        </footer>
    </div>
    <!-- /#page-content-wrapper -->
</div>
<!-- /#wrapper -->

<!-- Bootstrap JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // --- Connection Checker ---
    const checkInterval = 5000; // 5 secunde
    let isOffline = false; // Flag pentru a urmari starea conexiunii

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
                    // JSON-ul indica o eroare, dar conexiunea la server exista
                    if (!isOffline) {
                        console.log('Connection problem reported by server. Redirecting to status page...');
                        window.location.href = 'status.php';
                    }
                    isOffline = true;
                }
            })
            .catch(error => {
                // Eroare de retea, serverul nu este accesibil
                console.error('Connection check failed:', error);
                if (!isOffline) {
                    window.location.href = 'status.php';
                }
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