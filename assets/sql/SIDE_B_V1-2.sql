-- SIDE B v1.2 | Instalacion limpia MySQL 8.0.16+
-- Web: PHP/PDO directo a MySQL | Movil: Flutter -> FastAPI -> MySQL
-- No elimina ni modifica tablas existentes; ejecutar sobre base nueva/vacia.
-- Sin datos ficticios. Comunidad y mensajeria privada fuera de alcance.
CREATE DATABASE IF NOT EXISTS `side_b` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `side_b`;

-- Roles: 1 Listener, 2 Artist, 3 Executive Producer.
-- Se conserva tip_usu por compatibilidad con el esquema original.
CREATE TABLE IF NOT EXISTS `rol` (
    `id_rol` TINYINT UNSIGNED PRIMARY KEY,
    `nombre` VARCHAR(60) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `usuario` (
    `id_usr` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_usr` VARCHAR(100) NOT NULL,
    `mail` VARCHAR(150) NOT NULL UNIQUE,
    `pass` VARCHAR(255) NOT NULL,
    `dir_usr` VARCHAR(255) NULL,
    `tip_usu` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `activo` BOOLEAN NOT NULL DEFAULT TRUE,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`tip_usu`) REFERENCES `rol` (`id_rol`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `perfil_usuario` (
    `usr_id` INT PRIMARY KEY,
    `alias` VARCHAR(50) NULL,
    `biografia` VARCHAR(300) NULL,
    `avatar_url` VARCHAR(255) NULL,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_perfil_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `blog` (
    `id_blog` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `desc` VARCHAR(500) NULL,
    `img` VARCHAR(255) NULL,
    `tags` VARCHAR(255) NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `blog_detalle` (
    `blog_id` INT PRIMARY KEY,
    `contenido` LONGTEXT NOT NULL,
    `autor_id` INT NOT NULL,
    `estado` ENUM('borrador','revision','publicado','archivado') NOT NULL DEFAULT 'borrador',
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `fecha_publicacion` DATETIME NULL,
    KEY `idx_blog_detalle_autor` (`autor_id`),
    KEY `idx_blog_detalle_estado_fecha` (`estado`,`fecha_publicacion`),
    CONSTRAINT `fk_blog_detalle_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE CASCADE,
    CONSTRAINT `fk_blog_detalle_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Una sola tabla de comentarios para web y movil.
CREATE TABLE IF NOT EXISTS `comentarios` (
    `id_com` INT AUTO_INCREMENT PRIMARY KEY,
    `comentario` VARCHAR(500) NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `estatus` ENUM('visible','oculto','eliminado') NOT NULL DEFAULT 'visible',
    `usr_id` INT NOT NULL,
    `blog_id` INT NOT NULL,
    KEY `idx_comentarios_blog_estado_fecha` (`blog_id`,`estatus`,`fecha`),
    KEY `idx_comentarios_usuario` (`usr_id`),
    CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT,
    CONSTRAINT `fk_comentarios_blog` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id_blog`) ON DELETE RESTRICT,
    CONSTRAINT `chk_comentarios_no_vacio` CHECK (CHAR_LENGTH(TRIM(`comentario`)) > 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `artista` (
    `id_artista` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NULL UNIQUE,
    `nombre` VARCHAR(150) NOT NULL,
    `biografia` TEXT NULL,
    `imagen` VARCHAR(255) NULL,
    `genero` VARCHAR(100) NULL,
    `activo` BOOLEAN NOT NULL DEFAULT TRUE,
    KEY `idx_artista_nombre` (`nombre`),
    CONSTRAINT `fk_artista_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id_usr`) ON DELETE SET NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `album` (
    `id_album` INT AUTO_INCREMENT PRIMARY KEY,
    `artista_id` INT NOT NULL,
    `titulo` VARCHAR(150) NOT NULL,
    `fecha_lanzamiento` DATE NULL,
    `portada` VARCHAR(255) NULL,
    `tipo` ENUM('album','ep','sencillo') NOT NULL DEFAULT 'album',
    KEY `idx_album_artista` (`artista_id`),
    KEY `idx_album_lanzamiento` (`fecha_lanzamiento`),
    CONSTRAINT `fk_album_artista` FOREIGN KEY (`artista_id`) REFERENCES `artista` (`id_artista`) ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `categoria_producto` (
    `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `producto` (
    `id_producto` INT AUTO_INCREMENT PRIMARY KEY,
    `categoria_id` INT NOT NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `marca` VARCHAR(100) NULL,
    `descripcion` TEXT NULL,
    `precio` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `imagen` VARCHAR(255) NULL,
    `imagen_2` VARCHAR(255) NULL,
    `imagen_3` VARCHAR(255) NULL,
    `imagen_4` VARCHAR(255) NULL,
    `activo` BOOLEAN NOT NULL DEFAULT TRUE,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_producto_categoria` (`categoria_id`),
    KEY `idx_producto_nombre` (`nombre`),
    CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_producto` (`id_categoria`) ON DELETE RESTRICT,
    CONSTRAINT `chk_producto_precio` CHECK (`precio` >= 0),
    CONSTRAINT `chk_producto_stock` CHECK (`stock` >= 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `carrito_item` (
    `id_item` INT AUTO_INCREMENT PRIMARY KEY,
    `usr_id` INT NOT NULL,
    `producto_id` INT NOT NULL,
    `cantidad` INT NOT NULL DEFAULT 1,
    `fecha_agregado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_carrito_usuario_producto` (`usr_id`,`producto_id`),
    KEY `idx_carrito_producto` (`producto_id`),
    CONSTRAINT `fk_carrito_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE CASCADE,
    CONSTRAINT `fk_carrito_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT,
    CONSTRAINT `chk_carrito_cantidad` CHECK (`cantidad` > 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `pedido` (
    `id_pedido` INT AUTO_INCREMENT PRIMARY KEY,
    `usr_id` INT NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `estado` ENUM('pendiente','pagado','preparando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `direccion_envio` VARCHAR(255) NULL,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pedido_usuario_fecha` (`usr_id`,`fecha`),
    CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usr_id`) REFERENCES `usuario` (`id_usr`) ON DELETE RESTRICT,
    CONSTRAINT `chk_pedido_total` CHECK (`total` >= 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `pedido_detalle` (
    `id_detalle` INT AUTO_INCREMENT PRIMARY KEY,
    `pedido_id` INT NOT NULL,
    `producto_id` INT NOT NULL,
    `nombre_producto` VARCHAR(150) NOT NULL,
    `cantidad` INT NOT NULL,
    `precio_unitario` DECIMAL(10,2) NOT NULL,
    UNIQUE KEY `uq_pedido_producto` (`pedido_id`,`producto_id`),
    KEY `idx_detalle_producto` (`producto_id`),
    CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`id_pedido`) ON DELETE RESTRICT,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT,
    CONSTRAINT `chk_detalle_cantidad` CHECK (`cantidad` > 0),
    CONSTRAINT `chk_detalle_precio` CHECK (`precio_unitario` >= 0)
) ENGINE = InnoDB;

-- Datos de configuracion necesarios para poder crear usuarios.
INSERT INTO `rol` (`id_rol`, `nombre`, `descripcion`) VALUES
    (1,'Listener','Usuario convencional'),
    (2,'Artist','Usuario artista'),
    (3,'Executive Producer','Superadministrador')
ON DUPLICATE KEY UPDATE `id_rol` = `id_rol`;

-- IMPORTANTE:
-- 1. No hay usuarios ni productos ficticios. Crear el primer administrador mediante
--    un script de instalacion seguro que genere hash de contrasena; nunca texto plano.
-- 2. PHP/PDO y FastAPI deben validar autenticacion, autoria, permisos y estados.
-- 3. Para editar/eliminar comentarios propios, comparar usr_id con usuario autenticado.
-- 4. Borrado de comentarios = estatus 'eliminado'; moderacion = 'oculto'.
-- 5. Al crear un pedido: transaccion atomica, comprobar/descontar stock,
--    insertar detalle y calcular total en servidor (no confiar en el cliente).
-- 6. blog + blog_detalle deben insertarse juntos en una transaccion.
-- 7. La baja de productos y usuarios debe ser logica (activo=0).
-- 8. Este script no implementa pasarela de pago ni confirma pagos reales.
-- 9. IF NOT EXISTS no migra tablas anteriores; usar sobre BD nueva/vacia.
-- 10. Los roles representan niveles de acceso; reglas finas se implementan en backend.

-- Verificacion:
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'side_b' ORDER BY TABLE_NAME;
SELECT COUNT(*) AS total_tablas FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'side_b';
