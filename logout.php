<?php
session_start();
require_once 'includes/db.php';

// Registrar evento solo si hay usuario identificado
if (isset($_SESSION['user'])) {
    $email = $_SESSION['user']['email'] ?? 'usuario desconocido';
    $descripcion = "Usuario '{$email}' ha cerrado sesión.";
    $stmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
    $stmt->execute([$descripcion]);
}

session_unset();
session_destroy();

header("Location: index.php");
exit();
?>
