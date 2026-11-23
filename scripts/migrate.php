<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/assets/php/bootstrap.php';

try {
    $database = db();
    $columns = $database->query('SHOW COLUMNS FROM pedido')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['subtotal', 'envio', 'descuento', 'impuestos'] as $column) {
        if (!in_array($column, $columns, true)) {
            $database->exec("ALTER TABLE pedido ADD COLUMN `$column` DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            echo "Columna agregada: pedido.$column\n";
        }
    }
    $database->exec('UPDATE pedido p LEFT JOIN (
        SELECT pedido_id, SUM(cantidad * precio_unitario) AS subtotal
        FROM pedido_detalle GROUP BY pedido_id
    ) d ON d.pedido_id = p.id_pedido
    SET p.subtotal = COALESCE(d.subtotal, p.total),
        p.envio = GREATEST(0, p.total - COALESCE(d.subtotal, p.total))
    WHERE p.subtotal = 0');
    echo "Desglose de pedidos actualizado.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "No se pudo actualizar la base: " . $exception->getMessage() . "\n");
    exit(1);
}
