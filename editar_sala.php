<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: salas.php');
    exit;
}

$sala_id = (int) $_GET['id'];
$mensaje = '';
$tipo_mensaje = ''; // 'error' o 'exito'

// Obtener datos actuales de la sala
$stmt = $pdo->prepare("SELECT * FROM salas WHERE id = ?");
$stmt->execute([$sala_id]);
$sala = $stmt->fetch();

if (!$sala) {
    echo "Sala no encontrada.";
    exit;
}

$usuario_id = $_SESSION['user']['id'];

function registrarLog($pdo, $descripcion)
{
    $stmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
    $stmt->execute([$descripcion]);
}

// Mostrar mensaje si se actualizó
if (isset($_GET['actualizado']) && $_GET['actualizado'] == '1') {
    $mensaje = "Sala actualizada correctamente.";
    $tipo_mensaje = "exito";
}

// Actualizar datos textuales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_sala'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $num_puestos = trim($_POST['num_puestos'] ?? '');
    $descripcion_sala = trim($_POST['descripcion'] ?? '');
    $reservable = isset($_POST['reservable']) ? 1 : 0;

    if ($nombre === '' || $ubicacion === '' || $num_puestos === '' || $descripcion_sala === '') {
        $mensaje = "Por favor, complete todos los campos obligatorios.";
        $tipo_mensaje = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE salas SET nombre = ?, ubicacion = ?, num_puestos = ?, descripcion = ?, reservable = ? WHERE id = ?");
        $stmt->execute([$nombre, $ubicacion, (int) $num_puestos, $descripcion_sala, $reservable, $sala_id]);

        registrarLog($pdo, "Sala ID $sala_id actualizada por usuario ID $usuario_id. Datos: nombre='$nombre', ubicacion='$ubicacion', puestos=$num_puestos, reservable=$reservable");

        header("Location: editar_sala.php?id=$sala_id&actualizado=1");
        exit;
    }
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

    <?php if ($mensaje): ?>
        <div id="mensaje" class="mensaje <?= $tipo_mensaje ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <script>
            window.scrollTo({ top: 0, behavior: 'smooth' });
        </script>
    <?php endif; ?>

    <div class="container-editar-salas">

        <h1>Editar Sala: <?= htmlspecialchars($sala['nombre']) ?></h1>

        <h2>Datos de la sala</h2>
        <form id="editar-sala-form" method="POST" novalidate>
            <input type="hidden" name="actualizar_sala" value="1">

            <label>Nombre:
                <input type="text" name="nombre" value="<?= htmlspecialchars($sala['nombre']) ?>" required>
            </label><br>

            <label>Ubicación:
                <input type="text" name="ubicacion" value="<?= htmlspecialchars($sala['ubicacion']) ?>" required>
            </label><br>

            <label>Número de puestos:
                <input type="number" name="num_puestos" value="<?= $sala['num_puestos'] ?>" required>
            </label><br>

            <label>Descripción:<br>
                <textarea name="descripcion" rows="4" cols="50" required><?= htmlspecialchars($sala['descripcion']) ?></textarea>
            </label><br>

            <label>
                <input type="checkbox" name="reservable" <?= $sala['reservable'] ? 'checked' : '' ?>>
                ¿Reservable?
            </label><br>

            <button type="submit">Guardar cambios</button>
        </form>

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
        <form id="subir-foto-form" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="subir_foto" value="1">
            <label>Foto: <input type="file" name="foto" accept="image/*" required></label><br>
            <button type="submit">Subir foto</button>
        </form>

    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editarSalaForm = document.getElementById('editar-sala-form');
            const subirFotoForm = document.getElementById('subir-foto-form');

            if (editarSalaForm) {
                editarSalaForm.addEventListener('submit', function (e) {
                    const nombre = editarSalaForm.nombre.value.trim();
                    const ubicacion = editarSalaForm.ubicacion.value.trim();
                    const numPuestos = editarSalaForm.num_puestos.value.trim();
                    const descripcion = editarSalaForm.descripcion.value.trim();

                    if (!nombre || !ubicacion || !numPuestos || !descripcion) {
                        e.preventDefault();
                        mostrarMensaje("Por favor, complete todos los campos obligatorios.", "error");
                    }
                });
            }

            if (subirFotoForm) {
                subirFotoForm.addEventListener('submit', function (e) {
                    const inputArchivo = subirFotoForm.querySelector('input[type="file"]');
                    const archivo = inputArchivo.files[0];

                    if (!archivo) {
                        e.preventDefault();
                        mostrarMensaje("Por favor, seleccione una imagen antes de subirla.", "error");
                        return;
                    }

                    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!tiposPermitidos.includes(archivo.type)) {
                        e.preventDefault();
                        mostrarMensaje("Solo se permiten archivos de imagen (JPEG, PNG, GIF, WebP).", "error");
                    }
                });
            }

            function mostrarMensaje(texto, tipo) {
                let contenedor = document.getElementById('mensaje');
                if (!contenedor) {
                    contenedor = document.createElement('div');
                    contenedor.id = 'mensaje';
                    document.querySelector('.container-editar-salas').prepend(contenedor);
                }

                contenedor.className = 'mensaje ' + tipo;
                contenedor.textContent = texto;

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>
</body>

</html>
