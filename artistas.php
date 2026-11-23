<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$artists = [];
$error = null;
try {
    $artists = db()->query('SELECT nombre,genero,imagen FROM artista WHERE activo=TRUE ORDER BY nombre,id_artista')->fetchAll();
} catch (Throwable) {
    $error = 'No fue posible cargar los artistas.';
}

$featuredCandidates = array_values(array_filter($artists, static fn (array $artist): bool => !empty($artist['imagen'])));
$featured = $featuredCandidates ? $featuredCandidates[(int) date('z') % count($featuredCandidates)] : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Artistas — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/home.css?v=20261119">
    <link rel="stylesheet" href="assets/css/artists.css?v=20261119">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('artistas') ?>
<main id="contenido" class="artist-directory-page">
    <section class="home-lead container" aria-labelledby="artists-title">
        <div class="home-lead-copy">
            <p class="eyebrow">Nuevas coordenadas</p>
            <h1 id="artists-title">Descubre<br><em>algo distinto.</em></h1>
            <p class="hero-description">Artistas que están haciendo ruido en los márgenes. Sin algoritmos, con criterio.</p>
            <?php if ($artists): ?>
                <div class="button-group"><a class="button button-primary" href="#directorio">Explorar artistas <span aria-hidden="true">↓</span></a></div>
            <?php endif; ?>
        </div>
        <?php if ($featured): ?>
            <a class="home-feature artist-directory-feature" href="artista.php?artista=<?= rawurlencode(artist_slug($featured['nombre'])) ?>">
                <img class="home-feature-image" src="<?= e($featured['imagen']) ?>" alt="" fetchpriority="high">
                <div class="home-feature-caption">
                    <div>
                        <p class="eyebrow">Artista destacado</p>
                        <h2><?= e($featured['nombre']) ?></h2>
                        <?php if ($featured['genero']): ?><p class="home-feature-meta"><?= e($featured['genero']) ?></p><?php endif; ?>
                    </div>
                    <span class="home-feature-arrow" aria-hidden="true">↗</span>
                </div>
            </a>
        <?php endif; ?>
    </section>

    <section class="container artist-directory" id="directorio" aria-labelledby="directory-title">
        <div class="section-heading"><h2 id="directory-title">Directorio de artistas</h2></div>
        <?php if ($error): ?>
            <p role="alert"><?= e($error) ?></p>
        <?php elseif (!$artists): ?>
            <p>Aún no hay artistas disponibles.</p>
        <?php else: ?>
            <div class="artists-grid">
                <?php foreach ($artists as $artist): ?>
                    <article class="artist-card">
                        <a class="artist-directory-card" href="artista.php?artista=<?= rawurlencode(artist_slug($artist['nombre'])) ?>">
                            <?php if ($artist['imagen']): ?>
                                <img class="artist-directory-image" src="<?= e($artist['imagen']) ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <div class="artist-directory-copy">
                                <h3><?= e($artist['nombre']) ?></h3>
                                <?php if ($artist['genero']): ?><p><?= e($artist['genero']) ?></p><?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= sideb_footer() ?>
</body>
</html>
