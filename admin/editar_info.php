<?php
require_once '../includes/db.php';

// Control simple de sesión (supongo que ya tienes control de login y rol)
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$mensaje = '';

// Recuperar info actual
$stmt = $pdo->query("SELECT * FROM informacion_sitio LIMIT 1");
$info = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_centro = trim($_POST['nombre_centro']);
    $descripcion = trim($_POST['descripcion']);
    $horario_inicio = $_POST['horario_inicio'];
    $horario_fin = $_POST['horario_fin'];

    // Validar horario
    if ($horario_inicio >= $horario_fin) {
        $error = "El horario de inicio debe ser anterior al de fin.";
    }

    // Procesar logo (si se sube uno nuevo)
    $logoBinario = $info['logo']; // mantengo el anterior por defecto

    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        if (exif_imagetype($_FILES['logo']['tmp_name'])) {
            $logoBinario = file_get_contents($_FILES['logo']['tmp_name']);
        } else {
            $error = "El archivo subido no es una imagen válida.";
        }
    }

    if (!$error) {
        // Actualizar la fila existente
        $stmt = $pdo->prepare("UPDATE informacion_sitio SET 
            nombre_centro = ?, descripcion = ?, horario_inicio = ?, horario_fin = ?, logo = ?
            WHERE id = ?");
        $stmt->execute([$nombre_centro, $descripcion, $horario_inicio, $horario_fin, $logoBinario, $info['id']]);
        $mensaje = "Información actualizada correctamente.";
        // Recargar info para mostrar actualizado
        $stmt = $pdo->query("SELECT * FROM informacion_sitio LIMIT 1");
        $info = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Información del Sitio</title>
<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="main-content">
    <h2>Editar Información del Centro Docente</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($mensaje): ?>
        <div class="success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="nombre_centro">Nombre del Centro:</label>
        <input type="text" id="nombre_centro" name="nombre_centro" value="<?php echo htmlspecialchars($info['nombre_centro']); ?>" required>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($info['descripcion']); ?></textarea>

        <label for="horario_inicio">Horario Inicio:</label>
        <input type="time" id="horario_inicio" name="horario_inicio" value="<?php echo htmlspecialchars($info['horario_inicio']); ?>" required>

        <label for="horario_fin">Horario Fin:</label>
        <input type="time" id="horario_fin" name="horario_fin" value="<?php echo htmlspecialchars($info['horario_fin']); ?>" required>

        <label for="logo">Logo (subir nueva imagen para cambiar):</label><br>
        <?php if ($info['logo']): ?>
            <img src="mostrar_logo.php" alt="Logo actual" style="max-height: 150px;"><br>
        <?php endif; ?>
        <input type="file" name="logo" accept="image/*">

        <button type="submit">Guardar cambios</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
