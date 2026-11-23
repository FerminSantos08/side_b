-- Aplicar una vez después del respaldo side_b_2026-11-09.sql.
-- Para aplicar de forma repetible, usar: php scripts/migrate.php
ALTER TABLE pedido
    ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN envio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN impuestos DECIMAL(12,2) NOT NULL DEFAULT 0.00;

UPDATE pedido p
LEFT JOIN (
    SELECT pedido_id, SUM(cantidad * precio_unitario) AS subtotal
    FROM pedido_detalle GROUP BY pedido_id
) d ON d.pedido_id = p.id_pedido
SET p.subtotal = COALESCE(d.subtotal, p.total),
    p.envio = GREATEST(0, p.total - COALESCE(d.subtotal, p.total))
WHERE p.subtotal = 0;
