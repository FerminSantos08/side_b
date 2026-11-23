-- Portada local convertida con convert_image_to_webp().
UPDATE album AS al
INNER JOIN artista AS ar ON ar.id_artista = al.artista_id
SET al.portada = 'uploads/albumes/igor.webp'
WHERE al.titulo = 'IGOR' AND ar.nombre = 'Tyler, The Creator';
