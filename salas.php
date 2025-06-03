<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Obtener salas ordenadas por nombre
$stmt = $pdo->query("SELECT * FROM salas ORDER BY nombre");
$salas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener fotos por sala
$stmtFotos = $pdo->query("SELECT sala_id, foto, tipo_mime FROM fotos_salas ORDER BY sala_id");
$fotosPorSala = [];
foreach ($stmtFotos->fetchAll(PDO::FETCH_ASSOC) as $foto) {
    $fotosPorSala[$foto['sala_id']][] = $foto;
}

// Obtener comentarios agrupados por sala, solo si usuario logueado
$comentarios = [];
if (isset($_SESSION['user'])) {
    $stmtComentarios = $pdo->query("
        SELECT c.*, u.nombre AS usuario_nombre
        FROM comentarios_salas c
        JOIN usuarios u ON u.id = c.usuario_id
        ORDER BY c.fecha DESC
    ");
    $comentarios = $stmtComentarios->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Listado de Salas</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body class="salas-body">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <main class="salas-main">
        <h1 class="salas-title">Listado de Salas</h1>

        <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
            <p class="salas-add-link">
                <a href="admin/añadir_sala.php">➕ Añadir nueva sala</a>
            </p>
        <?php endif; ?>

        <div class="salas-table-wrapper">
            <table class="salas-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Puestos</th>
                        <th>Reservable</th>
                        <th>Fotos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $sala): ?>
                        <tr>
                            <td><?= htmlspecialchars($sala['nombre']) ?></td>
                            <td><?= htmlspecialchars($sala['ubicacion']) ?></td>
                            <td><?= (int) $sala['num_puestos'] ?></td>
                            <td><?= $sala['reservable'] ? 'Sí' : 'No' ?></td>
                            <td class="sala-fotos">
                                <?php
                                if (!empty($fotosPorSala[$sala['id']])) {
                                    foreach ($fotosPorSala[$sala['id']] as $foto) {
                                        $src = 'data:' . htmlspecialchars($foto['tipo_mime']) . ';base64,' . base64_encode($foto['foto']);
                                        echo "<img src='$src' class='foto-sala' alt='Foto de sala'>";
                                    }
                                } else {
                                    echo "<span class='sin-fotos'>Sin fotos</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
                                    <div class="acciones-admin">
                                        <a href="editar_sala.php?id=<?= (int) $sala['id'] ?>" class="btn-editar">✏️ Editar</a>
                                        <form method="POST" action="eliminar_sala.php"
                                            onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta sala?');"
                                            style="display:inline;">
                                            <input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
                                            <button type="submit" class="btn-eliminar">🗑️ Eliminar</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                        </tr>

                        <?php if (isset($_SESSION['user'])): ?>
                            <tr class="fila-comentarios">
                                <td colspan="6">
                                    <div class="comentarios">
                                        <strong>Comentarios:</strong>
                                        <ul>
                                            <?php if (!empty($comentarios[$sala['id']])): ?>
                                                <?php foreach ($comentarios[$sala['id']] as $coment): ?>
                                                    <li>
                                                        <em><?= htmlspecialchars($coment['usuario_nombre']) ?>:</em>
                                                        <?= nl2br(htmlspecialchars($coment['comentario'])) ?>
                                                        <small>(<?= date('d/m/Y H:i', strtotime($coment['fecha'])) ?>)</small>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li><em>Sin comentarios.</em></li>
                                            <?php endif; ?>
                                        </ul>

                                        <form method="POST" action="includes/agregar_comentario.php" class="form-comentario">
                                            <input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
                                            <textarea name="comentario" rows="2" placeholder="Escribe un comentario..."
                                                required></textarea>
                                            <button type="submit">💬 Comentar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>