<?php
// config/conexion.php

$host     = "apstivigil.edu.pe";
$usuario  = "iespvigil_unoche_e8";        // cámbialo si tu MySQL tiene otro usuario
$password = "Ze7@pLt#5NvM";            // cámbialo si tu MySQL tiene contraseña
$bd       = "practimach_db";

$mysqli = new mysqli($host, $usuario, $password, $bd);

if ($mysqli->connect_errno) {
    die("Error de conexión a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
