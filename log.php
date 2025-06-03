<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (empty($_SESSION['user']) || !($_SESSION['user']['rol'] ?? false)) {
    die('Acceso restringido.');
}

$logs = $pdo->query("SELECT * FROM log_eventos ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Log del sistema</title>
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/nav.php'; ?>
    <div class="log-container">

        <h1>Eventos recientes</h1>
        <table class="log-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['fecha']) ?></td>
                        <td><?= htmlspecialchars($log['descripcion']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>