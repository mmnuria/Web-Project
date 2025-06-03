<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die("Acceso no autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sala_id'])) {
    $sala_id = (int) $_POST['sala_id'];

    // Obtener el nombre de la sala antes de eliminarla
    $stmt = $pdo->prepare("SELECT nombre FROM salas WHERE id = ?");
    $stmt->execute([$sala_id]);
    $sala = $stmt->fetch();

    if ($sala) {
        // Eliminar comentarios asociados
        $pdo->prepare("DELETE FROM comentarios_salas WHERE sala_id = ?")->execute([$sala_id]);

        // Eliminar fotos asociadas
        $pdo->prepare("DELETE FROM fotos_salas WHERE sala_id = ?")->execute([$sala_id]);

        // Eliminar la sala
        $pdo->prepare("DELETE FROM salas WHERE id = ?")->execute([$sala_id]);

        // Registrar en logs (sin usuario_id)
        $usuario_nombre = $_SESSION['user']['nombre'] ?? 'Usuario desconocido';
        $descripcion = "El usuario '$usuario_nombre' eliminó la sala '{$sala['nombre']}' (ID: $sala_id)";
        $pdo->prepare("INSERT INTO log_eventos (descripcion, fecha) VALUES (?, NOW())")
            ->execute([$descripcion]);
    }
}

header("Location: salas.php");
exit;
