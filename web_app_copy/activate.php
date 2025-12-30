<?php
// activate.php

require_once 'config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$messageType = '';

if (empty($token)) {
    // Redirectam la index daca nu exista token
    header('Location: index.php');
    exit();
}

try {
    // Cautam utilizatorul dupa token-ul de activare
    $stmt = $pdo->prepare("SELECT u.id, p.id AS personal_id FROM users u JOIN personal p ON u.id_personal = p.id WHERE u.activation_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Am gasit utilizatorul, deci il activam
        $pdo->beginTransaction();

        // 1. Actualizam in 'personal': setam rol=4
        $stmt = $pdo->prepare("UPDATE personal SET rol = 4 WHERE id = ?");
        $stmt->execute([$user['personal_id']]);

        // 2. Actualizam in 'users': stergem token-ul pentru a nu fi reutilizat
        $stmt = $pdo->prepare("UPDATE users SET activation_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        $pdo->commit();

        // Setam un mesaj de succes in sesiune si redirectionam la login
        $_SESSION['activation_success'] = 'Contul a fost activat cu succes! Vă puteți autentifica acum.';
        header('Location: index.php');
        exit();

    } else {
        // Token-ul este invalid sau a fost deja folosit
        $_SESSION['activation_error'] = 'Link-ul de activare este invalid sau a expirat. Vă rugăm să vă înregistrați din nou.';
        header('Location: register.php');
        exit();
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // O eroare generica pentru utilizator
    $_SESSION['activation_error'] = 'A apărut o eroare tehnică la activarea contului. Vă rugăm să contactați administratorul.';
    header('Location: register.php');
    exit();
    // error_log($e->getMessage());
}
