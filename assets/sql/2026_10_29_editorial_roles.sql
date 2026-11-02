-- Migración aditiva para instalaciones existentes de SIDE B. No elimina datos.
USE `side_b`;

INSERT INTO `rol` (`id_rol`, `nombre`, `descripcion`)
VALUES (4, 'Editor', 'Integrante del equipo editorial de SIDE B')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`);

CREATE TABLE IF NOT EXISTS `blog_artista` (
    `blog_id` INT NOT NULL,
    `artista_id` INT NOT NULL,
    PRIMARY KEY (`blog_id`, `artista_id`),
    CONSTRAINT `fk_blog_artista_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE,
    CONSTRAINT `fk_blog_artista_artista` FOREIGN KEY (`artista_id`) REFERENCES `artista` (`id_artista`) ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `blog_album` (
    `blog_id` INT NOT NULL,
    `album_id` INT NOT NULL,
    PRIMARY KEY (`blog_id`, `album_id`),
    CONSTRAINT `fk_blog_album_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE,
    CONSTRAINT `fk_blog_album_album` FOREIGN KEY (`album_id`) REFERENCES `album` (`id_album`) ON DELETE RESTRICT
) ENGINE = InnoDB;
