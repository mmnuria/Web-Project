<?php
require_once 'db.php'; // o la ruta que uses para conectar a la BBDD

// Carga info del centro
$stmt = $pdo->query("SELECT nombre_centro FROM informacion_sitio LIMIT 1");
$info = $stmt->fetch();

$nombreCentro = $info['nombre_centro'] ?? 'Proyecto Web';
?>

<header>
    <div class="contenedor">
        <img src="mostrar_logo.php" alt="Logo del centro" height="50">
        <h1><?= htmlspecialchars($nombreCentro) ?></h1>
    </div>
</header>
