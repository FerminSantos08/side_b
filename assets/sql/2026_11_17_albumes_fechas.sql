-- Fechas de lanzamiento de los 47 álbumes del respaldo side_b_2026-11-09.sql.
-- Actualiza solo registros existentes que coincidan por artista y título.
-- No inserta álbumes ni cambia portadas. Se puede ejecutar más de una vez.
-- Fechas de las ediciones originales, no de reediciones/deluxe.
-- Variaciones regionales: AM usa 2013-09-06 (primer lanzamiento internacional);
-- Gorillaz, Demon Days y Plastic Beach usan la edición británica;
-- HIStory y Xscape usan la edición estadounidense. Thriller usa la fecha
-- conmemorativa oficial (1982-11-30), aunque hay registros del día anterior.
-- Fuentes y criterios: 2026_11_17_albumes_fechas_fuentes.md.
SET NAMES utf8mb4;
DROP TEMPORARY TABLE IF EXISTS sideb_fechas_album;
CREATE TEMPORARY TABLE sideb_fechas_album (
    artista VARCHAR(150) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    PRIMARY KEY (artista, titulo)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO sideb_fechas_album (artista, titulo, fecha) VALUES
    ('Arctic Monkeys', 'Whatever People Say I Am, That''s What I''m Not', '2006-01-23'),
    ('Arctic Monkeys', 'Favourite Worst Nightmare', '2007-04-23'),
    ('Arctic Monkeys', 'AM', '2013-09-06'),
    ('Arctic Monkeys', 'Tranquility Base Hotel & Casino', '2018-05-11'),
    ('Arctic Monkeys', 'The Car', '2022-10-21'),
    ('Childish Gambino', 'Camp', '2011-11-15'),
    ('Childish Gambino', 'Because the Internet', '2013-12-10'),
    ('Childish Gambino', 'Awaken, My Love!', '2016-12-02'),
    ('Childish Gambino', 'Atavista', '2024-05-13'),
    ('Childish Gambino', 'Bando Stone & the New World', '2024-07-19'),
    ('Frank Ocean', 'nostalgia, ULTRA', '2011-02-16'),
    ('Frank Ocean', 'channel ORANGE', '2012-07-10'),
    ('Frank Ocean', 'Endless', '2016-08-19'),
    ('Frank Ocean', 'Blonde', '2016-08-20'),
    ('Gorillaz', 'Gorillaz', '2001-03-26'),
    ('Gorillaz', 'Demon Days', '2005-05-23'),
    ('Gorillaz', 'Plastic Beach', '2010-03-08'),
    ('Gorillaz', 'Humanz', '2017-04-28'),
    ('Gorillaz', 'Cracker Island', '2023-02-24'),
    ('Gorillaz', 'The Mountain', '2026-02-27'),
    ('Joji', 'BALLADS 1', '2018-10-26'),
    ('Joji', 'Nectar', '2020-09-25'),
    ('Joji', 'SMITHEREENS', '2022-11-04'),
    ('Joji', 'Piss in the Wind', '2026-02-06'),
    ('Kendrick Lamar', 'good kid, m.A.A.d city', '2012-10-22'),
    ('Kendrick Lamar', 'To Pimp a Butterfly', '2015-03-16'),
    ('Kendrick Lamar', 'DAMN.', '2017-04-14'),
    ('Kendrick Lamar', 'Mr. Morale & the Big Steppers', '2022-05-13'),
    ('Kendrick Lamar', 'GNX', '2024-11-22'),
    ('Michael Jackson', 'Off the Wall', '1979-08-10'),
    ('Michael Jackson', 'Thriller', '1982-11-30'),
    ('Michael Jackson', 'Bad', '1987-08-31'),
    ('Michael Jackson', 'Dangerous', '1991-11-26'),
    ('Michael Jackson', 'HIStory', '1995-06-20'),
    ('Michael Jackson', 'Invincible', '2001-10-30'),
    ('Michael Jackson', 'Xscape', '2014-05-13'),
    ('The Weeknd', 'Kiss Land', '2013-09-10'),
    ('The Weeknd', 'Beauty Behind the Madness', '2015-08-28'),
    ('The Weeknd', 'Starboy', '2016-11-25'),
    ('The Weeknd', 'After Hours', '2020-03-20'),
    ('The Weeknd', 'Dawn FM', '2022-01-07'),
    ('The Weeknd', 'Hurry Up Tomorrow', '2025-01-31'),
    ('Tyler, The Creator', 'Flower Boy', '2017-07-21'),
    ('Tyler, The Creator', 'IGOR', '2019-05-17'),
    ('Tyler, The Creator', 'CALL ME IF YOU GET LOST', '2021-06-25'),
    ('Tyler, The Creator', 'CHROMAKOPIA', '2024-10-28'),
    ('Tyler, The Creator', 'DON''T TAP THE GLASS', '2025-07-21');

-- Muestra títulos que no existen en la BD actual; lo esperado es cero filas.
SELECT f.artista, f.titulo AS album_no_encontrado
FROM sideb_fechas_album f
LEFT JOIN artista ar ON ar.nombre = f.artista COLLATE utf8mb4_unicode_ci
LEFT JOIN album al ON al.artista_id = ar.id_artista AND al.titulo = f.titulo COLLATE utf8mb4_unicode_ci
WHERE al.id_album IS NULL;

START TRANSACTION;
UPDATE album al
INNER JOIN artista ar ON ar.id_artista = al.artista_id
INNER JOIN sideb_fechas_album f ON f.artista COLLATE utf8mb4_unicode_ci = ar.nombre AND f.titulo COLLATE utf8mb4_unicode_ci = al.titulo
SET al.fecha_lanzamiento = f.fecha;
COMMIT;

-- Verificación: fecha guardada, fecha esperada y resultado por álbum.
SELECT ar.nombre AS artista, al.titulo, al.fecha_lanzamiento,
       f.fecha AS fecha_esperada,
       CASE WHEN al.fecha_lanzamiento = f.fecha THEN 'OK' ELSE 'REVISAR' END AS resultado
FROM album al
INNER JOIN artista ar ON ar.id_artista = al.artista_id
INNER JOIN sideb_fechas_album f ON f.artista COLLATE utf8mb4_unicode_ci = ar.nombre AND f.titulo COLLATE utf8mb4_unicode_ci = al.titulo
ORDER BY ar.nombre, al.fecha_lanzamiento, al.titulo;

-- Álbumes de la BD que todavía no tienen fecha (incluye nuevos no presentes en el respaldo).
SELECT ar.nombre AS artista, al.titulo
FROM album al
INNER JOIN artista ar ON ar.id_artista = al.artista_id
WHERE al.fecha_lanzamiento IS NULL
ORDER BY ar.nombre, al.titulo;

DROP TEMPORARY TABLE sideb_fechas_album;
