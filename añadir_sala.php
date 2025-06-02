<?php
session_start();
require_once 'includes/db.php';

// Solo administradores pueden acceder
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $num_puestos = (int) $_POST['num_puestos'];
    $descripcion = $_POST['descripcion'];
    $reservable = isset($_POST['reservable']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO salas (nombre, ubicacion, num_puestos, descripcion, reservable) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $ubicacion, $num_puestos, $descripcion, $reservable]);

    $sala_id = $pdo->lastInsertId();

    // Registrar en log_eventos
    $usuario_id = $_SESSION['user']['id'];
    $descripcion_log = "Sala creada por usuario ID {$usuario_id}: Sala ID {$sala_id}, nombre '{$nombre}'";
    $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
    $logStmt->execute([$descripcion_log]);

    // Redirigir a la página de edición para añadir fotos
    header("Location: editar_sala.php?id=$sala_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Sala</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <h1>Añadir nueva sala</h1>
    <form method="POST" class="form-añadir" novalidate>
        <label>Nombre: <input type="text" name="nombre" required></label><br>
        <label>Ubicación: <input type="text" name="ubicacion" required></label><br>
        <label>Número de puestos: <input type="number" name="num_puestos" min="1" required></label><br>
        <label>Descripción:<br>
            <textarea name="descripcion" rows="4" cols="50"></textarea>
        </label><br>
        <label><input type="checkbox" name="reservable" checked> ¿Reservable?</label><br>
        <button type="submit">Guardar sala</button>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
