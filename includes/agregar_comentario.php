<?php
session_start();
require_once 'auth.php';
require_once 'db.php';

// Solo usuarios registrados pueden comentar
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_id = $_SESSION['user']['id'] ?? null;
$sala_id = $_POST['sala_id'] ?? null;
$comentario = trim($_POST['comentario'] ?? '');

if (!$sala_id || $comentario === '') {
    header('Location: ../salas.php?error=datos');
    exit;
}

// Verificar que la sala existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM salas WHERE id = ?");
$stmt->execute([$sala_id]);
if ($stmt->fetchColumn() == 0) {
    header('Location: ../salas.php?error=sala_no_valida');
    exit;
}

// Insertar comentario
$stmt = $pdo->prepare("INSERT INTO comentarios_salas (sala_id, usuario_id, comentario, fecha) VALUES (?, ?, ?, NOW())");
$stmt->execute([$sala_id, $usuario_id, $comentario]);

header('Location: ../salas.php');
exit;
