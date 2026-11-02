<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$featuredPosts = [];
try {
    $statement = db()->prepare(
        "SELECT b.titulo, b.`desc`, b.img, b.tags, b.slug, d.fecha_publicacion,
                (SELECT GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ' / ')
                 FROM blog_artista ba
                 INNER JOIN artista a ON a.id_artista = ba.artista_id
                 WHERE ba.blog_id = b.id_blog AND a.activo = TRUE) AS artista_nombre,
                COALESCE(p.alias, u.nom_usr) AS autor, r.nombre AS rol_autor
         FROM blog AS b
         INNER JOIN blog_detalle AS d ON d.blog_id = b.id_blog
         INNER JOIN usuario AS u ON u.id_usr = d.autor_id
         INNER JOIN rol AS r ON r.id_rol = u.tip_usu
         LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr
         WHERE d.estado = 'publicado' AND d.fecha_publicacion IS NOT NULL
           AND d.fecha_publicacion <= NOW()
         ORDER BY d.fecha_publicacion DESC
         LIMIT 3"
    );
    $statement->execute();
    $featuredPosts = $statement->fetchAll();
} catch (Throwable) {
    // El inicio sigue disponible aunque no se pueda cargar esta sección.
}
$shopProducts = [];
$shopCategories = [];
try {
    $shopProducts = db()->query('SELECT p.nombre,p.slug,p.precio,p.imagen,c.nombre categoria FROM producto p INNER JOIN categoria_producto c ON c.id_categoria=p.categoria_id WHERE p.activo=TRUE AND p.stock>0 AND p.slug IS NOT NULL ORDER BY p.fecha_actualizacion DESC LIMIT 3')->fetchAll();
    $shopCategories = db()->query('SELECT c.nombre,COUNT(p.id_producto) total FROM categoria_producto c INNER JOIN producto p ON p.categoria_id=c.id_categoria WHERE p.activo=TRUE AND p.stock>0 GROUP BY c.id_categoria,c.nombre ORDER BY c.nombre')->fetchAll();
} catch (Throwable) {
    // La portada continúa disponible si el catálogo falla.
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="SIDE B: historias, lanzamientos, merch y una comunidad real alrededor de la cultura musical.">
  <title>SIDE B — Más que música</title>
<link rel="icon" href="assets/favicons/favicon.ico" sizes="any">
<link rel="icon" href="assets/favicons/favicon.svg" type="image/svg+xml">
<link rel="icon" href="assets/favicons/favicon-96x96.png" type="image/png" sizes="96x96">
<link rel="apple-touch-icon" href="assets/favicons/apple-touch-icon.png">
<link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/posts-dynamic.css">
  <style>
    .shop-section .product-grid{grid-template-columns:repeat(auto-fill,minmax(210px,280px));justify-content:start}
    .shop-section .product-image{background:var(--color-paper)!important}
    .shop-section .product-image::before,.shop-section .product-image::after{display:none!important}
    .shop-section .product-image img{display:block;height:100%;object-fit:contain;position:relative;width:100%;z-index:1}
    @media(max-width:560px){.shop-section .product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  </style>

</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>

  <?= sideb_public_header('inicio') ?>

  <main id="contenido">
    <section class="hero-section container" aria-labelledby="hero-title">
      <div class="hero-copy">
        <p class="eyebrow">Cultura musical independiente</p>
        <h1 id="hero-title">Más que<br><em>música.</em></h1>
        <p class="hero-description">Historias, lanzamientos, merch y una comunidad real para quienes quieren escuchar un poco más allá.</p>
        <div class="button-group">
          <a class="button button-primary" href="#destacados">Explorar contenido <span aria-hidden="true">↗</span></a>
          <a class="button button-secondary" href="artistas.php">Descubrir artistas</a>
          <?php if (is_editorial_user()): ?><a class="button button-secondary" href="<?= in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true) ? 'backstage.php' : 'editorial.php' ?>"><?= (int) ($_SESSION['role_id'] ?? 0) === 2 ? 'Mis publicaciones' : 'Backstage' ?></a><?php endif; ?>
        </div>
        <div class="hero-note"><span class="note-line"></span><span>Lo que no sabías que querías escuchar</span></div>
      </div>
      <div class="hero-art visual-surface visual-surface--hero" role="img" aria-label="Composición abstracta editorial en terracota y oliva">
        <span class="hero-art-label">SIDE B / 001</span>
        <span class="hero-art-title">The<br>other<br>side</span>
        <span class="hero-art-circle"></span>
        <span class="hero-art-caption">Vol. 01 — Nuevas coordenadas</span>
      </div>
    </section>

    <section class="section container" id="destacados" aria-labelledby="featured-title">
      <div class="section-heading">
        <div><p class="eyebrow">Para empezar por aquí</p><h2 id="featured-title">Destacados</h2></div>
        <a class="text-link" href="publicaciones.php">Ver todos <span aria-hidden="true">↗</span></a>
      </div>
      <?php if ($featuredPosts): ?>
        <div class="editorial-grid">
          <?php $surfaces = ['visual-surface--portrait', 'visual-surface--warm', 'visual-surface--olive']; ?>
          <?php foreach ($featuredPosts as $index => $featured): ?>
            <?php
              $editorialLabel = publication_editorial_label($featured['artista_nombre'] ?? null);
              $editorialKicker = publication_editorial_kicker($featured['artista_nombre'] ?? null);
              $image = publication_image_url($featured['img']);
              $date = date('d/m/Y', strtotime($featured['fecha_publicacion']));
            ?>
            <article class="story-card <?= $index === 0 ? 'story-card--large' : '' ?>">
              <a class="story-image visual-surface <?= e($surfaces[$index]) ?>" href="publicacion.php?slug=<?= rawurlencode($featured['slug']) ?>" aria-label="Leer: <?= e($featured['titulo']) ?>">
                <?php if ($image): ?><img src="<?= e($image) ?>" alt=""><?php endif; ?>
                <?php if ($index === 0): ?><span class="image-stamp"><?= e($editorialLabel) ?></span><?php endif; ?>
                <span class="image-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
              </a>
              <div class="story-content">
                <p class="card-kicker"><?= e($editorialKicker) ?></p>
                <h3><a href="publicacion.php?slug=<?= rawurlencode($featured['slug']) ?>"><?= e($featured['titulo']) ?></a></h3>
                <?php if ($index === 0 && !empty($featured['desc'])): ?><p class="card-excerpt"><?= e($featured['desc']) ?></p><?php endif; ?>
                <div class="card-meta"><span>Por <?= e($featured['autor']) ?> · <?= e($featured['rol_autor']) ?></span><time datetime="<?= e($featured['fecha_publicacion']) ?>"><?= e($date) ?></time></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="card-excerpt">Aún no hay publicaciones destacadas.</p>
      <?php endif; ?>
    </section>

    <section class="discovery-section" id="artistas" aria-labelledby="discovery-title">
      <div class="container discovery-inner">
        <div class="discovery-intro"><p class="eyebrow">Nuevas coordenadas</p><h2 id="discovery-title">Descubre<br><em>algo distinto.</em></h2><p>Artistas que están haciendo ruido en los márgenes. Sin algoritmos, con criterio.</p><a class="button button-light" href="artistas.php">Explorar artistas <span aria-hidden="true">↗</span></a></div>
        <div class="artist-feature visual-surface visual-surface--artist"><div class="artist-feature-top"><span>ARTISTA DESTACADO</span><span>01 / 03</span></div><div class="artist-feature-info"><p>Desde Guadalajara</p><h3>La Niebla</h3><a href="#la-niebla" class="circle-arrow" aria-label="Ver perfil de La Niebla">↗</a></div></div>
      </div>
    </section>

    <section class="section shop-section container" id="tienda" aria-labelledby="shop-title">
      <div class="section-heading"><div><p class="eyebrow">Objetos para escuchar y llevar</p><h2 id="shop-title">Tienda destacada</h2></div><a class="text-link" href="tienda.php">Ver tienda <span aria-hidden="true">↗</span></a></div>
      <div class="shop-layout">
        <aside class="shop-categories" aria-label="Categorías de tienda"><p class="filter-label">Explora por categoría</p><?php foreach($shopCategories as $index=>$category):?><a class="category-link <?= $index===0?'is-selected':'' ?>" href="tienda.php"><?=e($category['nombre'])?> <span><?= (int)$category['total'] ?></span></a><?php endforeach;?></aside>
        <div class="product-grid">
          <?php if(!$shopProducts):?><p class="card-excerpt">Aún no hay productos disponibles.</p><?php endif;?><?php foreach($shopProducts as $product):?><article class="product-card"><a class="product-image visual-surface visual-surface--record" href="producto.php?slug=<?=rawurlencode($product['slug'])?>"><?php if($product['imagen']):?><img src="<?=e($product['imagen'])?>" alt="<?=e($product['nombre'])?>"><?php endif;?></a><div class="product-info"><div><p class="card-kicker"><?=e($product['categoria'])?></p><h3><a href="producto.php?slug=<?=rawurlencode($product['slug'])?>"><?=e($product['nombre'])?></a></h3></div><strong>$<?=number_format((float)$product['precio'],2)?></strong></div></article><?php endforeach;?>
        </div>
      </div>
    </section>

  </main>

  <?= sideb_footer() ?>
</body>
</html>




