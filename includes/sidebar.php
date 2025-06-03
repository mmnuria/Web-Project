<?php
require_once 'db.php';
require_once 'funciones.php'; // Incluir funciones comunes

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Obtener info general del sitio (nombre, logo, descripción, horarios)
    $stmtInfo = $pdo->query("SELECT * FROM informacion_sitio LIMIT 1");
    $info = $stmtInfo->fetch();

    // Total aulas reservables
    $stmtTotalAulas = $pdo->prepare("SELECT COUNT(*) FROM salas WHERE reservable = 1");
    $stmtTotalAulas->execute();
    $totalAulas = $stmtTotalAulas->fetchColumn();

    // Capacidad total sumando num_puestos
    $stmtCapacidadTotal = $pdo->prepare("SELECT SUM(num_puestos) FROM salas WHERE reservable = 1");
    $stmtCapacidadTotal->execute();
    $capacidadTotal = $stmtCapacidadTotal->fetchColumn();

    // Salas reservadas hoy (fecha = hoy y reservable)
    $stmtSalasReservadas = $pdo->prepare("
        SELECT COUNT(DISTINCT r.sala_id)
        FROM reservas r
        INNER JOIN salas s ON r.sala_id = s.id
        WHERE r.fecha = CURDATE() AND s.reservable = 1
    ");
    $stmtSalasReservadas->execute();
    $salasReservadas = $stmtSalasReservadas->fetchColumn();

} catch (PDOException $e) {
    die("Error al obtener datos para el sidebar: " . $e->getMessage());
}
?>

<aside>
    <div class="container-sidebar">

        <div class="info-sitio">
            <?php if (!empty($info['logo'])): ?>
                <img src="mostrar_logo.php" alt="Logo del centro" class="logo-centro">
            <?php endif; ?>

            <h3><?= htmlspecialchars($info['nombre_centro'] ?? 'Nombre del Centro') ?></h3>

            <p><?= nl2br(htmlspecialchars($info['descripcion'] ?? '')) ?></p>

            <p><strong>Horario:</strong>
                <?= formatHoraSinSegundos($info['horario_inicio'] ?? '') ?> -
                <?= formatHoraSinSegundos($info['horario_fin'] ?? '') ?>
            </p>
        </div>

        <div class="info-secundaria">
            <p>Total de aulas: <span id="totalAulas"><?= htmlspecialchars($totalAulas ?? '0') ?></span></p>
            <p>Capacidad total: <span id="capacidadTotal"><?= htmlspecialchars($capacidadTotal ?? '0') ?></span></p>
            <p>Salas reservadas hoy: <span id="salasReservadas"><?= htmlspecialchars($salasReservadas ?? '0') ?></span>
            </p>
        </div>

        <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'admin'): ?>
            <div class="sidebar-item">
                <a href="editar_info.php" class="btn-configurar-sitio">⚙️ Configurar sitio</a>
            </div>

        <?php endif; ?>
    </div>
</aside>