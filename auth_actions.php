<?php
session_start();
require_once 'config/config/conexion.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// Si no es JSON, intentar con $_POST normal (para compatibilidad)
if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? '';

$response = ['success' => false, 'message' => 'Acción no válida'];

if ($action === 'login') {

    $email    = $mysqli->real_escape_string($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role     = $input['role'] ?? ''; // 'estudiante', 'empresa', 'admin'

    $table      = '';
    $redirect   = '';
    $name_field = ''; // campo para mostrar en sesión

    if ($role === 'estudiante') {
        $table      = 'estudiantes';
        $redirect   = 'perfil_estudiante.php';
        $name_field = 'nombre';
    } elseif ($role === 'empresa') {
        $table      = 'empresas';
        $redirect   = 'perfil_empresa.php';
        $name_field = 'razon_social';
    } elseif ($role === 'admin') {
        $table      = 'admins';
        $redirect   = 'dashboard_admin.php';
        $name_field = 'nombre';
    } else {
        echo json_encode(['success' => false, 'message' => 'Rol de usuario no válido.']);
        exit;
    }

    // Consulta según tipo de usuario
    if ($table === 'admins') {
        $stmt = $mysqli->prepare("SELECT id, password, {$name_field} AS user_name, rol FROM {$table} WHERE email = ?");
    } else {
        $stmt = $mysqli->prepare("SELECT id, password, {$name_field} AS user_name FROM {$table} WHERE email = ?");
    }

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta: ' . $mysqli->error]);
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['user_role'] = ($role === 'admin') ? $user['rol'] : $role;

            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

} elseif ($action === 'register') {

    $role          = $input['role'] ?? ''; // 'estudiante' o 'empresa'
    $email         = $mysqli->real_escape_string($input['email'] ?? '');
    $passwordPlain = $input['password'] ?? '';
    $password      = password_hash($passwordPlain, PASSWORD_DEFAULT);

    if ($role === 'estudiante') {
        // 🔹 DATOS ESTUDIANTE
        $nombre   = $mysqli->real_escape_string($input['nombre']   ?? '');
        $dni      = $mysqli->real_escape_string($input['dni']      ?? '');
        $carrera  = $mysqli->real_escape_string($input['carrera']  ?? '');
        $telefono = $mysqli->real_escape_string($input['telefono'] ?? '');

        // Validaciones mínimas (puedes endurecer si quieres)
        

        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            echo json_encode(['success' => false, 'message' => 'El DNI debe tener exactamente 8 dígitos.']);
            exit;
        }

        if (!preg_match('/^[0-9]{6,}$/', $telefono)) {
            echo json_encode(['success' => false, 'message' => 'El teléfono debe tener solo números y al menos 6 dígitos.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'El correo no tiene un formato válido.']);
            exit;
        }

        if (strlen($passwordPlain) < 8) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
            exit;
        }

        // Duplicados
        $check = $mysqli->query("SELECT id FROM estudiantes WHERE email='$email' OR dni='$dni'");
        if ($check && $check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'El correo o DNI ya están registrados.']);
            exit;
        }

        // ⬇️ AQUÍ VA TELÉFONO TAMBIÉN
        $sql  = "INSERT INTO estudiantes (nombre, dni, carrera, telefono, email, password, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Error al preparar INSERT estudiante: " . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Error en el registro (prepare).']);
            exit;
        }

        $stmt->bind_param("ssssss", $nombre, $dni, $carrera, $telefono, $email, $password);

        if ($stmt->execute()) {
            $nuevoId = $stmt->insert_id;

            // si quieres loguear de frente:
            $_SESSION['user_id']   = $nuevoId;
            $_SESSION['user_role'] = 'estudiante';
            $_SESSION['user_name'] = $nombre;

            $response = ['success' => true, 'message' => 'Estudiante registrado exitosamente.'];
        } else {
            error_log("Error al registrar estudiante: " . $stmt->error);
            $response = ['success' => false, 'message' => 'Error en el registro: ' . $stmt->error];
        }
        $stmt->close();

    } elseif ($role === 'empresa') {
        // 🔹 DATOS EMPRESA
        $razon_social = $mysqli->real_escape_string($input['razon_social'] ?? '');
        $ruc          = $mysqli->real_escape_string($input['ruc']          ?? '');
        $sector       = $mysqli->real_escape_string($input['sector']       ?? '');
        $telefono     = $mysqli->real_escape_string($input['telefono']     ?? '');

        if ($razon_social === '' || $ruc === '' || $sector === '' || $telefono === '' || $email === '' || $passwordPlain === '') {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        if (!preg_match('/^[0-9]{11}$/', $ruc)) {
            echo json_encode(['success' => false, 'message' => 'El RUC debe tener 11 dígitos.']);
            exit;
        }

        if (!preg_match('/^[0-9]{6,}$/', $telefono)) {
            echo json_encode(['success' => false, 'message' => 'El teléfono debe tener solo números y al menos 6 dígitos.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'El correo no tiene un formato válido.']);
            exit;
        }

        // OJO: aquí mantengo tu lógica original de duplicados,
        // solo que si en tu BD el correo es `correo_contacto` deberías adaptar también esto.
        $check = $mysqli->query("SELECT id FROM empresas WHERE email='$email' OR ruc='$ruc'");
        if ($check && $check->num_rows > 0) {
             echo json_encode(['success' => false, 'message' => 'El correo o RUC ya están registrados.']);
             exit;
        }

        // ⬇️ AGREGAMOS TELEFONO EN EL INSERT
        $sql = "INSERT INTO empresas (razon_social, ruc, sector, telefono, email, password, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'revision')";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Error al preparar INSERT empresa: " . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Error en el registro (prepare).']);
            exit;
        }

        $stmt->bind_param("ssssss", $razon_social, $ruc, $sector, $telefono, $email, $password);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Empresa registrado exitosamente. Tu cuenta está en revisión.'];
        } else {
            error_log("Error al registrar empresa: " . $stmt->error);
            $response = ['success' => false, 'message' => 'Error en el registro: ' . $stmt->error];
        }
        $stmt->close();

    } else {
        $response = ['success' => false, 'message' => 'Rol no válido para registro.'];
    }

    echo json_encode($response);
    exit;
}

echo json_encode($response);
