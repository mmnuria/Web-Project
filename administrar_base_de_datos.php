<?php
session_start();
require_once 'includes/db.php';

// Solo admins pueden acceder
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die("Acceso no autorizado.");
}

$error = '';
$success = '';

function crearAdminSiNoExiste($pdo)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, dni, email, clave, rol) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute(['Admin', 'Principal', '00000000A', 'admin@localhost', $passwordHash, 'admin']);
    }
}

// Acción: Backup
if (isset($_POST['accion']) && $_POST['accion'] === 'backup') {
    try {
        // Obtenemos todas las tablas
        $tablas = [];
        $res = $pdo->query("SHOW TABLES");
        while ($fila = $res->fetch(PDO::FETCH_NUM)) {
            $tablas[] = $fila[0];
        }

        $sqlDump = "";
        foreach ($tablas as $tabla) {
            // DROP TABLE
            $sqlDump .= "DROP TABLE IF EXISTS `$tabla`;\n";

            // CREATE TABLE
            $res2 = $pdo->query("SHOW CREATE TABLE `$tabla`");
            $fila2 = $res2->fetch(PDO::FETCH_ASSOC);
            $sqlDump .= $fila2['Create Table'] . ";\n\n";

            // INSERTS
            $res3 = $pdo->query("SELECT * FROM `$tabla`");
            while ($fila3 = $res3->fetch(PDO::FETCH_ASSOC)) {
                $campos = array_map(function ($campo) use ($pdo) {
                    return $pdo->quote($campo);
                }, array_values($fila3));
                $sqlDump .= "INSERT INTO `$tabla` VALUES (" . implode(',', $campos) . ");\n";
            }
            $sqlDump .= "\n\n";
        }

        // Enviar como descarga
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=backup_' . date('Ymd_His') . '.sql');
        header('Content-Length: ' . strlen($sqlDump));
        echo $sqlDump;
        exit;

    } catch (PDOException $e) {
        $error = "Error generando backup: " . $e->getMessage();
    }
}

// Acción: Restaurar desde backup SQL
if (isset($_POST['accion']) && $_POST['accion'] === 'restaurar') {
    if (isset($_FILES['backup_sql']) && $_FILES['backup_sql']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['backup_sql']['tmp_name'];
        $sqlContent = file_get_contents($tmpFile);

        if ($sqlContent === false) {
            $error = "No se pudo leer el archivo.";
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

                // Separar por ';'
                $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }

                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
                $pdo->commit();

                // Asegurar que hay admin
                crearAdminSiNoExiste($pdo);

                $success = "Restauración completada correctamente.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error al ejecutar la restauración: " . $e->getMessage();
            }
        }
    } else {
        $error = "Error en la subida del archivo.";
    }
}

// Acción: Reiniciar (vaciar todas las tablas)
if (isset($_POST['accion']) && $_POST['accion'] === 'reiniciar') {
    try {
        $pdo->beginTransaction();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

        // Obtener tablas
        $tablas = [];
        $res = $pdo->query("SHOW TABLES");
        while ($fila = $res->fetch(PDO::FETCH_NUM)) {
            $tablas[] = $fila[0];
        }

        // Borrar datos (DELETE) de todas las tablas
        foreach ($tablas as $tabla) {
            $pdo->exec("DELETE FROM `$tabla`");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        $pdo->commit();

        // Asegurar admin
        crearAdminSiNoExiste($pdo);

        $success = "Base de datos reiniciada correctamente.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error al reiniciar la base de datos: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Administración de Base de Datos</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="admin-db-page">


        <h1>Administración de Base de Datos</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Backup -->
        <form method="post" novalidate>
            <input type="hidden" name="accion" value="backup" />
            <button type="submit">Generar Backup (Descargar)</button>
        </form>

        <!-- Restaurar -->
        <form method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="accion" value="restaurar" />
            <label for="backup_sql">Selecciona archivo SQL para restaurar:</label>
            <input type="file" id="backup_sql" name="backup_sql" accept=".sql,text/plain" required />
            <button type="submit">Restaurar Base de Datos</button>
        </form>

        <!-- Reiniciar -->
        <form method="post" novalidate
            onsubmit="return confirm('¿Seguro que deseas reiniciar la base de datos? Se borrará toda la información.');">
            <input type="hidden" name="accion" value="reiniciar" />
            <button type="submit" style="background-color:#e74c3c; color:white;">Reiniciar Base de Datos (Borrar
                información)</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>