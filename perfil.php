<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user']['id'];
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo email, contraseña y foto
    $nuevo_email = trim($_POST['email']);
    $nueva_password = $_POST['password'] ?? '';

    // Validar email
    if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email no válido.";
    } else {
        // Actualizar email y, si viene, contraseña
        try {
            if (!empty($nueva_password)) {
                if (strlen($nueva_password) < 4) {
                    $error = "La contraseña debe tener al menos 4 caracteres.";
                } else {
                    $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
                }
            }

            if (!$error) {
                // Si hay foto subida
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = mime_content_type($_FILES['foto']['tmp_name']);

                    if (!in_array($file_type, $allowed_types)) {
                        $error = "Solo se permiten imágenes JPG, PNG o GIF.";
                    } else {
                        $foto_data = file_get_contents($_FILES['foto']['tmp_name']);
                    }
                }
            }

            if (!$error) {
                // Preparar query dinámica
                if (!empty($nueva_password) && isset($foto_data)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET email = :email, contraseña = :pass, foto = :foto WHERE id = :id");
                    $stmt->bindParam(':email', $nuevo_email);
                    $stmt->bindParam(':pass', $hashed_password);
                    $stmt->bindParam(':foto', $foto_data, PDO::PARAM_LOB);
                    $stmt->bindParam(':id', $usuario_id);
                } elseif (!empty($nueva_password)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET email = :email, contraseña = :pass WHERE id = :id");
                    $stmt->bindParam(':email', $nuevo_email);
                    $stmt->bindParam(':pass', $hashed_password);
                    $stmt->bindParam(':id', $usuario_id);
                } elseif (isset($foto_data)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET email = :email, foto = :foto WHERE id = :id");
                    $stmt->bindParam(':email', $nuevo_email);
                    $stmt->bindParam(':foto', $foto_data, PDO::PARAM_LOB);
                    $stmt->bindParam(':id', $usuario_id);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
                    $stmt->bindParam(':email', $nuevo_email);
                    $stmt->bindParam(':id', $usuario_id);
                }
                $stmt->execute();

                $mensaje = "Datos actualizados correctamente.";

                // Actualizar sesión con nuevo email
                $_SESSION['user']['email'] = $nuevo_email;
            }
        } catch (Exception $e) {
            $error = "Error actualizando los datos.";
        }
    }
}

// Obtener datos actuales del usuario (nombre, email, DNI, foto)
$stmt = $pdo->prepare("SELECT nombre, apellidos, dni, email, foto FROM usuarios WHERE id = :id");
$stmt->bindParam(':id', $usuario_id);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

function mostrarImagen($foto_blob) {
    if ($foto_blob) {
        $base64 = base64_encode($foto_blob);
        return "<img src='data:image/jpeg;base64,$base64' alt='Foto de perfil' style='max-width:150px; max-height:150px; border-radius:8px;'>";
    } else {
        return "<p>No hay foto de perfil.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Perfil de usuario</title>
    <link rel="stylesheet" href="css/styles.css" />
    <style>
        .ajustes-container { max-width: 500px; margin: 2rem auto; padding: 1rem; background: #f7f7f7; border-radius: 8px; }
        label { font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 0.4rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px;
        }
        .success-msg { color: green; margin-bottom: 1rem; }
        .error-msg { color: red; margin-bottom: 1rem; }
        img { display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="ajustes-container">
            <h2>Editar información de tu cuenta</h2>

            <?php if ($mensaje): ?>
                <p class="success-msg"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="ajustes-form">
                <label>Nombre:</label>
                <input type="text" value="<?= htmlspecialchars($usuario['nombre']) ?>" disabled><br>

                <label>Apellidos:</label>
                <input type="text" value="<?= htmlspecialchars($usuario['apellidos']) ?>" disabled><br>

                <label>DNI:</label>
                <input type="text" value="<?= htmlspecialchars($usuario['dni']) ?>" disabled><br>

                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br>

                <label>Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="password" minlength="4" maxlength="50" autocomplete="new-password"><br>

                <label>Foto de perfil:</label><br>
                <?= mostrarImagen($usuario['foto']); ?>
                <input type="file" name="foto" accept="image/*"><br>

                <input type="submit" value="Actualizar" class="btn">
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
