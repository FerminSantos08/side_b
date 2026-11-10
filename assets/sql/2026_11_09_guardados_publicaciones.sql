-- Ejecutar una vez en instalaciones existentes.
CREATE TABLE IF NOT EXISTS `guardado_publicacion` (
    `id_guardado_publicacion` INT AUTO_INCREMENT PRIMARY KEY,
    `usr_id` INT NOT NULL,
    `blog_id` INT NOT NULL,
    `fecha_guardado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_guardado_publicacion_usuario_blog` (`usr_id`,`blog_id`),
    KEY `idx_guardado_publicacion_blog` (`blog_id`),
    CONSTRAINT `fk_guardado_publicacion_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE,
    CONSTRAINT `fk_guardado_publicacion_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE
) ENGINE = InnoDB;
