-- URLs públicas legibles para productos; el identificador interno permanece en MySQL.
USE `side_b`;

ALTER TABLE `producto`
    ADD COLUMN `slug` VARCHAR(180) NULL AFTER `nombre`,
    ADD UNIQUE KEY `uq_producto_slug` (`slug`);
