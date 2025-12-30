<?php
// includes/header.php

// Pornim sesiunea pe fiecare pagina, daca nu este deja pornita.
// Este esential pentru a mentine starea de autentificare.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<?php
// includes/header.php - Pentru paginile publice (login, register, etc.)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'WebApp Intranet'; ?></title>
    <!-- Link catre Bootstrap CSS local -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #212529; /* Culoare de fundal asortata cu tema Darkly */
        }
    </style>
</head>
<body>
<main class="container mt-4">

