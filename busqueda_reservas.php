<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

/*────────────────────────────────────────────
  1.  Criterios de búsqueda (GET)
────────────────────────────────────────────*/
$criterios = [
    'motivo'           => $_GET['motivo']          ?? '',
    'fecha_inicio'     => $_GET['fecha_inicio']    ?? '',
    'fecha_fin'        => $_GET['fecha_fin']       ?? '',
    'usuario_id'       => $_GET['usuario_id']      ?? '',
    'sala_id'          => $_GET['sala_id']         ?? '',
    'orden'            => $_GET['orden']           ?? 'fecha',
    'items_por_pagina' => $_GET['items_por_pagina']?? 5,
];

/*  Convierte items_por_pagina a int y valida */
$criterios['items_por_pagina'] = max(1, (int)$criterios['items_por_pagina']);

/*────────────────────────────────────────────
  2.  Paginación
────────────────────────────────────────────*/
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1; // página actual
$offset = ($pagina - 1) * $criterios['items_por_pagina'];

/*────────────────────────────────────────────
  3.  Listas para el formulario
────────────────────────────────────────────*/
$salas    = $pdo->query("SELECT id, nombre FROM salas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

/*────────────────────────────────────────────
  4.  Construir consulta con filtros
────────────────────────────────────────────*/
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

/* Orden */
if ($criterios['orden'] === 'sala') {
    $sql .= " ORDER BY s.nombre";
} else {
    $sql .= " ORDER BY r.fecha, r.hora_inicio";
}

/*────────────────────────────────────────────
  5.  Total filas para paginación
────────────────────────────────────────────*/
$count_sql = "SELECT COUNT(*) FROM ($sql) AS sub";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_reservas = (int)$stmt_count->fetchColumn();
$total_paginas = max(1, ceil($total_reservas / $criterios['items_por_pagina']));

/*────────────────────────────────────────────
  6.  Añadir LIMIT y OFFSET
────────────────────────────────────────────*/
$sql .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $criterios['items_por_pagina'];
$params['offset'] = $offset;

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*────────────────────────────────────────────
  7.  Construir URLs para paginación con filtros
────────────────────────────────────────────*/
$paramsBase = $criterios;  // copia todos los filtros actuales

// Página anterior
$paramsBase['pagina'] = max(1, $pagina - 1);
$prevUrl = '?' . http_build_query($paramsBase);

// Página siguiente
$paramsBase['pagina'] = min($total_paginas, $pagina + 1);
$nextUrl = '?' . http_build_query($paramsBase);
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

    <!-- Formulario de filtros -->
    <form method="get" action="busqueda_reservas.php">
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

            <?php if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
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
                    <option value="fecha" <?= $criterios['orden'] === 'fecha' ? 'selected' : '' ?>>Fecha y hora</option>
                    <option value="sala"  <?= $criterios['orden'] === 'sala'  ? 'selected' : '' ?>>Sala</option>
                </select>
            </label>

            <label>Ítems por página:
                <input type="number" min="1" name="items_por_pagina"
                       value="<?= htmlspecialchars($criterios['items_por_pagina']) ?>">
            </label>

            <button type="submit">Aplicar criterios</button>
        </fieldset>
    </form>

    <!-- Listado resultados -->
    <section>
        <h2>Reservas encontradas (<?= $total_reservas ?>)</h2>

        <ul>
        <?php foreach ($reservas as $r): ?>
            <li>
                <?= htmlspecialchars($r['motivo']) ?> —
                <?= htmlspecialchars($r['sala_nombre']) ?> —
                <?= htmlspecialchars($r['usuario_nombre']) ?> —
                <?= $r['fecha'] ?> <?= $r['hora_inicio'] ?>-<?= $r['hora_fin'] ?>
                <?php if (
                    (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin') ||
                    (!empty($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $r['usuario_id'])
                ): ?>
                    | <a href="eliminar_reserva.php?id=<?= $r['id'] ?>"
                         onclick="return confirm('¿Seguro que deseas borrar esta reserva?')">Eliminar</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>

        <!-- Paginación con flechas -->
        <div class="paginacion">
            <a href="<?= $prevUrl ?>" class="<?= $pagina == 1 ? 'disabled' : '' ?>">&#x2190;</a>
            <a href="<?= $nextUrl ?>" class="<?= $pagina == $total_paginas ? 'disabled' : '' ?>">&#x2192;</a>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
