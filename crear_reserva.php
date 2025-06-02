<?php
/* crear_reserva.php – Formulario y alta de reserva */
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Obtener horario apertura y cierre desde informacion_sitio
$stmtHorario = $pdo->query("SELECT horario_inicio, horario_fin FROM informacion_sitio LIMIT 1");
$horario = $stmtHorario ? $stmtHorario->fetch(PDO::FETCH_ASSOC) : false;

// Cortar segundos para obtener solo HH:MM
if ($horario && !empty($horario['horario_inicio']) && !empty($horario['horario_fin'])) {
    $horaInicioStr = substr($horario['horario_inicio'], 0, 5);
    $horaFinStr = substr($horario['horario_fin'], 0, 5);
} else {
    $horaInicioStr = '08:00';
    $horaFinStr = '20:00';
}

$horaInicio = DateTimeImmutable::createFromFormat('H:i', $horaInicioStr);
$horaFin = DateTimeImmutable::createFromFormat('H:i', $horaFinStr);

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['user']['id'];

/* ────────── GUARDAR (POST) ────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? '';
    $sala_id = (int) $_POST['sala_id'];
    $inicio = $_POST['hora_inicio'] ?? '';
    $fin = $_POST['hora_fin'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    $errores = [];
    if (!$motivo)
        $errores[] = 'El motivo es obligatorio.';
    if ($fin <= $inicio)
        $errores[] = 'La hora de fin debe ser posterior.';
    /* sala */
    $sala = $pdo->prepare('SELECT * FROM salas WHERE id = ?');
    $sala->execute([$sala_id]);
    $sala = $sala->fetch(PDO::FETCH_ASSOC);
    if (!$sala)
        $errores[] = 'La sala no existe.';
    elseif (!$sala['reservable'])
        $errores[] = 'La sala no es reservable.';

    $hIni = DateTimeImmutable::createFromFormat('H:i', $inicio);
    $hFin = DateTimeImmutable::createFromFormat('H:i', $fin);

    if ($hIni < $horaInicio || $hFin > $horaFin || $hIni >= $hFin) {
        $errores[] = "Las horas de reserva deben estar dentro del horario de apertura ({$horaInicioStr} - {$horaFinStr}).";
    }

    /* solapamiento */
    $sol = $pdo->prepare("
        SELECT 1
        FROM reservas
        WHERE sala_id = ?
          AND fecha = ?
          AND NOT (hora_fin <= ? OR hora_inicio >= ?)
        LIMIT 1
    ");
    $sol->execute([$sala_id, $fecha, $inicio, $fin]);
    if ($sol->fetchColumn())
        $errores[] = 'Existe solapamiento con otra reserva.';

    if (!$errores) {
        $ins = $pdo->prepare("
            INSERT INTO reservas (sala_id, usuario_id, fecha, hora_inicio, hora_fin, motivo)
            VALUES (?,?,?,?,?,?)
        ");
        $ins->execute([$sala_id, $usuario_id, $fecha, $inicio, $fin, $motivo]);

        // Insertar registro en log_eventos
        $descripcion = "Reserva creada por usuario ID {$usuario_id} para sala ID {$sala_id} el {$fecha} de {$inicio} a {$fin}";
        $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $logStmt->execute([$descripcion]);

        header('Location: reservas.php?fecha=' . $fecha);
        exit;
    }
}

/* ────────── MOSTRAR FORMULARIO (GET / errores) ────────── */
$fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? date('Y-m-d');
$salaId = $_GET['sala_id'] ?? $_POST['sala_id'] ?? '';
$inicio = $_GET['hora_inicio'] ?? $_POST['hora_inicio'] ?? '';
$fin = $_GET['hora_fin'] ?? $_POST['hora_fin'] ?? '';

// Limitar horas a rango horario apertura-cierre y redondear a 30 min
function limitarHora($hora, DateTimeImmutable $min, DateTimeImmutable $max)
{
    if (!$hora)
        return '';
    $dt = DateTimeImmutable::createFromFormat('H:i', $hora);
    if (!$dt)
        return '';

    if ($dt < $min)
        return $min->format('H:i');
    if ($dt > $max)
        return $max->format('H:i');

    $minutos = (int) $dt->format('i');
    $horaInt = (int) $dt->format('H');
    $minutosRedondeados = ($minutos < 15) ? 0 : (($minutos < 45) ? 30 : 0);
    if ($minutosRedondeados === 0 && $minutos >= 45)
        $horaInt++;
    return sprintf('%02d:%02d', $horaInt, $minutosRedondeados);
}

$inicio = limitarHora($inicio, $horaInicio, $horaFin);
$fin = limitarHora($fin, $horaInicio, $horaFin);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva reserva</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="crear-reserva-page">

        <h1>Crear nueva reserva</h1>

        <?php if (!empty($errores)): ?>
            <ul class="errores">
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" class="form-crear-reserva">
            <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
            <input type="hidden" name="sala_id" value="<?= htmlspecialchars($salaId) ?>">

            <p>Fecha: <strong><?= (new DateTimeImmutable($fecha))->format('d/m/Y') ?></strong></p>

            <label>
                Hora inicio:
                <input required type="time" step="1800" name="hora_inicio" min="<?= htmlspecialchars($horaInicioStr) ?>"
                    max="<?= htmlspecialchars($horaFinStr) ?>" value="<?= htmlspecialchars($inicio) ?>">
            </label><br><br>
            <label>
                Hora fin:
                <input required type="time" step="1800" name="hora_fin" min="<?= htmlspecialchars($horaInicioStr) ?>"
                    max="<?= htmlspecialchars($horaFinStr) ?>" value="<?= htmlspecialchars($fin) ?>">
            </label><br><br>
            <label>
                Motivo:
                <input required type="text" name="motivo" maxlength="100"
                    value="<?= htmlspecialchars($_POST['motivo'] ?? '') ?>">
            </label><br><br>

            <button type="submit">Guardar</button>
            <button type="button"
                onclick="window.location.href='reservas.php?fecha=<?= htmlspecialchars($fecha) ?>'">Cancelar</button>

        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>