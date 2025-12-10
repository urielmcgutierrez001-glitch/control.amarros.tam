<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['amarros_ids']) || !is_array($input['amarros_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de entrada inválidos']);
    exit;
}

$ids = array_values(array_filter($input['amarros_ids'], function ($v) {
    return is_numeric($v);
}));

if (count($ids) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se proporcionaron IDs de amarros válidos']);
    exit;
}

$estado = isset($input['estado']) && $input['estado'] !== '' ? $input['estado'] : 'COMPLETADO';

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

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE amarros SET estado = ? WHERE id IN ($placeholders)";

    $stmt = $pdo->prepare($sql);
    $params = array_merge([$estado], array_map('intval', $ids));
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Estado de amarros actualizado correctamente',
        'affected_rows' => $stmt->rowCount(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estado de amarros',
        'error'   => $e->getMessage(),
    ]);
}
