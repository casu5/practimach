<?php
session_start();      // Iniciar sesión para poder manipularla
session_unset();      // Eliminar todas las variables de sesión
session_destroy();    // Destruir la sesión actual

// Redirigir al usuario a la página de inicio de sesión o principal
header("Location: auth.php");
exit; // Asegurarse de que el script se detenga después de la redirección
?>