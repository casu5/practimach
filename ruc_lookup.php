<?php
// ruc_lookup.php
header('Content-Type: application/json; charset=utf-8');

$ruc = trim($_POST['ruc'] ?? '');

if (!validarRuc($ruc)) {
    echo json_encode([
        'success' => false,
        'message' => 'El RUC debe tener exactamente 11 dígitos numéricos.',
        'data'    => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Consultar SUNAT
$info = consultarRucSunat($ruc);

if (!$info['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo consultar el RUC: ' . $info['message'],
        'data'    => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Datos “normalizados” que devuelve consultarRucSunat
$estado    = $info['estado_contribuyente'] ?? '';
$condicion = $info['condicion_domicilio'] ?? '';

$estadoUpper    = strtoupper($estado);
$condicionUpper = strtoupper($condicion);

/**
 * Regla:
 * - Si estado/condición vienen vacíos → NO bloqueamos (la API no lo envía).
 * - Si tuvieran valor y NO fueran ACTIVO/HABIDO → ahí sí podríamos bloquear.
 *   (en este endpoint concreto, estado viene vacío, así que no se bloquea).
 */
if ($estadoUpper !== '' && $condicionUpper !== '') {
    if (!($estadoUpper === 'ACTIVO' && $condicionUpper === 'HABIDO')) {
        echo json_encode([
            'success' => false,
            'message' => "El RUC fue encontrado, pero no cumple las condiciones (Estado: {$estadoUpper}, Condición: {$condicionUpper}).",
            'data'    => [
                'ruc'                  => $info['ruc'] ?? $ruc,
                'razon_social'         => $info['razon_social'] ?? '',
                'estado_contribuyente' => $estado,
                'condicion_domicilio'  => $condicion,
                'direccion'            => $info['direccion']      ?? '',
                'distrito'             => $info['distrito']       ?? '',
                'provincia'            => $info['provincia']      ?? '',
                'departamento'         => $info['departamento']   ?? '',
                'id_departamento'      => $info['id_departamento']?? ''
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Si todo OK
echo json_encode([
    'success' => true,
    'message' => 'RUC válido.',
    'data'    => [
        'ruc'                  => $info['ruc'] ?? $ruc,
        'razon_social'         => $info['razon_social'] ?? '',
        'estado_contribuyente' => $estado,
        'condicion_domicilio'  => $condicion,
        // NUEVOS CAMPOS: todo lo que trae SUNAT (menos ids de distrito/provincia)
        'direccion'            => $info['direccion']      ?? '',
        'distrito'             => $info['distrito']       ?? '',
        'provincia'            => $info['provincia']      ?? '',
        'departamento'         => $info['departamento']   ?? '',
        'id_departamento'      => $info['id_departamento']?? ''
    ]
], JSON_UNESCAPED_UNICODE);
exit;

// ========= helpers =========

function validarRuc($ruc) {
    return preg_match('/^[0-9]{11}$/', $ruc) === 1;
}

/**
 * Consulta RUC en SUNAT usando:
 * https://ww1.sunat.gob.pe/ol-ti-itfisdenreg/itfisdenreg.htm?accion=obtenerDatosRuc&nroRuc=
 *
 * Formato real de respuesta (por tu captura):
 * {
 *   "message": "success",
 *   "lista": [
 *      {
 *          "idprovincia": "01",
 *          "iddistrito": "10",
 *          "apenomdenunciado": "...",
 *          "iddepartamento": "23",
 *          "direstablecimiento": "...",
 *          "desdistrito": "...",
 *          "desprovincia": "...",
 *          "desdepartamento": "..."
 *      }
 *   ]
 * }
 */
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

    // Decodificamos el JSON tal como viene
    $data = json_decode($resp, true);

    if (!is_array($data) || !isset($data['message']) || $data['message'] !== 'success') {
        return [
            'success' => false,
            'message' => 'La respuesta de SUNAT no indica success.',
        ];
    }

    if (!isset($data['lista']) || !is_array($data['lista']) || count($data['lista']) === 0) {
        return [
            'success' => false,
            'message' => 'La respuesta de SUNAT no contiene registros en "lista".',
        ];
    }

    // Tomamos el primer registro de la lista
    $item = $data['lista'][0];

    // Campos reales de tu endpoint
    $razonSocial   = $item['apenomdenunciado']   ?? '';
    $direccion     = $item['direstablecimiento'] ?? '';
    $distrito      = $item['desdistrito']        ?? '';
    $provincia     = $item['desprovincia']       ?? '';
    $departamento  = $item['desdepartamento']    ?? '';
    $idDepartamento= $item['iddepartamento']     ?? '';

    // Armamos un texto de domicilio “bonito”
    $partes = array_filter([$direccion, $distrito, $provincia, $departamento]);
    $condicionDomicilio = implode(' - ', $partes);

    return [
        'success'              => true,
        'message'              => 'Consulta exitosa.',
        'ruc'                  => $ruc,
        'razon_social'         => $razonSocial,
        'estado_contribuyente' => '', // este endpoint no lo trae
        'condicion_domicilio'  => $condicionDomicilio,
        // devolvemos todo lo útil del JSON
        'direccion'            => $direccion,
        'distrito'             => $distrito,
        'provincia'            => $provincia,
        'departamento'         => $departamento,
        'id_departamento'      => $idDepartamento,
        'raw'                  => $data
    ];
}
