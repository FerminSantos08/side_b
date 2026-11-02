-- Relación opcional para productos físicos vinculados a un lanzamiento musical.
USE `side_b`;

ALTER TABLE `producto`
    ADD COLUMN `album_id` INT NULL AFTER `categoria_id`,
    ADD KEY `idx_producto_album` (`album_id`),
    ADD CONSTRAINT `fk_producto_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`id_album`) ON DELETE SET NULL;

INSERT INTO `categoria_producto` (`nombre`, `descripcion`)
VALUES ('Álbumes musicales', 'Productos físicos vinculados a álbumes musicales')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
