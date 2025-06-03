<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die('Acceso no autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = (int) $_POST['usuario_id'];

    // Evitar que admin se elimine a sí mismo (opcional)
    if ($usuario_id === $_SESSION['user']['id']) {
        die('No puedes eliminarte a ti mismo.');
    }

    // Eliminar usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);

    // Registrar en logs
    $descripcion = "El usuario ID " . $_SESSION['user']['id'] . " eliminó al usuario ID $usuario_id.";
    $pdo->prepare("INSERT INTO log_eventos (descripcion, fecha) VALUES (?, NOW())")
        ->execute([$descripcion]);
}

header("Location: usuarios.php");
exit;
