<?php
// includes/auth_check.php

// Acest script verifica daca utilizatorul este autentificat.
// Il vom include la inceputul fiecarei pagini care necesita autentificare.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Daca variabila de sesiune 'user_id' nu este setata, inseamna ca
// utilizatorul nu este logat, asa ca il redirectionam la pagina de login.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit(); // Oprim executia scriptului curent
}
