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

            <section>
                <h2>Salas disponibles</h2>
                <div class="salas-grid">
                    <?php foreach ($salas as $sala): ?>
                        <div class="sala-card">
                            <h3><?= htmlspecialchars($sala['nombre']); ?></h3>
                            <p><strong>Capacidad:</strong> <?= htmlspecialchars($sala['num_puestos']); ?></p>
                            <p><strong>Ubicación:</strong> <?= htmlspecialchars($sala['ubicacion']); ?></p>
                            <p><?= nl2br(htmlspecialchars($sala['descripcion'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>


            <section>
                <h2>Reservas actuales</h2>
                <div class="reservas-grid">
                    <?php foreach ($reservas as $reserva): ?>
                        <div class="reserva-card">
                            <h3><?= htmlspecialchars($reserva['sala_nombre']); ?></h3>
                            <p><strong>Fecha:</strong> <?= htmlspecialchars($reserva['fecha']); ?></p>
                            <p><strong>Hora:</strong> <?= htmlspecialchars($reserva['hora_inicio']); ?> -
                                <?= htmlspecialchars($reserva['hora_fin']); ?></p>
                            <p><strong>Motivo:</strong> <?= nl2br(htmlspecialchars($reserva['motivo'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </main>

        <aside>
            <?php include 'includes/sidebar.php'; ?>
        </aside>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>