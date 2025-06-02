<?php
/*  reservas.php  – Muestra el calendario y la tabla de reservas  */
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Permitir acceso sin login
$usuario = $_SESSION['user'] ?? null;
$usuario_id = $usuario['id'] ?? null;

/* ───────────────────── PARÁMETROS DE FECHA ───────────────────── */
$hoy = new DateTimeImmutable('today');
$fechaStr = $_GET['fecha'] ?? $hoy->format('Y-m-d');
$fechaSeleccionada = DateTimeImmutable::createFromFormat('Y-m-d', $fechaStr);
if ($fechaSeleccionada === false) {
    $fechaSeleccionada = new DateTimeImmutable('today');
}

$anio = (int) $fechaSeleccionada->format('Y');
$mes = (int) $fechaSeleccionada->format('n');   // 1-12
$dia = (int) $fechaSeleccionada->format('j');

/* ───────────────────── MINICALENDARIO EN ESPAÑOL ─────────────── */
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
$nomDias = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];              // cabecera   (L-D)

$inicioMes = new DateTimeImmutable("$anio-$mes-01");
$offsetInicio = ((int) $inicioMes->format('N')) - 1;    // 0-6
$nDiasMes = (int) $inicioMes->format('t');

$mesAnterior = $inicioMes->modify('-1 month');
$mesSiguiente = $inicioMes->modify('+1 month');

function linkConFecha(DateTimeImmutable $f): string
{
    return 'reservas.php?fecha=' . $f->format('Y-m-d');
}

// Obtener horario apertura y cierre desde informacion_sitio
$stmtHorario = $pdo->query("SELECT horario_inicio, horario_fin FROM informacion_sitio LIMIT 1");
$horario = $stmtHorario ? $stmtHorario->fetch(PDO::FETCH_ASSOC) : false;

if ($horario && !empty($horario['horario_inicio']) && !empty($horario['horario_fin'])) {
    // Cortamos los segundos para usar solo HH:MM
    $horaInicioStr = substr($horario['horario_inicio'], 0, 5); // "08:00:00" -> "08:00"
    $horaFinStr = substr($horario['horario_fin'], 0, 5);
} else {
    $horaInicioStr = '08:00';
    $horaFinStr = '20:00';
}

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

/* ───────────────────── SALAS Y RESERVAS ──────────────────────── */
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
            <p style="text-align: center; background: #f8f8f8; padding: 1em; border: 1px solid #ccc;">
                <strong>¿Quieres hacer una reserva?</strong> <a href="login.php">Inicia sesión</a>.
            </p>
        <?php endif; ?>

        <!-- ─────── BLOQUE 1 – MINICALENDARIO ─────── -->
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
                    <tr>
                        <?php foreach ($nomDias as $d): ?>
                            <th><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
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

        <!-- ─────── BLOQUE 2 – TABLA DE RESERVAS ─────── -->
        <div class="tabla-scroll">
            <table class="tabla-reservas">
                <thead>
                    <tr>
                        <th>Sala</th>
                        <?php foreach ($horas as $h): ?>
                            <th><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $sala): ?>
                        <tr>
                            <td class="nombre-sala" title="Capacidad: <?= htmlspecialchars($sala['capacidad'] ?? '-') ?>">
                                <?= htmlspecialchars($sala['nombre']) ?>
                            </td>

                            <?php foreach ($horas as $hora): ?>
                                <?php
                                $estado = 'libre';
                                $celdaReserva = null;

                                // Permitir reservar en salas no reservables solo para admin
                                $puedeReservar = $sala['reservable'] || (isset($usuario['rol']) && $usuario['rol'] === 'admin');

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
                                <td class="<?= $estado ?>" <?php if ($celdaReserva): ?>
                                        title="<?= htmlspecialchars($celdaReserva['motivo']) ?> (<?= htmlspecialchars($celdaReserva['usuario_nombre']) ?>)"
                                    <?php endif; ?>>
                                    <?php if ($estado === 'libre' && $usuario && $puedeReservar): ?>
                                        <!--  ➕ Crear reserva  -->
                                        <a class="btn-add" title="Crear reserva"
                                            href="crear_reserva.php?fecha=<?= $fechaSeleccionada->format('Y-m-d') ?>
                                        &sala_id=<?= $sala['id'] ?>
                                        &hora_inicio=<?= $hora ?>
                                        &hora_fin=<?= (new DateTimeImmutable($hora))->modify('+30 minutes')->format('H:i') ?>">
                                            ➕
                                        </a>
                                    <?php elseif ($estado === 'propia' && $usuario): ?>
                                        <!--  ❌ Cancelar reserva propia  -->
                                        <form method="POST" action="cancelar_reserva.php" style="margin:0;">
                                            <input type="hidden" name="reserva_id" value="<?= $celdaReserva['id'] ?>">
                                            <input type="hidden" name="fecha" value="<?= $fechaSeleccionada->format('Y-m-d') ?>">
                                            <button type="submit" class="btn-cancel" title="Cancelar reserva">❌</button>
                                        </form>
                                    <?php else: ?>
                                        — <!-- Ocupada o no reservable o no logueado -->
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