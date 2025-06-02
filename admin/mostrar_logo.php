<?php
require_once '../includes/db.php';

$stmt = $pdo->query("SELECT logo FROM informacion_sitio LIMIT 1");
$info = $stmt->fetch();

if ($info && $info['logo']) {
    // Aquí asumo que el logo está guardado en PNG, ajusta si usas otro formato
    header('Content-Type: image/png');
    echo $info['logo'];
} else {
    // Si no hay logo, muestra una imagen por defecto
    header('Content-Type: image/png');
    readfile('./images/logo.png'); // pon una imagen por defecto
}
