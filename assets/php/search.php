<?php
declare(strict_types=1);

function sideb_search_query(mixed $input): string
{
    if (!is_string($input) || !preg_match('//u', $input)) return '';
    $input = trim($input);
    return function_exists('mb_substr') ? mb_substr($input, 0, 100, 'UTF-8') : substr($input, 0, 100);
}

function sideb_search_categories(): array
{
    return [
        'artistas' => 'Artistas',
        'albumes' => 'Álbumes',
        'publicaciones' => 'Publicaciones',
        'productos' => 'Productos',
        'autores' => 'Productores y autores',
    ];
}

function sideb_search_url(string $query, string $category = '', int $page = 1): string
{
    $params = ['q' => $query];
    if ($category !== '') $params['categoria'] = $category;
    if ($page > 1) $params['pagina'] = $page;
    return 'buscar.php?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function sideb_search_excerpt(?string $text): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if (function_exists('mb_strimwidth')) return mb_strimwidth($text, 0, 145, '…', 'UTF-8');
    return $text;
}

/** Only site-relative image paths from the catalog are exposed in results. */
function sideb_search_image(?string $path): ?string
{
    $path = trim((string) $path);
    if (!preg_match('~\A(?:uploads|assets)/[a-z0-9._% /-]+\z~iD', $path)
        || str_contains($path, '..') || str_contains($path, '%')) return null;
    return $path;
}

/** @return list<array{label:string,meta:string,description:string,image:?string,url:string}> */
function sideb_search_results(PDO $database, string $query, string $category, int $limit, int $offset = 0): array
{
    $published = "d.estado='publicado' AND d.fecha_publicacion IS NOT NULL AND d.fecha_publicacion<=NOW()";
    // Each marker receives the same literal substring, with wildcard characters escaped.
    $sql = match ($category) {
        'artistas' => "SELECT nombre AS label,genero,biografia AS description,imagen AS image FROM artista
            WHERE activo=TRUE AND (nombre LIKE ? OR genero LIKE ?) ORDER BY nombre,id_artista",
        'albumes' => "SELECT al.titulo AS label,al.fecha_lanzamiento,al.portada AS image,al.tipo,a.nombre AS artista
            FROM album al INNER JOIN artista a ON a.id_artista=al.artista_id
            WHERE a.activo=TRUE AND (al.titulo LIKE ? OR a.nombre LIKE ?)
            ORDER BY (al.titulo LIKE ?) DESC,al.fecha_lanzamiento DESC,al.titulo,al.id_album",
        'publicaciones' => "SELECT b.titulo AS label,b.slug,b.`desc` AS description,b.img,d.fecha_publicacion
            FROM blog b INNER JOIN blog_detalle d ON d.blog_id=b.id_blog
            WHERE $published AND (b.titulo LIKE ? OR b.`desc` LIKE ? OR d.contenido LIKE ?
                OR EXISTS (SELECT 1 FROM blog_artista ba JOIN artista a ON a.id_artista=ba.artista_id
                    WHERE ba.blog_id=b.id_blog AND a.activo=TRUE AND a.nombre LIKE ?)
                OR EXISTS (SELECT 1 FROM blog_album ba JOIN album al ON al.id_album=ba.album_id
                    WHERE ba.blog_id=b.id_blog AND al.titulo LIKE ?)
                OR EXISTS (SELECT 1 FROM usuario u LEFT JOIN perfil_usuario pu ON pu.usr_id=u.id_usr
                    WHERE u.id_usr=d.autor_id AND (u.nom_usr LIKE ? OR pu.alias LIKE ?)))
            ORDER BY (b.titulo LIKE ?) DESC,d.fecha_publicacion DESC,b.id_blog DESC",
        'productos' => "SELECT p.nombre AS label,p.slug,p.imagen AS image,p.precio,p.descripcion AS description,c.nombre AS categoria
            FROM producto p INNER JOIN categoria_producto c ON c.id_categoria=p.categoria_id
            WHERE p.activo=TRUE AND p.slug IS NOT NULL AND p.slug<>'' AND
                (p.nombre LIKE ? OR p.marca LIKE ? OR p.descripcion LIKE ? OR c.nombre LIKE ?)
            ORDER BY (p.nombre LIKE ?) DESC,p.fecha_actualizacion DESC,p.id_producto DESC",
        'autores' => "SELECT DISTINCT u.nom_usr,COALESCE(pu.alias,u.nom_usr) AS label,r.nombre AS rol
            FROM usuario u INNER JOIN rol r ON r.id_rol=u.tip_usu
            LEFT JOIN perfil_usuario pu ON pu.usr_id=u.id_usr
            INNER JOIN blog_detalle d ON d.autor_id=u.id_usr
            WHERE u.activo=TRUE AND $published AND (u.nom_usr LIKE ? OR pu.alias LIKE ? OR r.nombre LIKE ?)
            ORDER BY label,nom_usr",
    };
    $like = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    $statement = $database->prepare(str_replace('LIKE ?', "LIKE ? ESCAPE '!'", $sql) . ' LIMIT ? OFFSET ?');
    $position = 1;
    for ($markers = substr_count($sql, '?'); $position <= $markers; $position++) {
        $statement->bindValue($position, $like);
    }
    $statement->bindValue($position, $limit, PDO::PARAM_INT);
    $statement->bindValue($position + 1, $offset, PDO::PARAM_INT);
    $statement->execute();

    $items = [];
    foreach ($statement->fetchAll() as $row) {
        $name = $row['label'];
        $description = sideb_search_excerpt($row['description'] ?? null);
        [$meta, $url, $description] = match ($category) {
            'artistas' => ['Artista', 'artista.php?artista=' . rawurlencode(artist_slug($name)),
                $row['genero'] ?: $description],
            'albumes' => [
                ['album' => 'Álbum', 'ep' => 'EP', 'sencillo' => 'Sencillo'][$row['tipo']] ?? 'Álbum',
                album_url($row['artista'], $name),
                $row['artista'] . ($row['fecha_lanzamiento'] ? ' · ' . substr($row['fecha_lanzamiento'], 0, 4) : ''),
            ],
            'publicaciones' => ['Publicación', 'publicacion.php?slug=' . rawurlencode($row['slug']),
                $description ?: date('d/m/Y', strtotime($row['fecha_publicacion']))],
            'productos' => [$row['categoria'], 'producto.php?slug=' . rawurlencode($row['slug']),
                cart_money((float) $row['precio']) . ' MXN' . ($description ? ' · ' . $description : '')],
            'autores' => [$row['rol'], sideb_search_url($name, 'publicaciones'), 'Ver sus publicaciones en SIDE B'],
        };
        $items[] = [
            'label' => $name,
            'meta' => $meta,
            'description' => $description,
            'image' => sideb_search_image($category === 'publicaciones' ? publication_image_url($row['img']) : ($row['image'] ?? null)),
            'url' => $url,
        ];
    }
    return $items;
}
