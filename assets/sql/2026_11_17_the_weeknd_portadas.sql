-- Carátulas descargadas y convertidas con convert_image_to_webp().
SET NAMES utf8mb4;
START TRANSACTION;

UPDATE album AS al
INNER JOIN artista AS ar ON ar.id_artista = al.artista_id
SET al.portada = CASE al.titulo
    WHEN 'Kiss Land' THEN 'uploads/albumes/the-weeknd-kiss-land.webp'
    WHEN 'After Hours' THEN 'uploads/albumes/the-weeknd-after-hours.webp'
    WHEN 'Starboy' THEN 'uploads/albumes/the-weeknd-starboy.webp'
END
WHERE ar.nombre = 'The Weeknd'
  AND al.titulo IN ('Kiss Land', 'After Hours', 'Starboy');

COMMIT;

SELECT al.id_album, al.titulo, al.portada
FROM album AS al
INNER JOIN artista AS ar ON ar.id_artista = al.artista_id
WHERE ar.nombre = 'The Weeknd'
  AND al.titulo IN ('Kiss Land', 'After Hours', 'Starboy');
