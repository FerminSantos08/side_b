<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__ . '/assets/php/spotify.php';

function album_short_date(string $date): string
{
    [$year, $month, $day] = explode('-', substr($date, 0, 10));
    $months = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];
    return $day . ' ' . $months[(int) $month - 1] . ' ' . $year;
}

$legacyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$artistName = $_GET['artista'] ?? null;
$albumTitle = $_GET['album'] ?? null;
$album = null;
$albumQuery = 'SELECT al.*, ar.nombre AS artista, ar.genero, ar.imagen AS artista_imagen FROM album al INNER JOIN artista ar ON ar.id_artista=al.artista_id WHERE ar.activo=TRUE';

if (is_string($artistName) && $artistName !== '' && is_string($albumTitle) && $albumTitle !== '') {
    $statement = db()->prepare($albumQuery . ' AND ar.nombre=:artist AND al.titulo=:title
        AND BINARY ar.nombre=BINARY :exact_artist AND BINARY al.titulo=BINARY :exact_title
        ORDER BY al.fecha_lanzamiento DESC, al.id_album LIMIT 1');
    $statement->execute(['artist' => $artistName, 'title' => $albumTitle, 'exact_artist' => $artistName, 'exact_title' => $albumTitle]);
    $album = $statement->fetch();
} elseif ($legacyId) {
    $statement = db()->prepare($albumQuery . ' AND al.id_album=:id');
    $statement->execute(['id' => $legacyId]);
    $album = $statement->fetch();
}

if ($album) {
    if (array_key_exists('id', $_GET)) {
        $url = album_url($album['artista'], $album['titulo']);
        if (($_GET['escuchar'] ?? '') === 'spotify') $url .= '&escuchar=spotify';
        header('Location: ' . $url, true, 301);
        exit;
    }
    $id = (int) $album['id_album'];
    $isSpotifyRedirect = ($_GET['escuchar'] ?? '') === 'spotify';
    $spotify = spotify_album($album['titulo'], $album['artista'], $album['fecha_lanzamiento'], !$isSpotifyRedirect);
    if ($isSpotifyRedirect) {
        header('Cache-Control: no-store');
        redirect($spotify['url']);
    }
    $tracks = $spotify['tracks'];
    $multipleDiscs = $tracks && $tracks[array_key_last($tracks)]['disc'] > 1;

    $statement = db()->prepare('SELECT id_producto,nombre,precio,imagen FROM producto WHERE album_id=:id AND activo=TRUE ORDER BY fecha_actualizacion DESC');
    $statement->execute(['id' => $id]);
    $products = $statement->fetchAll();
    $relatedProductCount = count($products);
    if ($relatedProductCount > 0 && $relatedProductCount < 3) {
        $statement = db()->prepare('SELECT p.id_producto,p.nombre,p.precio,p.imagen
            FROM producto p LEFT JOIN album al ON al.id_album=p.album_id
            WHERE p.activo=TRUE AND p.stock>0 AND NOT (p.album_id <=> :album)
            ORDER BY (al.artista_id <=> :artist) DESC, p.fecha_actualizacion DESC, p.id_producto
            LIMIT :remaining');
        $statement->bindValue('album', $id, PDO::PARAM_INT);
        $statement->bindValue('artist', (int) $album['artista_id'], PDO::PARAM_INT);
        $statement->bindValue('remaining', 3 - $relatedProductCount, PDO::PARAM_INT);
        $statement->execute();
        $products = array_merge($products, $statement->fetchAll());
    }

    $statement = db()->prepare("SELECT b.titulo,b.slug,b.`desc`,b.img,bd.fecha_publicacion FROM blog b INNER JOIN blog_album ba ON ba.blog_id=b.id_blog INNER JOIN blog_detalle bd ON bd.blog_id=b.id_blog WHERE ba.album_id=:id AND bd.estado='publicado' AND bd.fecha_publicacion<=NOW() ORDER BY bd.fecha_publicacion DESC");
    $statement->execute(['id' => $id]);
    $posts = $statement->fetchAll();

    $statement = db()->prepare('SELECT id_album,titulo,portada,fecha_lanzamiento FROM album
        WHERE artista_id=:artist AND id_album<>:album ORDER BY fecha_lanzamiento DESC,titulo LIMIT 2');
    $statement->execute(['artist' => $album['artista_id'], 'album' => $id]);
    $discography = $statement->fetchAll();
    if ($discography) {
        $discography[] = $album;
        usort($discography, static fn (array $a, array $b): int =>
            strcmp($b['fecha_lanzamiento'] ?? '', $a['fecha_lanzamiento'] ?? '') ?: strcmp($a['titulo'], $b['titulo']));
    }

    $artistUrl = 'artista.php?artista=' . rawurlencode(artist_slug($album['artista']));
    $albumType = ['album' => 'Álbum', 'ep' => 'EP', 'sencillo' => 'Sencillo'][$album['tipo']] ?? $album['tipo'];
    $release = $album['fecha_lanzamiento'] ? album_short_date($album['fecha_lanzamiento']) : null;
    $duration = intdiv(array_sum(array_column($tracks, 'seconds')), 60);
    $trackCount = count($tracks);
    $editorial = null;
    foreach ($posts as $post) {
        if (trim((string) $post['desc']) !== '') {
            $editorial = $post;
            break;
        }
    }
} else {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($album['titulo'] ?? 'Álbum no disponible') ?> — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/home.css?v=20261118">
    <link rel="stylesheet" href="assets/css/posts.css?v=20261118">
    <link rel="stylesheet" href="assets/css/album.css?v=20261118-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a><?= sideb_public_header('artistas') ?>
<main id="contenido" class="album-page">
    <?php if (!$album): ?>
        <div class="container album-empty"><h1>Álbum no disponible.</h1></div>
    <?php else: ?>
        <header class="album-masthead">
            <?php if ($album['artista_imagen']): ?>
                <div class="album-portrait" aria-hidden="true"><img src="<?= e($album['artista_imagen']) ?>" alt=""></div>
            <?php endif; ?>
            <div class="container album-masthead-inner">
                <a class="text-link album-back" href="<?= e($artistUrl) ?>">← Volver al artista</a>
                <div class="album-hero<?= $album['portada'] ? ' album-hero--cover' : '' ?>">
                    <?php if ($album['portada']): ?>
                        <img class="album-cover" src="<?= e($album['portada']) ?>" alt="Portada de <?= e($album['titulo']) ?>">
                    <?php endif; ?>
                    <div class="album-copy">
                        <p class="eyebrow"><?= e($albumType) ?><?= $release ? ' · ' . e(substr($album['fecha_lanzamiento'], 0, 4)) : '' ?></p>
                        <h1><?= e($album['titulo']) ?></h1>
                        <a class="album-artist" href="<?= e($artistUrl) ?>"><?= e($album['artista']) ?></a>
                        <?php if ($album['genero']): ?>
                            <p class="album-genre"><?= e($album['genero']) ?></p>
                        <?php endif; ?>
                        <?php if ($tracks || $release): ?>
                            <ul class="album-meta" role="list">
                                <?php if ($tracks): ?>
                                    <li><?= $trackCount ?> <?= $trackCount === 1 ? 'canción' : 'canciones' ?></li>
                                    <?php if ($duration > 0): ?><li><?= $duration ?> min</li><?php endif; ?>
                                <?php endif; ?>
                                <?php if ($release): ?>
                                    <li><time datetime="<?= e($album['fecha_lanzamiento']) ?>"><?= e($release) ?></time></li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="album-actions">
                            <a class="button button-primary spotify-button" href="<?= e($spotify['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Escuchar <?= e($album['titulo']) ?> en Spotify">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M7.5 10.2c3.5-.9 6.3-.5 8.8.9M8.2 13c2.7-.6 5-.3 6.8.7M9 15.5c1.9-.4 3.5-.1 4.8.5"/></svg>
                                Escuchar en Spotify
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="container album-layout<?= $editorial || $tracks ? ' album-layout--content' : '' ?>">
            <div class="album-main">
                <?php if ($editorial): ?>
                    <section class="album-editorial" aria-labelledby="sobre-album">
                        <h2 id="sobre-album">Sobre el álbum</h2>
                        <p><?= e($editorial['desc']) ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($tracks): ?>
                    <section class="album-tracks" id="canciones" aria-labelledby="lista-canciones">
                        <h2 id="lista-canciones">Lista de canciones</h2>
                        <table class="album-tracklist">
                            <thead><tr><th scope="col">#</th><th scope="col">Título</th><th scope="col">Duración</th><th scope="col" aria-label="Escuchar en Spotify"></th></tr></thead>
                            <tbody>
                                <?php foreach ($tracks as $track): ?>
                                    <tr>
                                        <td class="album-track-number"><?= $multipleDiscs ? (int) $track['disc'] . '–' : '' ?><?= sprintf('%02d', $track['number']) ?></td>
                                        <th scope="row">
                                            <?php if ($track['url']): ?>
                                                <a href="<?= e($track['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($track['name']) ?></a>
                                            <?php else: ?>
                                                <?= e($track['name']) ?>
                                            <?php endif; ?>
                                        </th>
                                        <td><time datetime="PT<?= (int) $track['seconds'] ?>S"><?= sprintf('%d:%02d', intdiv($track['seconds'], 60), $track['seconds'] % 60) ?></time></td>
                                        <td>
                                            <?php if ($track['url']): ?>
                                                <a class="album-track-play" href="<?= e($track['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Escuchar <?= e($track['name']) ?> en Spotify"><span aria-hidden="true">▶</span></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                <?php endif; ?>

            </div>

            <aside class="album-sidebar" aria-label="Información complementaria">
                <section class="album-panel" aria-labelledby="datos-album">
                    <h2 id="datos-album">Datos clave</h2>
                    <dl class="album-facts">
                        <div><dt>Formato</dt><dd><?= e($albumType) ?></dd></div>
                        <?php if ($album['genero']): ?><div><dt>Género del artista</dt><dd><?= e($album['genero']) ?></dd></div><?php endif; ?>
                        <?php if ($release): ?><div><dt>Lanzamiento</dt><dd><time datetime="<?= e($album['fecha_lanzamiento']) ?>"><?= e($release) ?></time></dd></div><?php endif; ?>
                        <?php if ($tracks): ?>
                            <?php if ($duration > 0): ?><div><dt>Duración</dt><dd><?= $duration ?> min</dd></div><?php endif; ?>
                            <div><dt>Canciones</dt><dd><?= $trackCount ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </section>

                <?php if ($discography): ?>
                    <section class="album-panel" aria-labelledby="discografia">
                        <div class="album-panel-heading">
                            <h2 id="discografia">En la discografía</h2>
                            <a class="text-link" href="<?= e($artistUrl) ?>">Ver artista <span aria-hidden="true">↗</span></a>
                        </div>
                        <ul class="album-discography" role="list">
                            <?php foreach ($discography as $entry): ?>
                                <li>
                                    <a href="<?= e(album_url($album['artista'], $entry['titulo'])) ?>"<?= (int) $entry['id_album'] === $id ? ' aria-current="page"' : '' ?>>
                                        <span class="album-disc-cover">
                                            <?php if ($entry['portada']): ?><img src="<?= e($entry['portada']) ?>" alt="" loading="lazy"><?php endif; ?>
                                        </span>
                                        <strong><?= e($entry['titulo']) ?></strong>
                                        <?php if ($entry['fecha_lanzamiento']): ?><small><?= e(substr($entry['fecha_lanzamiento'], 0, 4)) ?></small><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if ($posts): ?>
                    <section class="album-panel" aria-labelledby="articulos-album">
                        <h2 id="articulos-album">Artículos relacionados</h2>
                        <ul class="album-stories" role="list">
                            <?php foreach ($posts as $post): ?>
                                <?php $image = publication_image_url($post['img']); ?>
                                <li>
                                    <a class="album-story<?= $image ? ' album-story--image' : '' ?>" href="publicacion.php?slug=<?= rawurlencode($post['slug']) ?>">
                                        <?php if ($image): ?><img src="<?= e($image) ?>" alt="" loading="lazy"><?php endif; ?>
                                        <span>
                                            <strong><?= e($post['titulo']) ?></strong>
                                            <time datetime="<?= e(substr($post['fecha_publicacion'], 0, 10)) ?>"><?= e(album_short_date($post['fecha_publicacion'])) ?></time>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
                <?php if ($products): ?>
                    <section class="album-panel" aria-labelledby="productos-album">
                        <div class="album-panel-heading">
                            <h2 id="productos-album">En la tienda</h2>
                            <a class="text-link" href="tienda.php">Explorar tienda <span aria-hidden="true">↗</span></a>
                        </div>
                        <ul class="album-stories album-products" role="list">
                            <?php foreach ($products as $index => $product): ?>
                                <li>
                                    <a class="album-story<?= $product['imagen'] ? ' album-story--image' : '' ?>" href="producto.php?id=<?= (int) $product['id_producto'] ?>">
                                        <?php if ($product['imagen']): ?><img src="<?= e($product['imagen']) ?>" alt="" loading="lazy"><?php endif; ?>
                                        <span>
                                            <?php if ($index < $relatedProductCount): ?><small>Relacionado con el álbum</small><?php endif; ?>
                                            <strong><?= e($product['nombre']) ?></strong>
                                            <span class="album-product-price"><?= e(cart_money((float) $product['precio'])) ?> MXN</span>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</main>
<?= sideb_footer() ?>
</body>
</html>
