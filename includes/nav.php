<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['user']['rol'] ?? null;
?>

<nav>
    <div class="nav-content">
        <ul class="nav-menu">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="salas.php">Salas</a></li>
            <li><a href="reservas.php">Reservas</a></li>

            <?php if ($rol === 'admin'): ?>
                <li><a href="añadir_sala.php">Añadir Sala</a></li>
                <li><a href="usuarios.php">Usuarios</a></li>
                <li><a href="backup.php">Backup BBDD</a></li>
                <li><a href="log.php">Log</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-right">
            <?php if (isset($_SESSION['user'])): ?>
                <div class="dropdown">
                    <button class="dropbtn"><?php echo htmlspecialchars($_SESSION['user']['nombre']); ?> ▼</button>
                    <div class="dropdown-content">
                        <a href="perfil.php">Perfil</a>
                        <a href="logout.php">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn">Iniciar sesión</a>
                <a href="registro.php" class="btn">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
