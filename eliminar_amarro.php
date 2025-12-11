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

$amarroId = isset($input['amarro_id']) ? (int) $input['amarro_id'] : 0;

if ($amarroId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de amarro inválido']);
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

    // Verificar que el amarro existe
    $stmt = $pdo->prepare('SELECT id FROM amarros WHERE id = ?');
    $stmt->execute([$amarroId]);
    $amarro = $stmt->fetch();

    if (!$amarro) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Amarro no encontrado']);
        exit;
    }

    // Eliminar el amarro (los documentos se eliminan automáticamente por CASCADE)
    $stmt = $pdo->prepare('DELETE FROM amarros WHERE id = ?');
    $stmt->execute([$amarroId]);

    echo json_encode([
        'success' => true,
        'message' => 'Amarro eliminado correctamente',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el amarro',
        'error'   => $e->getMessage(),
    ]);
}
