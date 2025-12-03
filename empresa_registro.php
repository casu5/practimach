<?php
// empresa_registro.php
header('Content-Type: application/json; charset=utf-8');

// ajusta la ruta según tu estructura real
require_once __DIR__ . '/config/config/conexion.php';

// 1. Datos de entrada
$ruc      = trim($_POST['ruc']      ?? '');
$sector   = trim($_POST['sector']   ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$telefono = trim($_POST['telefono'] ?? '');

$errores = [];

// Validaciones
if (!validarRuc($ruc)) {
    $errores[] = 'El RUC debe tener exactamente 11 dígitos numéricos.';
}
if ($sector === '') {
    $errores[] = 'El sector es obligatorio.';
}
if ($telefono === '') {
    $errores[] = 'El teléfono es obligatorio.';
} elseif (!preg_match('/^[0-9]{6,}$/', $telefono)) {
    $errores[] = 'El teléfono debe tener solo números y al menos 6 dígitos.';
}
if ($email === '') {
    $errores[] = 'El correo es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo no tiene un formato válido.';
}
if ($password === '') {
    $errores[] = 'La contraseña es obligatoria.';
}

if (!empty($errores)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errores),
        'verificacion' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Verificar que no exista ya ese RUC o correo en EMPRESAS
try {
    // RUC repetido
    $stmt = $mysqli->prepare("SELECT id FROM empresas WHERE ruc = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Error en la consulta de RUC: ' . $mysqli->error);
    }
    $stmt->bind_param("s", $ruc);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una empresa registrada con ese RUC.',
            'verificacion' => null
        ], JSON_UNESCAPED_UNICODE);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Correo repetido (OJO: columna email)
    $stmt = $mysqli->prepare("SELECT id FROM empresas WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Error en la consulta de email: ' . $mysqli->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una empresa registrada con ese correo.',
            'verificacion' => null
        ], JSON_UNESCAPED_UNICODE);
        $stmt->close();
        exit;
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar registros existentes: ' . $e->getMessage(),
        'verificacion' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Consultar nuevamente SUNAT para estar seguros
$infoRuc = consultarRucSunat($ruc);

if (!$infoRuc['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo verificar el RUC: ' . $infoRuc['message'],
        'verificacion' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$estadoSunat    = strtoupper($infoRuc['estado_contribuyente'] ?? '');
$condicionSunat = strtoupper($infoRuc['condicion_domicilio'] ?? '');

/**
 * Regla:
 * - Si estado/condición están vacíos → NO bloqueamos (SUNAT no devolvió claro).
 * - Si tienen valor y NO son ACTIVO/HABIDO → bloqueamos.
 */
if ($estadoSunat !== '' && $condicionSunat !== '') {
    if (!($estadoSunat === 'ACTIVO' && $condicionSunat === 'HABIDO')) {
        echo json_encode([
            'success' => false,
            'message' => "El RUC no cumple las condiciones necesarias (Estado: {$estadoSunat}, Condición: {$condicionSunat}).",
            'verificacion' => [
                'ruc'                       => $infoRuc['ruc'] ?? $ruc,
                'razon_social_sunat'        => $infoRuc['razon_social'] ?? '',
                'estado_ruc_sunat'          => $estadoSunat,
                'condicion_domicilio_sunat' => $condicionSunat
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4. Guardar empresa en estado "pendiente"
$passwordHash     = password_hash($password, PASSWORD_BCRYPT);
$razonSocialSunat = $infoRuc['razon_social'] ?? '';

try {
    /**
     * Asegúrate de que tu tabla EMPRESAS tenga las columnas:
     *  - ruc
     *  - razon_social
     *  - sector
     *  - email         👈
     *  - telefono      👈
     *  - password
     *  - estado_verificacion
     *  - razon_social_sunat
     *  - estado_ruc_sunat
     *  - condicion_domicilio_sunat
     *  - fecha_verificacion
     */
    $stmt = $mysqli->prepare("
        INSERT INTO empresas (
            ruc,
            razon_social,
            sector,
            email,
            telefono,
            password,
            estado_verificacion,
            razon_social_sunat,
            estado_ruc_sunat,
            condicion_domicilio_sunat,
            fecha_verificacion
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, NULL
        )
    ");

    if (!$stmt) {
        throw new Exception('Error en prepare() del INSERT: ' . $mysqli->error);
    }

    $razonLocal = $razonSocialSunat;

    $stmt->bind_param(
        'sssssssss',
        $ruc,
        $razonLocal,
        $sector,
        $email,
        $telefono,
        $passwordHash,
        $razonSocialSunat,
        $estadoSunat,
        $condicionSunat
    );

    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Tu empresa ha sido registrada y está en espera de verificación por el administrador.',
        'verificacion' => [
            'ruc'                       => $infoRuc['ruc'] ?? $ruc,
            'razon_social_sunat'        => $razonSocialSunat,
            'estado_ruc_sunat'          => $estadoSunat,
            'condicion_domicilio_sunat' => $condicionSunat
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la empresa: ' . $e->getMessage(),
        'verificacion' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ======== helpers ========

function validarRuc($ruc) {
    return preg_match('/^[0-9]{11}$/', $ruc) === 1;
}

function consultarRucSunat($ruc) {
    $baseUrl = 'https://ww1.sunat.gob.pe/ol-ti-itfisdenreg/itfisdenreg.htm';
    $url = $baseUrl . '?accion=obtenerDatosRuc&nroRuc=' . urlencode($ruc);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PractiMachBot/1.0; +http://localhost)'
    ]);

    $resp = curl_exec($ch);

    if ($resp === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'Error cURL: ' . $error
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => 'HTTP ' . $httpCode . ' al consultar SUNAT.'
        ];
    }

    // 1) Intento: parsear todo como JSON
    $data = json_decode($resp, true);

    // 2) Si no es JSON, intentar extraer el objeto { ... } dentro del HTML
    if (!is_array($data)) {
        $ini = strpos($resp, '{');
        $fin = strrpos($resp, '}');
        if ($ini !== false && $fin !== false && $fin > $ini) {
            $jsonStr = substr($resp, $ini, $fin - $ini + 1);
            $data = json_decode($jsonStr, true);
        }
    }

    if (!is_array($data)) {
        return [
            'success'              => true,
            'message'              => 'Consulta exitosa, pero no se pudo interpretar el formato de la respuesta.',
            'ruc'                  => $ruc,
            'razon_social'         => '',
            'estado_contribuyente' => '',
            'condicion_domicilio'  => '',
            'raw'                  => $resp
        ];
    }

    $getField = function(array $arr, array $keys, $default = '') {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && $arr[$k] !== '') {
                return $arr[$k];
            }
        }
        return $default;
    };

    $rucVal      = $getField($data, ['ruc','nroRuc','numRuc','numeroDocumento'], $ruc);
    $razonSocial = $getField($data, ['razonSocial','razon_social','nombre','desRazonSocial'], '');
    $estado      = $getField($data, ['estado','estadoContribuyente','estado_contribuyente','desEstadoContribuyente'], '');
    $condicion   = $getField($data, ['condicion','condicionDomicilio','condicion_domicilio','condDomiRuc','desCondDomiRuc'], '');

    return [
        'success'              => true,
        'message'              => 'Consulta exitosa.',
        'ruc'                  => $rucVal,
        'razon_social'         => $razonSocial,
        'estado_contribuyente' => $estado,
        'condicion_domicilio'  => $condicion,
        'raw'                  => $data
    ];
}
