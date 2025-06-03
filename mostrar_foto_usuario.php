<?php
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if ($usuario && !empty($usuario['foto'])) {
    header("Content-Type: image/jpeg"); 
    echo $usuario['foto'];
} else {
    http_response_code(404);
    echo "Foto no encontrada.";
}
