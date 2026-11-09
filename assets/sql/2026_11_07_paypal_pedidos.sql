-- Aplicar una sola vez en instalaciones existentes.
ALTER TABLE `pedido`
    ADD COLUMN `paypal_order_id` VARCHAR(64) NULL UNIQUE AFTER `direccion_envio`,
    ADD COLUMN `paypal_capture_id` VARCHAR(64) NULL UNIQUE AFTER `paypal_order_id`,
    ADD COLUMN `fecha_pago` DATETIME NULL AFTER `paypal_capture_id`;

-- Desglose congelado al crear el pedido. Aplicar una sola vez en instalaciones existentes.
ALTER TABLE `pedido`
    ADD COLUMN `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total`,
    ADD COLUMN `envio` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
    ADD COLUMN `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `envio`,
    ADD COLUMN `impuestos` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `descuento`;
