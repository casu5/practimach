<?php
session_start();
require_once 'config/config/conexion.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$estudiante_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$empresa_id = $input['empresa_id'] ?? null;
$action = $input['action'] ?? null; // 'like' o 'reject'

if (!$empresa_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

$response = ['success' => false, 'message' => 'Error desconocido.'];

// 1. Buscar registro existente para el par (estudiante_id, empresa_id)
$stmt_find = $mysqli->prepare("SELECT estado FROM matches WHERE estudiante_id = ? AND empresa_id = ?");
$stmt_find->bind_param("ii", $estudiante_id, $empresa_id);
$stmt_find->execute();
$res_find = $stmt_find->get_result();
$existing_match = $res_find->fetch_assoc();

if ($action === 'like') {
    if ($existing_match) {
        // Record exists, update it
        $current_estado = $existing_match['estado'];
        $new_estado = 'estudiante_gusta'; // Default if student likes

        if ($current_estado === 'empresa_gusta') {
            $new_estado = 'match'; // Mutual like!
        } else if ($current_estado === 'rechazado') {
            // Student likes, overriding previous reject by student (or company)
            $new_estado = 'estudiante_gusta';
        }
        // If it was already 'match' or 'estudiante_gusta', keep it as 'estudiante_gusta' or 'match'
        
        $stmt_update = $mysqli->prepare("UPDATE matches SET estado = ? WHERE estudiante_id = ? AND empresa_id = ?");
        $stmt_update->bind_param("sii", $new_estado, $estudiante_id, $empresa_id);
        if ($stmt_update->execute()) {
            $response = ['success' => true, 'message' => ($new_estado === 'match' ? '¡Match confirmado!' : 'Interacción de estudiante registrada.')];
        } else {
            $response = ['success' => false, 'message' => 'Error al actualizar interacción (like): ' . $mysqli->error];
        }

    } else {
        // No record, insert new one (Student initiates like)
        $new_estado = 'estudiante_gusta';
        $stmt_insert = $mysqli->prepare("INSERT INTO matches (estudiante_id, empresa_id, estado) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iis", $estudiante_id, $empresa_id, $new_estado);
        if ($stmt_insert->execute()) {
            $response = ['success' => true, 'message' => 'Interacción de estudiante registrada.'];
        } else {
            $response = ['success' => false, 'message' => 'Error al registrar interacción (like): ' . $mysqli->error];
        }
    }
} elseif ($action === 'reject') {
    if ($existing_match) {
        // Record exists, update to reject
        $new_estado = 'rechazado';
        $stmt_update = $mysqli->prepare("UPDATE matches SET estado = ? WHERE estudiante_id = ? AND empresa_id = ?");
        $stmt_update->bind_param("sii", $new_estado, $estudiante_id, $empresa_id);
        if ($stmt_update->execute()) {
            $response = ['success' => true, 'message' => 'Interacción rechazada.'];
        } else {
            $response = ['success' => false, 'message' => 'Error al actualizar interacción (reject): ' . $mysqli->error];
        }
    } else {
        // No record, insert as reject
        $new_estado = 'rechazado';
        $stmt_insert = $mysqli->prepare("INSERT INTO matches (estudiante_id, empresa_id, estado) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iis", $estudiante_id, $empresa_id, $new_estado);
        if ($stmt_insert->execute()) {
            $response = ['success' => true, 'message' => 'Interacción rechazada y registrada.'];
        } else {
            $response = ['success' => false, 'message' => 'Error al registrar interacción (reject): ' . $mysqli->error];
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Acción no válida.'];
}

echo json_encode($response);

$mysqli->close();
?>