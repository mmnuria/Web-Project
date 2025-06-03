<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/funciones.php';

$cookie_expire = time() + 30 * 24 * 60 * 60;

$cookie_names = [
    'motivo',
    'fecha_inicio',
    'fecha_fin',
    'usuario_id',
    'sala_id',
    'orden',
    'items_por_pagina'
];

$criterios = [];
$hay_get = !empty($_GET);

if ($hay_get) {
    foreach ($cookie_names as $name) {
        $criterios[$name] = $_GET[$name] ?? '';
        setcookie("busqueda_reservas_$name", $criterios[$name], $cookie_expire, "/");
    }
} else {
    foreach ($cookie_names as $name) {
        $criterios[$name] = $_COOKIE["busqueda_reservas_$name"] ?? '';
    }
}

$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;

$errores = [];
$criterios['motivo'] = trim($criterios['motivo']);

if ($criterios['fecha_inicio'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $criterios['fecha_inicio'])) {
    $errores['fecha_inicio'] = "Formato de fecha inicio inválido (AAAA-MM-DD)";
}
if ($criterios['fecha_fin'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $criterios['fecha_fin'])) {
    $errores['fecha_fin'] = "Formato de fecha fin inválido (AAAA-MM-DD)";
}

if ($criterios['fecha_inicio'] === '' && $criterios['fecha_fin'] !== '') {
    $errores['fecha_inicio'] = "Debe indicar también la fecha de inicio";
}
if ($criterios['fecha_fin'] === '' && $criterios['fecha_inicio'] !== '') {
    $errores['fecha_fin'] = "Debe indicar también la fecha de fin";
}

if (
    empty($errores['fecha_inicio']) && empty($errores['fecha_fin']) &&
    $criterios['fecha_inicio'] !== '' && $criterios['fecha_fin'] !== ''
) {
    if ($criterios['fecha_inicio'] > $criterios['fecha_fin']) {
        $errores['fecha_fin'] = "La fecha fin no puede ser anterior a la fecha inicio";
    }
}

function esIdValido($pdo, $tabla, $id)
{
    if (!ctype_digit($id)) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM $tabla WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() !== false;
}

if ($criterios['usuario_id'] !== '') {
    if (!esIdValido($pdo, 'usuarios', $criterios['usuario_id'])) {
        $errores['usuario_id'] = "Usuario no válido";
    }
}

if ($criterios['sala_id'] !== '') {
    if (!esIdValido($pdo, 'salas', $criterios['sala_id'])) {
        $errores['sala_id'] = "Sala no válida";
    }
}

if (!in_array($criterios['orden'], ['fecha', 'sala'], true)) {
    $criterios['orden'] = 'fecha';
}

