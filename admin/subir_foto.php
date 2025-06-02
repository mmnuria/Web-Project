<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && isset($_POST['sala_id'])) {
    $sala_id = (int)$_POST['sala_id'];
    $tmpName = $_FILES['foto']['tmp_name'];
    $mime = mime_content_type($tmpName);

    // Validación
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $tiposPermitidos)) {
        die("Tipo de imagen no válido");
    }

    $foto = file_get_contents($tmpName);

    $stmt = $pdo->prepare("INSERT INTO fotos_salas (sala_id, foto, tipo_mime) VALUES (?, ?, ?)");
    $stmt->execute([$sala_id, $foto, $mime]);

    header("Location: editar_sala.php?id=$sala_id");
    exit;
}
?>
