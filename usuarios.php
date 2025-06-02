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

    <main>
        <h1>Listado de Usuarios</h1>

        <?php if ($mensaje): ?>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

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
                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>">Editar</a>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Añadir nuevo usuario</h2>

        <form action="usuarios.php" method="POST" novalidate>
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required>

            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <option value="">-- Seleccione --</option>
                <option value="admin">Administrador</option>
                <option value="user">Usuario</option>
            </select>

            <button type="submit">Añadir Usuario</button>
        </form>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
