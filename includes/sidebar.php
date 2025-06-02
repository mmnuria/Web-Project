<?php
require_once 'includes/db.php';

try {
    // Total de aulas reservables
    $stmtTotalAulas = $pdo->prepare("SELECT COUNT(*) FROM salas WHERE reservable = 1");
    $stmtTotalAulas->execute();
    $totalAulas = $stmtTotalAulas->fetchColumn();

    // Capacidad total sumando num_puestos
    $stmtCapacidadTotal = $pdo->prepare("SELECT SUM(num_puestos) FROM salas WHERE reservable = 1");
    $stmtCapacidadTotal->execute();
    $capacidadTotal = $stmtCapacidadTotal->fetchColumn();

    // Salas reservadas hoy (reservas con fecha = hoy para salas reservables)
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
    <div class="info-secundaria">
        <p>Total de aulas: <span id="totalAulas"><?php echo htmlspecialchars($totalAulas); ?></span></p>
        <p>Capacidad total: <span id="capacidadTotal"><?php echo htmlspecialchars($capacidadTotal); ?></span></p>
        <p>Salas reservadas hoy: <span id="salasReservadas"><?php echo htmlspecialchars($salasReservadas); ?></span></p>
    </div>
</aside>
