<?php
function log_evento(string $descripcion) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO log_eventos (descripcion) VALUES (?)");
    $stmt->execute([$descripcion]);
}
