<?php
require_once 'includes/db.php';
$error = '';

function validarDNI($dni) {
    $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
    if (preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        $numero = substr($dni, 0, 8);
        $letra = substr($dni, -1);
        return $letra === $letras[$numero % 23];
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $dni = strtoupper(trim($_POST['dni']));
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $clave = $_POST['password'];
    $foto = $_FILES['foto'];

    if (!$nombre || !$apellidos || !$email || strlen($clave) < 4 || !validarDNI($dni)) {
        $error = "Datos inválidos. Verifica todos los campos y el formato del DNI.";
    } else {
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        $fotoBinaria = null;

        if ($foto['size'] > 0 && exif_imagetype($foto['tmp_name'])) {
            $fotoBinaria = file_get_contents($foto['tmp_name']);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, dni, email, clave, foto, rol)
                                   VALUES (?, ?, ?, ?, ?, ?, 'cliente')");
            $stmt->execute([$nombre, $apellidos, $dni, $email, $claveHash, $fotoBinaria]);
        
            // Insertar log del evento
            $descripcion = "Nuevo usuario registrado: $email";
            $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion, fecha) VALUES (?, NOW())");
            $logStmt->execute([$descripcion]);
        
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error al registrar. Puede que el correo o el DNI ya estén registrados.";
        }
        
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="main-content">
        <div class="registro-container">
            <h2>Registrarse</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="login-form">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" required>

                <label for="apellidos">Apellidos:</label>
                <input type="text" name="apellidos" required>

                <label for="dni">DNI:</label>
                <input type="text" name="dni" pattern="[0-9]{8}[A-Z]" required>

                <label for="email">Correo electrónico:</label>
                <input type="email" name="email" required>

                <label for="password">Contraseña:</label>
                <input type="password" name="password" minlength="4" required>

                <label for="foto">Fotografía:</label>
                <input type="file" name="foto" accept="image/*">

                <button type="submit">Registrarse</button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
