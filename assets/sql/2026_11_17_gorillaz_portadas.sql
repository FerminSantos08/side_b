-- Carátulas descargadas y convertidas con convert_image_to_webp().
START TRANSACTION;

UPDATE album AS al
INNER JOIN artista AS ar ON ar.id_artista = al.artista_id
SET al.portada = CASE al.titulo
    WHEN 'Gorillaz' THEN 'uploads/albumes/gorillaz.webp'
    WHEN 'Demon Days' THEN 'uploads/albumes/demon-days.webp'
    WHEN 'Plastic Beach' THEN 'uploads/albumes/plastic-beach.webp'
    WHEN 'Cracker Island' THEN 'uploads/albumes/cracker-island.webp'
END
WHERE ar.nombre = 'Gorillaz'
  AND al.titulo IN ('Gorillaz', 'Demon Days', 'Plastic Beach', 'Cracker Island');

COMMIT;

SELECT al.id_album, al.titulo, al.portada
FROM album AS al
INNER JOIN artista AS ar ON ar.id_artista = al.artista_id
WHERE ar.nombre = 'Gorillaz'
  AND al.titulo IN ('Gorillaz', 'Demon Days', 'Plastic Beach', 'Cracker Island');
