<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['usuario']);
}

function getUserRol() {
    return $_SESSION['rol'] ?? 'visitante';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || getUserRol() !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        echo "Acceso denegado. No tienes permisos para esta página.";
        exit();
    }
}
