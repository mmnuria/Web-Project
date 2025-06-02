<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['clave'])) {
        // Guardar datos del usuario en sesión
        $_SESSION['user'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol']
        ];

        // Registrar en log_eventos que usuario se ha identificado
        $descripcion = "Usuario '{$usuario['email']}' se ha identificado correctamente.";
        $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $logStmt->execute([$descripcion]);

        header("Location: index.php");
        exit();
    } else {
        // Registrar intento fallido
        $descripcion = "Intento de identificación fallido con email: '{$email}'.";
        $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $logStmt->execute([$descripcion]);

        $error = "Credenciales inválidas.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="css/styles.css"> 
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="main-content">
        <div class="login-container">
            <h2>Iniciar sesión</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" class="login-form">
                <label for="email">Correo electrónico:</label>
                <input type="email" name="email" required>

                <label for="password">Contraseña:</label>
                <input type="password" name="password" required>

                <button type="submit">Iniciar sesión</button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
