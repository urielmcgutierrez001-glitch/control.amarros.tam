<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$amarroId = isset($_GET['amarro_id']) ? (int) $_GET['amarro_id'] : 0;

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

    // Obtener documentos del amarro
    $stmt = $pdo->prepare('
        SELECT 
            numero_documento,
            existe_fisicamente,
            observaciones
        FROM documentos 
        WHERE amarro_id = ?
        ORDER BY numero_documento ASC
    ');
    $stmt->execute([$amarroId]);
    $documentos = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'documentos' => $documentos,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener documentos',
        'error'   => $e->getMessage(),
    ]);
}
