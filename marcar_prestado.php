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

$documentoId = isset($input['documento_id']) ? (int) $input['documento_id'] : 0;

if ($documentoId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de documento inválido']);
    exit;
}

$host = 'boybxypzpukgziihnmnp-mysql.services.clever-cloud.com';
$port = 3306;
$db   = 'boybxypzpukgziihnmnp';
$user = 'ue8rmrjjhpgxcyci';
$password = 'kx14eg7ctuyuP1qsviP5';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Verificar que el documento existe
    $stmt = $pdo->prepare('SELECT id, observaciones FROM documentos WHERE id = ?');
    $stmt->execute([$documentoId]);
    $documento = $stmt->fetch();

    if (!$documento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        exit;
    }

    // Agregar "Prestado" a las observaciones existentes
    $obsActuales = $documento['observaciones'] ?? '';
    $obsArray = array_filter(array_map('trim', explode(',', $obsActuales)));
    
    // Si ya tiene "Prestado", no hacer nada
    if (in_array('Prestado', $obsArray)) {
        echo json_encode([
            'success' => true,
            'message' => 'Este documento ya estaba marcado como Prestado',
        ]);
        exit;
    }
    
    // Agregar "Prestado"
    $obsArray[] = 'Prestado';
    $nuevasObs = implode(', ', $obsArray);

    // Actualizar observaciones
    $stmt = $pdo->prepare('UPDATE documentos SET observaciones = ? WHERE id = ?');
    $stmt->execute([$nuevasObs, $documentoId]);

    echo json_encode([
        'success' => true,
        'message' => 'Documento marcado como Prestado correctamente',
        'observaciones' => $nuevasObs,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el documento',
        'error'   => $e->getMessage(),
    ]);
}
