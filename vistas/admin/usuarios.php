<?php
require '../db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    die("Acceso denegado");
}

$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();

foreach ($usuarios as $u) {
    echo $u['nombre'] . " - " . $u['email'] . "<br>";
}
