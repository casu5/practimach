<?php
    

$host     = "localhost";
$usuario  = "root";        
$password = "";            
$bd       = "practimach_db";

$mysqli = new mysqli($host, $usuario, $password, $bd);

if ($mysqli->connect_errno) {
    die("Error de conexión a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
