<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php'; 

// Obtener salas reservables
$stmtSalas = $pdo->prepare("SELECT * FROM salas WHERE reservable = 1");
$stmtSalas->execute();
$salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

// Obtener reservas con nombre de sala y nombre de usuario
$stmtReservas = $pdo->prepare("
    SELECT r.*, s.nombre AS sala_nombre, u.nombre AS usuario_nombre
    FROM reservas r
    INNER JOIN salas s ON r.sala_id = s.id
    INNER JOIN usuarios u ON r.usuario_id = u.id
    WHERE s.reservable = 1
    ORDER BY r.fecha, r.hora_inicio
");
$stmtReservas->execute();
$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Salas</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <div class="container">
        <main>
            <h1>Bienvenido a la plataforma de reservas de salas</h1>
            <p>Por favor, utilice el menú para navegar.</p>

            <section>
                <h2>Salas disponibles</h2>
                <ul>
                    <?php foreach ($salas as $sala): ?>
                        <li>
                            <?php echo htmlspecialchars($sala['nombre']); ?> — Capacidad: <?php echo htmlspecialchars($sala['num_puestos']); ?><br>
                            Ubicación: <?php echo htmlspecialchars($sala['ubicacion']); ?><br>
                            Descripción: <?php echo htmlspecialchars($sala['descripcion']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section>
                <h2>Reservas actuales</h2>
                <ul>
                    <?php foreach ($reservas as $reserva): ?>
                        <li>
                            Sala: <?php echo htmlspecialchars($reserva['sala_nombre']); ?> | Usuario: <?php echo htmlspecialchars($reserva['usuario_nombre']); ?> | Fecha: <?php echo htmlspecialchars($reserva['fecha']); ?> | Hora: <?php echo htmlspecialchars($reserva['hora_inicio']); ?> - <?php echo htmlspecialchars($reserva['hora_fin']); ?><br>
                            Motivo: <?php echo htmlspecialchars($reserva['motivo']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </main>

        <aside>
            <?php include 'includes/sidebar.php'; ?>
        </aside>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
