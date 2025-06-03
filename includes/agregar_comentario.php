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
$sala_id = (int)($_POST['sala_id'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($sala_id <= 0 || $comentario === '') {
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
if (!$stmt->execute([$sala_id, $usuario_id, $comentario])) {
    die('Error al insertar comentario');
}

// Redirigir manteniendo la página y añadiendo ancla para la sala comentada
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
header('Location: ../salas.php?pagina=' . $pagina . '#sala-' . $sala_id);
exit;
