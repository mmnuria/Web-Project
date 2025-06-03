<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo administradores
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: usuarios.php');
    exit;
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $clave = $_POST['clave'] ?? null;
    $foto = $_FILES['foto'] ?? null;

    if ($nombre && $apellidos && $dni && $email && $rol) {
        // Manejo de foto
        if ($foto && $foto['tmp_name']) {
            $fotoData = file_get_contents($foto['tmp_name']);
        } else {
            $fotoData = $usuario['foto']; // mantener la existente
        }

        // Si hay clave, actualizarla
        if ($clave) {
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellidos=?, dni=?, email=?, clave=?, foto=?, rol=? WHERE id=?");
            $stmt->execute([$nombre, $apellidos, $dni, $email, $clave_hash, $fotoData, $rol, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellidos=?, dni=?, email=?, foto=?, rol=? WHERE id=?");
            $stmt->execute([$nombre, $apellidos, $dni, $email, $fotoData, $rol, $id]);
        }

        $mensaje = "Usuario actualizado correctamente.";
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
    } else {
        $mensaje = "Por favor, completa todos los campos obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <div class="container-editar-usuario">
        <h1>Editar Usuario</h1>

        <?php if ($mensaje): ?>
            <p class="<?php echo strpos($mensaje, 'correctamente') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?= htmlspecialchars($mensaje) ?>
            </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-editar" novalidate>
            <label>ID (no editable):</label>
            <input type="text" value="<?= htmlspecialchars($usuario['id']) ?>" disabled>

            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

            <label>Apellidos:</label>
            <input type="text" name="apellidos" value="<?= htmlspecialchars($usuario['apellidos']) ?>" required>

            <label>DNI:</label>
            <input type="text" name="dni" value="<?= htmlspecialchars($usuario['dni']) ?>" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

            <label>Clave (dejar en blanco para no cambiar):</label>
            <input type="password" name="clave">

            <label>Foto actual:</label>
            <?php if (!empty($usuario['foto'])): ?>
                <?php
                $fotoBase64 = base64_encode($usuario['foto']);
                ?>
                <img src="data:image/jpeg;base64,<?= $fotoBase64 ?>" class="preview-foto" alt="Foto de usuario" />
            <?php else: ?>
                <p>No tiene foto subida.</p>
            <?php endif; ?>

            <label>Actualizar foto:</label>
            <input type="file" name="foto" accept="image/*">

            <label>Rol:</label>
            <select name="rol" required>
                <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="usuario" <?= $usuario['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
            </select>

            <button type="submit">Guardar cambios</button>
            <a href="usuarios.php" class="btn-cancelar">Cancelar</a>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
