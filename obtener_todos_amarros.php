<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
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

    $sql = 'SELECT 
                a.id,
                a.codigo_amarro,
                a.`año` AS anio,
                a.rango_inicio,
                a.rango_fin,
                a.total_documentos,
                a.ubicacion,
                a.fecha_creacion,
                a.estado,
                a.observaciones,
                COUNT(d.id) AS documentos_registrados,
                SUM(CASE WHEN d.existe_fisicamente = 0 THEN 1 ELSE 0 END) AS documentos_faltantes_count
            FROM amarros a
            LEFT JOIN documentos d ON d.amarro_id = a.id
            GROUP BY a.id, a.codigo_amarro, a.`año`, a.rango_inicio, a.rango_fin, a.total_documentos, a.ubicacion, a.fecha_creacion, a.estado, a.observaciones
            ORDER BY a.fecha_creacion DESC, a.id DESC';

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $amarros = [];
    foreach ($rows as $row) {
        $amarros[] = [
            'id' => (int) $row['id'],
            'codigo_amarro' => $row['codigo_amarro'],
            'anio' => $row['anio'] !== null ? (int) $row['anio'] : null,
            'rango_inicio' => (int) $row['rango_inicio'],
            'rango_fin' => (int) $row['rango_fin'],
            'total_documentos' => $row['total_documentos'] !== null ? (int) $row['total_documentos'] : null,
            'ubicacion' => $row['ubicacion'],
            'fecha_creacion' => $row['fecha_creacion'],
            'estado' => $row['estado'],
            'observaciones' => $row['observaciones'],
            'documentos_registrados' => (int) $row['documentos_registrados'],
            'documentos_faltantes_count' => (int) $row['documentos_faltantes_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'amarros' => $amarros,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener amarros',
        'error'   => $e->getMessage(),
    ]);
}
