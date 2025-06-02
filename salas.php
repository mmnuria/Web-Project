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
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <h1>Listado de Salas</h1>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
        <p><a href="admin/añadir_sala.php">➕ Añadir nueva sala</a></p>
    <?php endif; ?>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
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
                    <td><?= (int)$sala['num_puestos'] ?></td>
                    <td><?= $sala['reservable'] ? 'Sí' : 'No' ?></td>
                    <td>
                        <?php
                        if (!empty($fotosPorSala[$sala['id']])) {
                            foreach ($fotosPorSala[$sala['id']] as $foto) {
                                $src = 'data:' . htmlspecialchars($foto['tipo_mime']) . ';base64,' . base64_encode($foto['foto']);
                                echo "<img src='$src' width='100' style='margin: 3px;' alt='Foto de sala'>";
                            }
                        } else {
                            echo "Sin fotos";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
                            <a href="editar_sala.php?id=<?= (int)$sala['id'] ?>">✏️ Editar</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if (isset($_SESSION['user'])): ?>
                    <tr>
                        <td colspan="6" style="background: #f9f9f9;">
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

                            <!-- Formulario para añadir comentario -->
                            <form method="POST" action="includes/agregar_comentario.php">
                                <input type="hidden" name="sala_id" value="<?= (int)$sala['id'] ?>">
                                <textarea name="comentario" rows="2" cols="60" placeholder="Escribe un comentario..." required></textarea><br>
                                <button type="submit">💬 Comentar</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
