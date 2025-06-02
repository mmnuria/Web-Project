<?php
session_start();
require_once 'includes/db.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: salas.php');
    exit;
}

$sala_id = (int) $_GET['id'];

// Obtener datos actuales de la sala
$stmt = $pdo->prepare("SELECT * FROM salas WHERE id = ?");
$stmt->execute([$sala_id]);
$sala = $stmt->fetch();

if (!$sala) {
    echo "Sala no encontrada.";
    exit;
}

$usuario_id = $_SESSION['user']['id'];

// Función para registrar eventos en log_eventos
function registrarLog($pdo, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
    $stmt->execute([$descripcion]);
}

// Actualizar datos textuales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_sala'])) {
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $num_puestos = (int) $_POST['num_puestos'];
    $descripcion = $_POST['descripcion'];
    $reservable = isset($_POST['reservable']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE salas SET nombre = ?, ubicacion = ?, num_puestos = ?, descripcion = ?, reservable = ? WHERE id = ?");
    $stmt->execute([$nombre, $ubicacion, $num_puestos, $descripcion, $reservable, $sala_id]);

    registrarLog($pdo, "Sala ID $sala_id actualizada por usuario ID $usuario_id. Datos: nombre='$nombre', ubicacion='$ubicacion', puestos=$num_puestos, reservable=$reservable");

    header("Location: editar_sala.php?id=$sala_id");
    exit;
}

// Subida de nuevas fotos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_foto']) && !empty($_FILES['foto']['tmp_name'])) {
    $tmpName = $_FILES['foto']['tmp_name'];
    $fileType = mime_content_type($tmpName);

    if (str_starts_with($fileType, 'image/')) {
        $imagen = file_get_contents($tmpName);
        $stmt = $pdo->prepare("INSERT INTO fotos_salas (sala_id, foto, tipo_mime) VALUES (?, ?, ?)");
        $stmt->execute([$sala_id, $imagen, $fileType]);

        $foto_id = $pdo->lastInsertId();
        registrarLog($pdo, "Foto ID $foto_id añadida a sala ID $sala_id por usuario ID $usuario_id.");
    }

    header("Location: editar_sala.php?id=$sala_id");
    exit;
}

// Eliminar foto
if (isset($_GET['eliminar_foto'])) {
    $foto_id = (int) $_GET['eliminar_foto'];
    $stmt = $pdo->prepare("DELETE FROM fotos_salas WHERE id = ? AND sala_id = ?");
    $stmt->execute([$foto_id, $sala_id]);

    registrarLog($pdo, "Foto ID $foto_id eliminada de sala ID $sala_id por usuario ID $usuario_id.");

    header("Location: editar_sala.php?id=$sala_id");
    exit;
}

// Obtener todas las fotos actuales
$stmt = $pdo->prepare("SELECT * FROM fotos_salas WHERE sala_id = ?");
$stmt->execute([$sala_id]);
$fotos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Sala</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <div class="container-editar-salas">

        <h1>Editar Sala: <?= htmlspecialchars($sala['nombre']) ?></h1>

        <h2>Datos de la sala</h2>
        <form id="editar-sala-form" method="POST">
            <input type="hidden" name="actualizar_sala" value="1">
            <label>Nombre: <input type="text" name="nombre" value="<?= htmlspecialchars($sala['nombre']) ?>" required></label><br>
            <label>Ubicación: <input type="text" name="ubicacion" value="<?= htmlspecialchars($sala['ubicacion']) ?>" required></label><br>
            <label>Número de puestos: <input type="number" name="num_puestos" value="<?= $sala['num_puestos'] ?>" required></label><br>
            <label>Descripción:<br>
                <textarea name="descripcion" rows="4" cols="50"><?= htmlspecialchars($sala['descripcion']) ?></textarea>
            </label><br>
            <label><input type="checkbox" name="reservable" <?= $sala['reservable'] ? 'checked' : '' ?>>
                ¿Reservable?</label><br>
            <button type="submit">Guardar cambios</button>
        </form>

        <!-- Fotos actuales -->
        <h2>Fotos actuales</h2>
        <?php if (count($fotos) > 0): ?>
            <div class="fotos-container">
                <?php foreach ($fotos as $foto): ?>
                    <div class="foto-item">
                        <img src="data:<?= $foto['tipo_mime'] ?>;base64,<?= base64_encode($foto['foto']) ?>" alt="Foto sala">
                        <a href="editar_sala.php?id=<?= $sala_id ?>&eliminar_foto=<?= $foto['id'] ?>"
                            onclick="return confirm('¿Eliminar esta foto?')">Eliminar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay fotos añadidas.</p>
        <?php endif; ?>

        <h2>Añadir nueva foto</h2>
        <form id="subir-foto-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="subir_foto" value="1">
            <label>Foto: <input type="file" name="foto" accept="image/*" required></label><br>
            <button type="submit">Subir foto</button>
        </form>

        <br><a href="salas.php">← Volver a la lista de salas</a>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>
