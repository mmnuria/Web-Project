<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

$usuario = $_SESSION['user'] ?? null;
$usuario_id = $usuario['id'] ?? null;

$hoy = new DateTimeImmutable('today');
$fechaStr = $_GET['fecha'] ?? $hoy->format('Y-m-d');
$fechaSeleccionada = DateTimeImmutable::createFromFormat('Y-m-d', $fechaStr);
if ($fechaSeleccionada === false) {
    $fechaSeleccionada = new DateTimeImmutable('today');
}

$anio = (int) $fechaSeleccionada->format('Y');
$mes = (int) $fechaSeleccionada->format('n');
$dia = (int) $fechaSeleccionada->format('j');

$nomMeses = [
    1 => 'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
];
$nomDias = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

$inicioMes = new DateTimeImmutable("$anio-$mes-01");
$offsetInicio = ((int) $inicioMes->format('N')) - 1;
$nDiasMes = (int) $inicioMes->format('t');

$mesAnterior = $inicioMes->modify('-1 month');
$mesSiguiente = $inicioMes->modify('+1 month');

function linkConFecha(DateTimeImmutable $f): string
{
    return 'reservas.php?fecha=' . $f->format('Y-m-d');
}

$stmtHorario = $pdo->query("SELECT horario_inicio, horario_fin FROM informacion_sitio LIMIT 1");
$horario = $stmtHorario ? $stmtHorario->fetch(PDO::FETCH_ASSOC) : false;

$horaInicioStr = $horario && !empty($horario['horario_inicio']) ? substr($horario['horario_inicio'], 0, 5) : '08:00';
$horaFinStr = $horario && !empty($horario['horario_fin']) ? substr($horario['horario_fin'], 0, 5) : '20:00';

$horaInicio = DateTimeImmutable::createFromFormat('H:i', $horaInicioStr);
$horaFin = DateTimeImmutable::createFromFormat('H:i', $horaFinStr);
if (!$horaInicio || !$horaFin) {
    $horaInicio = new DateTimeImmutable('08:00');
    $horaFin = new DateTimeImmutable('20:00');
}

$horas = [];
$slot = $horaInicio;
while ($slot <= $horaFin) {
    $horas[] = $slot->format('H:i');
    $slot = $slot->modify('+30 minutes');
}

