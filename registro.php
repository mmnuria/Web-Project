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
            
            // Registro de evento en log_eventos
            $descripcion = "Nuevo usuario registrado con email '{$email}'";
            $logStmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
            $logStmt->execute([$descripcion]);
            
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error al registrar. Puede que el correo o el DNI ya estén registrados.";
        }
    }
}
?>
