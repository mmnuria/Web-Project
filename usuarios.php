<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo administradores pueden ver esta página (o modificar)
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = '';

// Procesar alta usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? '';

    if ($nombre && $email && $rol) {
        // Insertar usuario en BD
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, rol) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $email, $rol]);

        $usuario_id = $pdo->lastInsertId();

        // Registrar en log_eventos la creación
        $descripcion = "Usuario ID $usuario_id ('$nombre') creado por admin ID " . $_SESSION['user']['id'];
        $stmtLog = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $stmtLog->execute([$descripcion]);

        $mensaje = "Usuario '$nombre' añadido correctamente.";
    } else {
        $mensaje = "Por favor, complete todos los campos.";
    }
}

// Obtener usuarios desde BD
$stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY nombre");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Usuarios</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <main id="usuarios-page">
    <h1>Listado de Usuarios</h1>

    <?php if ($mensaje): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <div class="acciones-superiores">
        <a href="crear_usuario.php" class="btn btn-add">+ Añadir Usuario</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
                    <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
                        <td>
                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-edit">Editar</a>
                            <form method="POST" action="eliminar_usuario.php" style="display:inline;"
                                  onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-delete">Eliminar</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>


    <?php include 'includes/footer.php'; ?>
</body>

</html>