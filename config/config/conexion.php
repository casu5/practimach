<?php
    

$host     = "apstivigil.edu.pe";
$usuario  = "iespvigil_unoche_e8";        
$password = "Ze7@pLt#5NvM";            
$bd       = "iespvigil_unoche_e8";

$mysqli = new mysqli($host, $usuario, $password, $bd);

if ($mysqli->connect_errno) {
    die("Error de conexión a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