$criterios['items_por_pagina'] = filter_var($criterios['items_por_pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($criterios['items_por_pagina'] === false) {
    $criterios['items_por_pagina'] = 5;
}

$offset = ($pagina - 1) * $criterios['items_por_pagina'];

$salas = $pdo->query("SELECT id, nombre FROM salas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$reservas = [];
$total_reservas = 0;
$total_paginas = 1;

if (empty($errores)) {
    $sql = "SELECT r.*, s.nombre AS sala_nombre, u.nombre AS usuario_nombre
            FROM reservas r
            JOIN salas s ON r.sala_id = s.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE 1=1";

    $params = [];

    if ($criterios['motivo'] !== '') {
        $sql .= " AND r.motivo LIKE :motivo";
        $params['motivo'] = '%' . $criterios['motivo'] . '%';
    }
    if ($criterios['fecha_inicio'] !== '') {
        $sql .= " AND r.fecha >= :fecha_inicio";
        $params['fecha_inicio'] = $criterios['fecha_inicio'];
    }
    if ($criterios['fecha_fin'] !== '') {
        $sql .= " AND r.fecha <= :fecha_fin";
        $params['fecha_fin'] = $criterios['fecha_fin'];
    }
    if ($criterios['usuario_id'] !== '') {
        $sql .= " AND r.usuario_id = :usuario_id";
        $params['usuario_id'] = $criterios['usuario_id'];
    }
    if ($criterios['sala_id'] !== '') {
        $sql .= " AND r.sala_id = :sala_id";
        $params['sala_id'] = $criterios['sala_id'];
    }

    if ($criterios['orden'] === 'sala') {
        $sql .= " ORDER BY s.nombre";
    } else {
        $sql .= " ORDER BY r.fecha, r.hora_inicio";
    }

    $count_sql = "SELECT COUNT(*) FROM ($sql) AS sub";
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_reservas = (int) $stmt_count->fetchColumn();
    $total_paginas = max(1, ceil($total_reservas / $criterios['items_por_pagina']));

    $sql .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $criterios['items_por_pagina'];
    $params['offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(":$k", $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
    }
}

$paramsBase = $criterios;
$paramsBase['pagina'] = max(1, $pagina - 1);
$prevUrl = '?' . http_build_query($paramsBase);
$paramsBase['pagina'] = min($total_paginas, $pagina + 1);
$nextUrl = '?' . http_build_query($paramsBase);

$esAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda de Reservas</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="busqueda-reservas">
    <h1>Buscar Reservas</h1>

    <form method="get" action="busqueda_reservas.php" novalidate>
        <fieldset>
            <legend>Filtros de búsqueda</legend>

            <label>Motivo:
                <input type="text" name="motivo" value="<?= htmlspecialchars($criterios['motivo']) ?>">
            </label>

            <label>Fecha inicio:
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($criterios['fecha_inicio']) ?>">
            </label>

            <label>Fecha fin:
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($criterios['fecha_fin']) ?>">
            </label>

            <?php if ($esAdmin): ?>
                <label>Usuario:
                    <select name="usuario_id">
                        <option value="">-- Todos --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $criterios['usuario_id'] == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

            <label>Sala:
                <select name="sala_id">
                    <option value="">-- Todas --</option>
                    <?php foreach ($salas as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $criterios['sala_id'] == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Ordenar por:
                <select name="orden">
                    <option value="fecha" <?= $criterios['orden'] === 'fecha' ? 'selected' : '' ?>>Fecha</option>
                    <option value="sala" <?= $criterios['orden'] === 'sala' ? 'selected' : '' ?>>Sala</option>
                </select>
            </label>

            <label>Items por página:
                <input type="number" name="items_por_pagina" min="1"
                    value="<?= htmlspecialchars($criterios['items_por_pagina']) ?>">
            </label>

            <button type="submit">Buscar</button>
        </fieldset>
    </form>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($errores)): ?>
        <section>
            <?php $mostrando = min($pagina * $criterios['items_por_pagina'], $total_reservas); ?>
            <p>Mostrando <?= $mostrando ?> de <?= $total_reservas ?> reservas</p>

            <?php if (count($reservas) === 0): ?>
                <ul><li>No hay reservas que coincidan.</li></ul>
            <?php else: ?>
                <ul>
                    <?php foreach ($reservas as $r): ?>
                        <li>
                            <?php if ($esAdmin): ?>
                                <strong>Usuario:</strong> <?= htmlspecialchars($r['usuario_nombre']) ?><br>
                            <?php endif; ?>
                            <strong>Sala:</strong> <?= htmlspecialchars($r['sala_nombre']) ?><br>
                            <strong>Motivo:</strong> <?= htmlspecialchars($r['motivo']) ?><br>
                            <strong>Fecha y Hora:</strong> <?= htmlspecialchars($r['fecha']) ?>
                            <?= formatHoraSinSegundos($r['hora_inicio']) ?> - <?= formatHoraSinSegundos($r['hora_fin']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <nav class="paginacion">
            <?php if ($pagina > 1): ?>
                <a href="<?= $prevUrl ?>" title="Página anterior">&#8249;</a>
            <?php else: ?>
                <a href="#" class="disabled">&#8249;</a>
            <?php endif; ?>

            <span class="current">Página <?= $pagina ?> de <?= $total_paginas ?></span>

            <?php if ($pagina < $total_paginas): ?>
                <a href="<?= $nextUrl ?>" title="Página siguiente">&#8250;</a>
            <?php else: ?>
                <a href="#" class="disabled">&#8250;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
