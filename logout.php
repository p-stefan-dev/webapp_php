<?php
// logout.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Stergem toate variabilele din sesiune
$_SESSION = array();

// 2. Distrugem sesiunea de pe server
session_destroy();

// 3. Redirectionam utilizatorul la pagina de login
header('Location: index.php');
exit();
