<?php
session_start();
require_once 'includes/db.php';

$usuario = $_SESSION['user'] ?? null;
$reserva_id = $_POST['reserva_id'] ?? null;
$fecha = $_POST['fecha'] ?? date('Y-m-d');

// Validaciones básicas
if (!$usuario || !$reserva_id) {
    header("Location: reservas.php?fecha=" . urlencode($fecha));
    exit;
}

// Obtener la reserva
$stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ?");
$stmt->execute([$reserva_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

// Validar permisos: el usuario debe ser el dueño o tener rol 'admin'
if ($reserva && ($reserva['usuario_id'] == $usuario['id'] || $usuario['rol'] === 'admin')) {
    $deleteStmt = $pdo->prepare("DELETE FROM reservas WHERE id = ?");
    $deleteStmt->execute([$reserva_id]);
}

header("Location: reservas.php?fecha=" . urlencode($fecha));
exit;
