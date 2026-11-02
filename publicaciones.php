<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$posts = [];
$loadError = null;
try {
    $statement = db()->prepare(
        "SELECT b.id_blog, b.titulo, b.`desc`, b.img, b.tags, b.slug,
                d.fecha_publicacion, u.nom_usr, COALESCE(p.alias, u.nom_usr) AS autor, r.nombre AS rol_autor,
                (SELECT GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ' / ')
                 FROM blog_artista ba
                 INNER JOIN artista a ON a.id_artista = ba.artista_id
                 WHERE ba.blog_id = b.id_blog AND a.activo = TRUE) AS artista_nombre
         FROM blog AS b
         INNER JOIN blog_detalle AS d ON d.blog_id = b.id_blog
         INNER JOIN usuario AS u ON u.id_usr = d.autor_id
         INNER JOIN rol AS r ON r.id_rol = u.tip_usu
         LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr
         WHERE d.estado = 'publicado' AND d.fecha_publicacion IS NOT NULL AND d.fecha_publicacion <= NOW()
         ORDER BY d.fecha_publicacion DESC"
    );
    $statement->execute();
    $posts = $statement->fetchAll();
} catch (Throwable) {
    $loadError = 'No fue posible cargar las publicaciones. Intenta de nuevo más tarde.';
}

$surfaces = ['visual-surface--portrait', 'visual-surface--warm', 'visual-surface--olive'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Publicaciones y artículos de cultura musical en SIDE B.">
  <title>Publicaciones — SIDE B</title>
  <link rel="icon" href="assets/favicons/favicon.ico" sizes="any">
  <link rel="icon" href="assets/favicons/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="assets/favicons/apple-touch-icon.png">
  <link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/posts.css">
  <link rel="stylesheet" href="assets/css/posts-dynamic.css">
</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php">SIDE B<span class="brand-dot">.</span></a>
      <nav class="primary-nav" aria-label="Navegación principal">
        <a class="nav-link" href="index.php">Inicio</a><a class="nav-link" href="artistas.php">Artistas</a><a class="nav-link" href="#comunidad">Comunidad</a><a class="nav-link" href="tienda.php">Tienda</a>
      </nav>
      <div class="header-actions">
        <a class="icon-button" href="#buscar" aria-label="Buscar"><span class="icon icon-search"></span></a>
        <a class="icon-button" href="perfil.php" aria-label="Mi perfil"><span class="icon icon-user"></span></a>
        <a class="icon-button cart-button" href="carrito.php" aria-label="Carrito"><span class="icon icon-bag"></span><span class="cart-count">0</span></a>
      </div>
      <button class="menu-button" type="button" aria-label="Abrir menú"><span></span><span></span></button>
    <?= logout_control() ?></div></header>
  <main id="contenido">
    <section class="posts-hero container"><p class="eyebrow">Historias para escuchar distinto</p><h1>Publicaciones<br><em>y hallazgos.</em></h1><p>Entrevistas, contexto y selecciones para mirar la música desde otro lugar.</p></section>
    <section class="container">
      <nav class="category-tabs" aria-label="Filtrar publicaciones"><a class="is-active" href="publicaciones.php">Todos</a></nav>
      <?php if ($loadError): ?>
        <p class="profile-note"><?= e($loadError) ?></p>
      <?php elseif (!$posts): ?>
        <p class="profile-note">Aún no hay publicaciones disponibles.</p>
      <?php else: ?>
        <div class="posts-grid">
          <?php foreach ($posts as $index => $post): ?>
            <?php
              $editorialLabel = publication_editorial_label($post['artista_nombre'] ?? null);
              $editorialKicker = publication_editorial_kicker($post['artista_nombre'] ?? null);
              $date = date('d/m/Y', strtotime($post['fecha_publicacion']));
              $excerpt = (string) ($post['desc'] ?? '');
              $image = publication_image_url($post['img']);
            ?>
            <article class="story-card <?= $index === 0 ? 'story-card--featured' : '' ?>">
              <a class="story-image visual-surface <?= e($surfaces[$index % count($surfaces)]) ?>" href="publicacion.php?slug=<?= rawurlencode($post['slug']) ?>" aria-label="Leer <?= e($post['titulo']) ?>">
                <?php if ($image): ?><img src="<?= e($image) ?>" alt=""><?php endif; ?>
                <?php if ($index === 0): ?><span class="image-stamp"><?= e($editorialLabel) ?></span><?php endif; ?>
                <span class="image-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
              </a>
              <div class="story-content">
                <p class="card-kicker"><?= e($editorialKicker) ?></p>
                <?php if ($index === 0): ?><h2><?php else: ?><h3><?php endif; ?><a href="publicacion.php?slug=<?= rawurlencode($post['slug']) ?>"><?= e($post['titulo']) ?></a><?php if ($index === 0): ?></h2><?php else: ?></h3><?php endif; ?>
                <?php if ($excerpt !== ''): ?><p class="card-excerpt"><?= e($excerpt) ?></p><?php endif; ?>
                <div class="card-meta"><span>Por <?= e($post['autor']) ?> · <?= e($post['rol_autor']) ?></span><time datetime="<?= e($post['fecha_publicacion']) ?>"><?= e($date) ?></time></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <?= sideb_footer() ?>
</body>
</html>
