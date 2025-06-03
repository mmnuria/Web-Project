<?php
require_once 'includes/db.php';
session_start();

if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die("Acceso no autorizado.");
}

$error = '';
$mensaje = '';

// Obtener datos actuales
$stmt = $pdo->query("SELECT * FROM informacion_sitio LIMIT 1");
$info = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_centro = trim($_POST['nombre_centro']);
    $descripcion = trim($_POST['descripcion']);
    $horario_inicio = $_POST['horario_inicio'];
    $horario_fin = $_POST['horario_fin'];
    $logoBin = $info['logo']; // mantener si no se cambia

    if ($horario_inicio >= $horario_fin) {
        $error = "El horario de inicio debe ser anterior al de fin.";
    }

    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        if (exif_imagetype($_FILES['logo']['tmp_name'])) {
            $logoBin = file_get_contents($_FILES['logo']['tmp_name']);
        } else {
            $error = "El archivo subido no es una imagen válida.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE informacion_sitio SET nombre_centro = ?, descripcion = ?, horario_inicio = ?, horario_fin = ?, logo = ? WHERE id = ?");
        $stmt->execute([$nombre_centro, $descripcion, $horario_inicio, $horario_fin, $logoBin, $info['id']]);
        $mensaje = "Información actualizada correctamente.";
        $info = $pdo->query("SELECT * FROM informacion_sitio LIMIT 1")->fetch();

        // Registrar en logs
        $usuario_nombre = $_SESSION['user']['nombre'] ?? 'Usuario desconocido';
        $descripcion_log = "El usuario '$usuario_nombre' actualizó la información del sitio web.";
        $pdo->prepare("INSERT INTO log_eventos (descripcion, fecha) VALUES (?, NOW())")
            ->execute([$descripcion_log]);
    }
}

// Variables seguras para evitar warnings y deprecateds
$nombre_centro_safe = htmlspecialchars($info['nombre_centro'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$descripcion_safe = htmlspecialchars($info['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$horario_inicio_safe = htmlspecialchars($info['horario_inicio'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$horario_fin_safe = htmlspecialchars($info['horario_fin'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$logo_exists = !empty($info['logo']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Configuración del Sitio</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <main class="configuracion-container">
        <h2 class="configuracion-title">Configuración del Sitio</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php elseif ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="configuracion-form" novalidate>
            <div class="form-group">
                <label for="nombre_centro">Nombre del centro:</label>
                <input type="text" id="nombre_centro" name="nombre_centro" value="<?= $nombre_centro_safe ?>" required />
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?= $descripcion_safe ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="horario_inicio">Horario de apertura:</label>
                    <input type="time" id="horario_inicio" name="horario_inicio" value="<?= $horario_inicio_safe ?>" required />
                </div>

                <div class="form-group">
                    <label for="horario_fin">Horario de cierre:</label>
                    <input type="time" id="horario_fin" name="horario_fin" value="<?= $horario_fin_safe ?>" required />
                </div>
            </div>

            <div class="form-group">
                <label for="logo">Logo (dejar vacío para mantener el actual):</label><br />
                <?php if ($logo_exists): ?>
                    <img src="mostrar_logo.php" alt="Logo actual" class="logo-preview" /><br />
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept="image/*" />
            </div>

            <button type="submit" class="btn-submit">Guardar cambios</button>
        </form>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
