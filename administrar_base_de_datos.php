<?php
session_start();
require_once 'includes/db.php';

// Solo administradores
if (empty($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die("Acceso no autorizado.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'backup') {
        // Generar backup SQL manualmente
        ob_start();
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=backup_' . date('Ymd_His') . '.sql');

        $tablas = ['usuarios', 'aulas', 'reservas', 'log_eventos']; // Ajusta a tus tablas

        foreach ($tablas as $tabla) {
            echo "-- Dump de la tabla $tabla\n";
            echo "DROP TABLE IF EXISTS `$tabla`;\n";
            $createStmt = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);
            echo $createStmt['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $vals = array_map(function ($v) use ($pdo) {
                    return is_null($v) ? 'NULL' : $pdo->quote($v);
                }, $row);
                $cols = implode('`, `', array_keys($row));
                $valsStr = implode(', ', $vals);
                echo "INSERT INTO `$tabla` (`$cols`) VALUES ($valsStr);\n";
            }
            echo "\n\n";
        }
        exit;
    }

    if ($accion === 'restaurar' && isset($_FILES['sqlfile'])) {
        $sql = file_get_contents($_FILES['sqlfile']['tmp_name']);
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->beginTransaction();
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                }
            }
            $pdo->commit();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $success = "Base de datos restaurada correctamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al restaurar: " . $e->getMessage();
        }
    }

    if ($accion === 'reiniciar') {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->beginTransaction();
            $tablas = ['reservas', 'aulas', 'usuarios', 'log_eventos']; // Ajusta a tus tablas reales
            foreach ($tablas as $tabla) {
                $pdo->exec("DELETE FROM `$tabla`");
            }

            // Crear admin básico
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, dni, email, clave, rol) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute(['Admin', 'Principal', '00000000A', 'admin@example.com', $adminPass, 'admin']);

            $pdo->commit();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $success = "Base de datos reiniciada. Admin: admin@example.com / admin123";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al reiniciar: " . $e->getMessage();
        }
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