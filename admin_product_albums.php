<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
require_executive_producer();
header('Content-Type: application/json; charset=utf-8');
$productId = filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT) ?: 0;
$albums = db()->query('SELECT al.id_album AS id,al.titulo,ar.nombre AS artista FROM album al INNER JOIN artista ar ON ar.id_artista=al.artista_id ORDER BY ar.nombre,al.titulo')->fetchAll();
$selected = null;
if ($productId) { $s=db()->prepare('SELECT album_id FROM producto WHERE id_producto=:id');$s->execute(['id'=>$productId]);$selected=$s->fetchColumn() ?: null; }
echo json_encode(['albums'=>$albums,'selected'=>$selected], JSON_UNESCAPED_UNICODE);
