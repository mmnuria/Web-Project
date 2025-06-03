<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $foto = trim($_POST['foto'] ?? ''); // Opcional
    $rol = $_POST['rol'] ?? '';

    if ($nombre && $apellidos && $dni && $email && $clave && $rol) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, dni, email, clave, foto, rol) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $apellidos, $dni, $email, password_hash($clave, PASSWORD_DEFAULT), $foto ?: null, $rol]);

        $usuario_id = $pdo->lastInsertId();

        // Log del evento
        $descripcion = "Usuario ID $usuario_id ('$nombre $apellidos') creado por admin ID " . $_SESSION['user']['id'];
        $stmtLog = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $stmtLog->execute([$descripcion]);

        $mensaje = "Usuario '$nombre $apellidos' añadido correctamente.";
    } else {
        $mensaje = "Por favor, complete todos los campos obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<main id="crear-usuario-page">
    <h1>Crear nuevo usuario</h1>

    <?php if ($mensaje): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form action="crear_usuario.php" method="POST" class="form-usuario" novalidate>
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required>

        <label for="dni">DNI *</label>
        <input type="text" id="dni" name="dni" required>

        <label for="email">Correo electrónico *</label>
        <input type="email" id="email" name="email" required>

        <label for="clave">Contraseña *</label>
        <input type="password" id="clave" name="clave" required>

        <label for="foto">Foto (opcional)</label>
        <input type="text" id="foto" name="foto" placeholder="URL de la imagen (opcional)">

        <label for="rol">Rol *</label>
        <select id="rol" name="rol" required>
            <option value="">-- Seleccione --</option>
            <option value="admin">admin</option>
            <option value="cliente">cliente</option>
            <option value="usuario">usuario</option>
        </select>

        <button type="submit">Crear Usuario</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
