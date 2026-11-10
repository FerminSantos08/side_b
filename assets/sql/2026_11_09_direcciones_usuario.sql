CREATE TABLE IF NOT EXISTS `direccion_usuario` (
    `id_direccion` INT AUTO_INCREMENT PRIMARY KEY,
    `usr_id` INT NOT NULL,
    `etiqueta` VARCHAR(60) NOT NULL DEFAULT 'Casa',
    `direccion` VARCHAR(255) NOT NULL,
    `es_principal` BOOLEAN NOT NULL DEFAULT FALSE,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_direccion_usuario` (`usr_id`,`es_principal`),
    CONSTRAINT `fk_direccion_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE = InnoDB;

INSERT INTO direccion_usuario (usr_id, etiqueta, direccion, es_principal)
SELECT id_usr, 'Principal', dir_usr, TRUE FROM usuario
WHERE dir_usr IS NOT NULL AND TRIM(dir_usr) <> ''
  AND NOT EXISTS (SELECT 1 FROM direccion_usuario d WHERE d.usr_id = usuario.id_usr);
