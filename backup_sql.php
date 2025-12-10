<?php
// Exporta un backup de las tablas amarros y documentos en formato .sql (estructura + datos)
header('Content-Type: application/sql; charset=utf-8');
$filename = 'backup_control_amarros_' . date('Ymd_His') . '.sql';
header('Content-Disposition: attachment; filename="' . $filename . '"');

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

    $out  = "-- Backup generado por Control de Amarros\n";
    $out .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    $out .= "SET NAMES utf8mb4;\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Estructura de tablas (según definición proporcionada)
    $out .= "DROP TABLE IF EXISTS `documentos`;\n";
    $out .= "DROP TABLE IF EXISTS `amarros`;\n\n";

    $out .= "CREATE TABLE `amarros` (\n";
    $out .= "    `id` INT PRIMARY KEY AUTO_INCREMENT,\n";
    $out .= "    `codigo_amarro` VARCHAR(50) NOT NULL UNIQUE,\n";
    $out .= "    `año` INT(4),\n";
    $out .= "    `tipo_documento` VARCHAR(100),\n";
    $out .= "    `rango_inicio` INT NOT NULL,\n";
    $out .= "    `rango_fin` INT NOT NULL,\n";
    $out .= "    `total_documentos` INT,\n";
    $out .= "    `ubicacion` VARCHAR(255),\n";
    $out .= "    `fecha_creacion` DATE DEFAULT (CURRENT_DATE()),\n";
    $out .= "    `estado` VARCHAR(50) DEFAULT 'PENDIENTE',\n";
    $out .= "    `observaciones` TEXT\n";
    $out .= ");\n\n";

    $out .= "CREATE TABLE `documentos` (\n";
    $out .= "    `id` INT PRIMARY KEY AUTO_INCREMENT,\n";
    $out .= "    `amarro_id` INT NOT NULL,\n";
    $out .= "    `numero_documento` INT NOT NULL,\n";
    $out .= "    `existe_fisicamente` BOOLEAN DEFAULT 0,\n";
    $out .= "    `fecha_revision` DATE,\n";
    $out .= "    `revisado_por` VARCHAR(150),\n";
    $out .= "    `observaciones` TEXT,\n";
    $out .= "    CONSTRAINT `fk_documentos_amarros` FOREIGN KEY (`amarro_id`) REFERENCES `amarros`(`id`) ON DELETE CASCADE,\n";
    $out .= "    UNIQUE KEY `idx_documentos_unq` (`amarro_id`, `numero_documento`)\n";
    $out .= ");\n\n";

    // Datos de amarros
    $stmt = $pdo->query('SELECT * FROM amarros');
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $cols = array_keys($row);
        $values = [];
        foreach ($cols as $col) {
            $val = $row[$col];
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $values[] = $pdo->quote($val);
            }
        }
        $out .= 'INSERT INTO `amarros` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $values) . ");\n";
    }

    $out .= "\n";

    // Datos de documentos
    $stmt = $pdo->query('SELECT * FROM documentos');
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $cols = array_keys($row);
        $values = [];
        foreach ($cols as $col) {
            $val = $row[$col];
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $values[] = $pdo->quote($val);
            }
        }
        $out .= 'INSERT INTO `documentos` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $values) . ");\n";
    }

    $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    echo $out;
} catch (Exception $e) {
    http_response_code(500);
    echo "-- Error al generar backup: " . $e->getMessage();
}
