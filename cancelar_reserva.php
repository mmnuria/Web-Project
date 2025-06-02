<?php
/* cancelar_reserva.php – Elimina una reserva propia */
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id'])) {
    $reserva_id = (int)$_POST['reserva_id'];
    $fecha      = $_POST['fecha'] ?? date('Y-m-d');

    /* ¿La reserva es del usuario? */
    $st = $pdo->prepare("SELECT 1 FROM reservas WHERE id = ? AND usuario_id = ?");
    $st->execute([$reserva_id, $usuario_id]);

    if ($st->fetchColumn()) {
        $pdo->prepare("DELETE FROM reservas WHERE id = ?")->execute([$reserva_id]);

        // Registrar en log_eventos la cancelación
        $descripcion = "Reserva ID $reserva_id cancelada por usuario ID $usuario_id";
        $stmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
        $stmt->execute([$descripcion]);
    }
    header('Location: reservas.php?fecha=' . $fecha);
    exit;
}
header('Location: reservas.php');
