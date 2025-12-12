<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de entrada inválidos']);
    exit;
}

$codigoAmarro = $input['codigo_amarro'] ?? null;
$anio         = isset($input['anio']) ? (int) $input['anio'] : 0;
$revisor      = $input['revisor'] ?? '';
$rangoInicio  = $input['rango_inicio'] ?? null;
$rangoFin     = $input['rango_fin'] ?? null;
$documentos   = $input['documentos'] ?? null;
$documentosAdicionales = $input['documentos_adicionales'] ?? [];
$editId       = isset($input['edit_id']) ? (int) $input['edit_id'] : 0; // Detectar modo edición

if (!$codigoAmarro || !is_array($documentos) || $rangoInicio === null || $rangoFin === null || $anio <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$host = 'boybxypzpukgziihnmnp-mysql.services.clever-cloud.com';
$port = 3306;
$db   = 'boybxypzpukgziihnmnp';
$user = 'ue8rmrjjhpgxcyci';
// NOTA: Verifica que esta contraseña coincida con la de Clever Cloud.
$password = 'kx14eg7ctuyuP1qsviP5';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->beginTransaction();

    $totalDocumentos = count($documentos) + count($documentosAdicionales);

    // Verificar si es modo edición
    if ($editId > 0) {
        // MODO EDICIÓN: Actualizar amarro existente
        $stmtAmarro = $pdo->prepare('UPDATE amarros SET codigo_amarro = ?, `año` = ?, rango_inicio = ?, rango_fin = ?, total_documentos = ?, ubicacion = ? WHERE id = ?');
        $stmtAmarro->execute([
            $codigoAmarro,
            (int) $anio,
            (int) $rangoInicio,
            (int) $rangoFin,
            (int) $totalDocumentos,
            $revisor,
            $editId
        ]);
        
        $amarroId = $editId;
        
        // Eliminar documentos antiguos antes de insertar los nuevos
        $stmtDelete = $pdo->prepare('DELETE FROM documentos WHERE amarro_id = ?');
        $stmtDelete->execute([$amarroId]);
    } else {
        // MODO CREACIÓN: Insertar nuevo amarro
        $stmtAmarro = $pdo->prepare('INSERT INTO amarros (codigo_amarro, `año`, rango_inicio, rango_fin, total_documentos, ubicacion) VALUES (?, ?, ?, ?, ?, ?)');
        $stmtAmarro->execute([
            $codigoAmarro,
            (int) $anio,
            (int) $rangoInicio,
            (int) $rangoFin,
            (int) $totalDocumentos,
            $revisor,
        ]);
        
        $amarroId = (int) $pdo->lastInsertId();
    }

    $stmtDocumento = $pdo->prepare('INSERT INTO documentos (amarro_id, numero_documento, existe_fisicamente, observaciones) VALUES (?, ?, ?, ?)');

    foreach ($documentos as $doc) {
        if (!isset($doc['numero_documento'])) {
            continue;
        }

        $numero = (int) $doc['numero_documento'];
        $existe = !empty($doc['existe_fisicamente']) ? 1 : 0;
        $observaciones = $doc['observaciones'] ?? null;

        $stmtDocumento->execute([
            $amarroId,
            $numero,
            $existe,
            $observaciones,
        ]);
    }

    // Procesar documentos adicionales (fuera de rango)
    foreach ($documentosAdicionales as $doc) {
        if (!isset($doc['numero_documento'])) {
            continue;
        }

        $numero = (int) $doc['numero_documento'];
        $observaciones = $doc['observaciones'] ?? '';

        $stmtDocumento->execute([
            $amarroId,
            $numero,
            0, // Por defecto no existe físicamente
            $observaciones,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'message'    => $editId > 0 ? 'Amarro actualizado correctamente' : 'Revisión guardada correctamente',
        'amarro_id'  => $amarroId,
        'documentos' => $totalDocumentos,
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la revisión en base de datos',
        'error'   => $e->getMessage(),
    ]);
}