$salas = $pdo->query("SELECT * FROM salas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sql = "
    SELECT r.*, u.nombre usuario_nombre
    FROM reservas r
    JOIN usuarios u ON u.id = r.usuario_id
    WHERE r.fecha = ?
";
$resStmt = $pdo->prepare($sql);
$resStmt->execute([$fechaSeleccionada->format('Y-m-d')]);
$reservasPorSala = [];
foreach ($resStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $reservasPorSala[$r['sala_id']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reservas – <?= htmlspecialchars($fechaSeleccionada->format('d/m/Y')) ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="reservas-page">
        <h1 class="titulo">Reservas del <?= $fechaSeleccionada->format('d/m/Y') ?></h1>

        <?php if (!$usuario): ?>
            <p>
                <strong>¿Quieres hacer una reserva?</strong> <a href="login.php">Inicia sesión</a>.
            </p>
        <?php endif; ?>

        <div class="calendario-wrapper">
            <div class="controles-mes">
                <a class="btn-cal" href="<?= linkConFecha($mesAnterior) ?>">◀
                    <?= $nomMeses[$mesAnterior->format('n')] ?></a>
                <span class="nombre-mes"><?= $nomMeses[$mes] . ' ' . $anio ?></span>
                <a class="btn-cal"
                   href="<?= linkConFecha($mesSiguiente) ?>"><?= $nomMeses[$mesSiguiente->format('n')] ?> ▶</a>
            </div>
            <table class="mini-cal">
                <thead>
                <tr><?php foreach ($nomDias as $d) echo "<th>$d</th>"; ?></tr>
                </thead>
                <tbody>
                <tr>
                    <?php
                    for ($i = 0; $i < $offsetInicio; $i++)
                        echo '<td></td>';
                    for ($d = 1; $d <= $nDiasMes; $d++) {
                        $fechaCelda = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                        $clase = ($d === $dia) ? 'dia-seleccionado' : '';
                        echo "<td class='$clase'><a href='reservas.php?fecha=$fechaCelda'>$d</a></td>";
                        if (($d + $offsetInicio) % 7 === 0 && $d !== $nDiasMes)
                            echo '</tr><tr>';
                    }
                    $resto = (7 - ($nDiasMes + $offsetInicio) % 7) % 7;
                    for ($i = 0; $i < $resto; $i++)
                        echo '<td></td>';
                    ?>
                </tr>
                </tbody>
            </table>
            <a class="btn-hoy" href="<?= linkConFecha($hoy) ?>">Hoy</a>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-reservas">
                <thead>
                <tr>
                    <th>Sala</th><?php foreach ($horas as $h) echo "<th>$h</th>"; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($salas as $sala): ?>
                    <tr>
                        <td class="nombre-sala"
                            title="Capacidad: <?= htmlspecialchars($sala['capacidad'] ?? '-') ?>">
                            <?= htmlspecialchars($sala['nombre']) ?>
                        </td>
                        <?php foreach ($horas as $hora):
                            $estado = 'libre';
                            $celdaReserva = null;
                            $puedeReservar = $sala['reservable'] || ($usuario['rol'] ?? null) === 'admin';

                            if (!$puedeReservar) {
                                $estado = 'no-reservable';
                            } elseif (!empty($reservasPorSala[$sala['id']])) {
                                foreach ($reservasPorSala[$sala['id']] as $res) {
                                    if ($hora >= $res['hora_inicio'] && $hora < $res['hora_fin']) {
                                        $estado = ($usuario_id && $res['usuario_id'] == $usuario_id) ? 'propia' : 'ocupada';
                                        $celdaReserva = $res;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <td class="<?= $estado ?>">
                                <?php if ($celdaReserva): ?>
                                    <div class="tooltip-container">
                                        <?php if ($estado === 'libre' && $usuario && $puedeReservar): ?>
                                            <a class="btn-add" title="Crear reserva"
                                               href="crear_reserva.php?fecha=<?= $fechaSeleccionada->format('Y-m-d') ?>&sala_id=<?= $sala['id'] ?>&hora_inicio=<?= $hora ?>&hora_fin=<?= (new DateTimeImmutable($hora))->modify('+30 minutes')->format('H:i') ?>">
                                                ➕
                                            </a>
                                        <?php elseif ((($estado === 'propia') && $usuario) || ($usuario && $usuario['rol'] === 'admin')): ?>
                                            <form method="POST" action="cancelar_reserva.php"
                                                  onsubmit="return confirm('¿Estás seguro de que quieres cancelar esta reserva?');" novalidate>
                                                <input type="hidden" name="reserva_id" value="<?= $celdaReserva['id'] ?>">
                                                <input type="hidden" name="fecha" value="<?= $fechaSeleccionada->format('Y-m-d') ?>">
                                                <button type="submit" class="btn-cancel"
                                                        title="Cancelar reserva<?= ($usuario['rol'] === 'admin' ? ' (como administrador)' : '') ?>">❌
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>

                                        <div class="tooltip-info">
                                            <strong>Información reserva:</strong><br>
                                            Motivo: <?= htmlspecialchars($celdaReserva['motivo']) ?><br>
                                            Hora inicio: <?= formatHoraSinSegundos($celdaReserva['hora_inicio']) ?><br>
                                            Hora fin: <?= formatHoraSinSegundos($celdaReserva['hora_fin']) ?><br>
                                            <?php if (($usuario['rol'] ?? null) === 'admin'): ?>
                                                Usuario: <?= htmlspecialchars($celdaReserva['usuario_nombre']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php if ($estado === 'libre' && $usuario && $puedeReservar): ?>
                                        <a class="btn-add" title="Crear reserva"
                                           href="crear_reserva.php?fecha=<?= $fechaSeleccionada->format('Y-m-d') ?>&sala_id=<?= $sala['id'] ?>&hora_inicio=<?= $hora ?>&hora_fin=<?= (new DateTimeImmutable($hora))->modify('+30 minutes')->format('H:i') ?>">
                                            ➕
                                        </a>
                                    <?php elseif ((($estado === 'propia') && $usuario) || ($usuario && $usuario['rol'] === 'admin')): ?>
                                        <form method="POST" action="cancelar_reserva.php" 
                                              onsubmit="return confirm('¿Estás seguro de que quieres cancelar esta reserva?');"novalidate>
                                            <input type="hidden" name="reserva_id" value="<?= $celdaReserva['id'] ?>">
                                            <input type="hidden" name="fecha" value="<?= $fechaSeleccionada->format('Y-m-d') ?>">
                                            <button type="submit" class="btn-cancel"
                                                    title="Cancelar reserva<?= ($usuario['rol'] === 'admin' ? ' (como administrador)' : '') ?>">❌
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>
