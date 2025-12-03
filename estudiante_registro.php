<?php
// estudiante_registro.php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/config/config/conexion.php';

// 1. Recibir datos del formulario
$nombre    = trim($_POST['nombre']    ?? '');
$dni       = trim($_POST['dni']       ?? '');
$carrera   = trim($_POST['carrera']   ?? '');
$telefono  = trim($_POST['telefono']  ?? ''); // 👈 viene desde auth.php
$email     = trim($_POST['email']     ?? '');
$password  = $_POST['password']       ?? '';

$errores = [];

// Validaciones básicas
if ($nombre === '')   $errores[] = 'El nombre es obligatorio.';
if ($dni === '')      $errores[] = 'El DNI es obligatorio.';
if (!preg_match('/^[0-9]{8}$/', $dni)) {
    $errores[] = 'El DNI debe tener exactamente 8 dígitos.';
}
if ($carrera === '')  $errores[] = 'La carrera es obligatoria.';
if ($telefono === '') $errores[] = 'El teléfono es obligatorio.';
if (!preg_match('/^[0-9]{6,}$/', $telefono)) {
    $errores[] = 'El teléfono debe tener solo números y al menos 6 dígitos.';
}
if ($email === '')    $errores[] = 'El correo es obligatorio.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo no tiene un formato válido.';
}
if ($password === '') $errores[] = 'La contraseña es obligatoria.';
// 🔴 Eliminado el check de longitud mínima:
// if (strlen($password) < 8) {
//     $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
// }

// Si hay errores, devolvemos JSON y cortamos
if (!empty($errores)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errores)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 2. Verificar duplicados (DNI o correo)
    $stmt = $mysqli->prepare("SELECT id FROM estudiantes WHERE dni = ? OR email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Error en prepare(): ' . $mysqli->error);
    }
    $stmt->bind_param("ss", $dni, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un estudiante con ese DNI o correo.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->close();

    // 3. Insertar estudiante (INCLUYE telefono)
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    /*
      Asegúrate de que tu tabla `estudiantes` tenga, al menos:
      id (PK, AI), nombre, dni, carrera, telefono, email, password, created_at
    */
    $stmt = $mysqli->prepare("
        INSERT INTO estudiantes (nombre, dni, carrera, telefono, email, password, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) {
        throw new Exception('Error en prepare() del INSERT: ' . $mysqli->error);
    }

    $stmt->bind_param(
        "ssssss",
        $nombre,
        $dni,
        $carrera,
        $telefono,   // 👈 se inserta en la columna telefono
        $email,
        $passwordHash
    );

    $stmt->execute();

    $nuevoId = $stmt->insert_id;
    $stmt->close();

    // 4. Iniciar sesión automáticamente
    $_SESSION['user_id']   = $nuevoId;
    $_SESSION['user_role'] = 'estudiante';

    echo json_encode([
        'success' => true,
        'message' => 'Registro completado con éxito. ¡Bienvenido a PractiMach!'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar estudiante: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
