<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT logo FROM informacion_sitio LIMIT 1");
    $row = $stmt->fetch();

    if ($row && !empty($row['logo'])) {
        // Suponiendo que el logo está guardado como blob en 'logo'
        $logoData = $row['logo'];

        // Detectar el tipo MIME (opcional, si sabes que es siempre PNG o JPG puedes poner fijo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($logoData);

        // Si no se detecta, pon por defecto image/png
        if (!$mimeType) {
            $mimeType = 'image/png';
        }

        header("Content-Type: $mimeType");
        header("Content-Length: " . strlen($logoData));
        echo $logoData;
        exit;
    } else {
        // No hay imagen, mostrar placeholder o nada
        header("HTTP/1.0 404 Not Found");
        echo "No logo found.";
        exit;
    }
} catch (PDOException $e) {
    header("HTTP/1.0 500 Internal Server Error");
    echo "Error al cargar logo: " . $e->getMessage();
    exit;
}
