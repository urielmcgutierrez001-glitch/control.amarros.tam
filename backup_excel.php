<?php
// Exporta los datos en formato CSV compatible con Excel (según query solicitado)
$filename = 'backup_control_amarros_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$host = 'boybxypzpukgziihnmnp-mysql.services.clever-cloud.com';
$port = 3306;
$db   = 'boybxypzpukgziihnmnp';
$user = 'ue8rmrjjhpgxcyci';
$password = 'kx14eg7ctuyuP1qsviP5';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$out = fopen('php://output', 'w');

// Encabezado según el SELECT solicitado
fputcsv($out, [
    'codigo_amarro',
    'anio_del_amarro',
    'tipo_documento',
    'numero_documento',
    'existe_fisicamente',
    'fecha_revision',
], ';');

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $sql = 'SELECT
                a.codigo_amarro,
                a.`año` AS anio_del_amarro,
                a.tipo_documento,
                d.numero_documento,
                d.existe_fisicamente,
                d.fecha_revision
            FROM amarros a
            JOIN documentos d ON a.id = d.amarro_id
            ORDER BY a.`año` ASC, a.codigo_amarro ASC, d.numero_documento ASC';

    foreach ($pdo->query($sql) as $row) {
        fputcsv($out, [
            $row['codigo_amarro'],
            $row['anio_del_amarro'],
            $row['tipo_documento'],
            $row['numero_documento'],
            $row['existe_fisicamente'],
            $row['fecha_revision'],
        ], ';');
    }
} catch (Exception $e) {
    // En caso de error, escribimos una sola fila con el mensaje
    fputcsv($out, ['ERROR', $e->getMessage()], ';');
}

fclose($out);
